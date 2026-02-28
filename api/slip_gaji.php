<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

header('Content-Type: application/json');
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        $bulan = $_GET['bulan'] ?? date('n');
        $tahun = $_GET['tahun'] ?? date('Y');
        
        if (isset($_GET['id'])) {
            // Get single slip with details
            $stmt = $db->prepare("
                SELECT s.*, k.nik, k.nama, k.email, k.jabatan, k.departemen, k.no_rekening, k.bank
                FROM slip_gaji s
                JOIN karyawan k ON s.karyawan_id = k.id
                WHERE s.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $slip = $stmt->fetch();
            
            if ($slip) {
                // Get details
                $detailStmt = $db->prepare("
                    SELECT sd.*, kg.nama as komponen_nama, kg.tipe,
                           kk.nama as kategori_nama
                    FROM slip_detail sd
                    JOIN komponen_gaji kg ON sd.komponen_id = kg.id
                    LEFT JOIN kategori_komponen kk ON kg.kategori_id = kk.id
                    WHERE sd.slip_gaji_id = ?
                    ORDER BY kg.tipe ASC, kg.urutan ASC
                ");
                $detailStmt->execute([$_GET['id']]);
                $slip['details'] = $detailStmt->fetchAll();
                
                // Calculate totals
                $totalPendapatan = 0;
                $totalPotongan = 0;
                foreach ($slip['details'] as $d) {
                    if ($d['tipe'] === 'pendapatan') $totalPendapatan += $d['jumlah'];
                    else $totalPotongan += $d['jumlah'];
                }
                $slip['total_pendapatan'] = $totalPendapatan;
                $slip['total_potongan'] = $totalPotongan;
                $slip['gaji_bersih'] = $totalPendapatan - $totalPotongan;
            }
            
            echo json_encode($slip);
        } else {
            // Get all slips for period
            $stmt = $db->prepare("
                SELECT s.*, k.nik, k.nama, k.jabatan
                FROM slip_gaji s
                JOIN karyawan k ON s.karyawan_id = k.id
                WHERE s.bulan = ? AND s.tahun = ?
                ORDER BY k.nama ASC
            ");
            $stmt->execute([$bulan, $tahun]);
            $slips = $stmt->fetchAll();
            
            // Calculate totals for each slip
            foreach ($slips as &$slip) {
                $detailStmt = $db->prepare("
                    SELECT kg.tipe, SUM(sd.jumlah) as total
                    FROM slip_detail sd
                    JOIN komponen_gaji kg ON sd.komponen_id = kg.id
                    WHERE sd.slip_gaji_id = ?
                    GROUP BY kg.tipe
                ");
                $detailStmt->execute([$slip['id']]);
                $totals = $detailStmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                $slip['total_pendapatan'] = floatval($totals['pendapatan'] ?? 0);
                $slip['total_potongan'] = floatval($totals['potongan'] ?? 0);
                $slip['gaji_bersih'] = $slip['total_pendapatan'] - $slip['total_potongan'];
            }
            
            echo json_encode($slips);
        }
        break;

    case 'POST':
        $karyawanId = $data['karyawan_id'] ?? null;
        $bulan = $data['bulan'] ?? null;
        $tahun = $data['tahun'] ?? null;
        $keterangan = $data['keterangan'] ?? '';
        $details = $data['details'] ?? [];

        if (!$karyawanId || !$bulan || !$tahun) {
            echo json_encode(['success' => false, 'message' => 'Karyawan, bulan, dan tahun wajib diisi']);
            exit;
        }

        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO slip_gaji (karyawan_id, bulan, tahun, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$karyawanId, $bulan, $tahun, $keterangan]);
            $slipId = $db->lastInsertId();

            // Insert details
            $detailStmt = $db->prepare("INSERT INTO slip_detail (slip_gaji_id, komponen_id, jumlah) VALUES (?, ?, ?)");
            foreach ($details as $detail) {
                if (floatval($detail['jumlah']) > 0) {
                    $detailStmt->execute([$slipId, $detail['komponen_id'], $detail['jumlah']]);
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Slip gaji berhasil dibuat', 'id' => $slipId]);
        } catch (Exception $e) {
            $db->rollBack();
            $msg = $e->getMessage();
            if (strpos($msg, 'unique_slip') !== false) {
                echo json_encode(['success' => false, 'message' => 'Slip gaji untuk karyawan ini di periode tersebut sudah ada']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $msg]);
            }
        }
        break;

    case 'PUT':
        $id = $data['id'] ?? null;
        $keterangan = $data['keterangan'] ?? '';
        $details = $data['details'] ?? [];

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID slip gaji tidak ditemukan']);
            exit;
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE slip_gaji SET keterangan = ? WHERE id = ?");
            $stmt->execute([$keterangan, $id]);

            // Delete old details and re-insert
            $db->prepare("DELETE FROM slip_detail WHERE slip_gaji_id = ?")->execute([$id]);
            
            $detailStmt = $db->prepare("INSERT INTO slip_detail (slip_gaji_id, komponen_id, jumlah) VALUES (?, ?, ?)");
            foreach ($details as $detail) {
                if (floatval($detail['jumlah']) > 0) {
                    $detailStmt->execute([$id, $detail['komponen_id'], $detail['jumlah']]);
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Slip gaji berhasil diperbarui']);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID slip gaji tidak ditemukan']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM slip_gaji WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Slip gaji berhasil dihapus']);
        break;
}
