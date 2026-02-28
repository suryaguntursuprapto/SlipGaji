<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

header('Content-Type: application/json');
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

$tipe = $_GET['tipe'] ?? null;

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT kg.*, kk.nama as kategori_nama FROM komponen_gaji kg LEFT JOIN kategori_komponen kk ON kg.kategori_id = kk.id WHERE kg.id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch());
        } else {
            $sql = "SELECT kg.*, kk.nama as kategori_nama, kk.icon as kategori_icon 
                    FROM komponen_gaji kg 
                    LEFT JOIN kategori_komponen kk ON kg.kategori_id = kk.id";
            $params = [];
            if ($tipe) {
                $sql .= " WHERE kg.tipe = ?";
                $params[] = $tipe;
            }
            $sql .= " ORDER BY kg.tipe ASC, kg.urutan ASC, kg.id ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        $nama = $data['nama'] ?? '';
        $tipe = $data['tipe'] ?? '';
        $kategori_id = $data['kategori_id'] ?? null;
        
        if (!$nama || !$tipe) {
            echo json_encode(['success' => false, 'message' => 'Nama dan tipe wajib diisi']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 FROM komponen_gaji WHERE tipe = ?");
        $stmt->execute([$tipe]);
        $nextUrutan = $stmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO komponen_gaji (nama, tipe, kategori_id, urutan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $tipe, $kategori_id, $nextUrutan]);
        echo json_encode(['success' => true, 'message' => 'Komponen berhasil ditambahkan', 'id' => $db->lastInsertId()]);
        break;

    case 'PUT':
        $id = $data['id'] ?? null;
        $nama = $data['nama'] ?? '';
        $kategori_id = $data['kategori_id'] ?? null;
        $aktif = isset($data['aktif']) ? intval($data['aktif']) : 1;
        
        if (!$id || !$nama) {
            echo json_encode(['success' => false, 'message' => 'ID dan nama wajib diisi']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE komponen_gaji SET nama = ?, kategori_id = ?, aktif = ? WHERE id = ?");
        $stmt->execute([$nama, $kategori_id, $aktif, $id]);
        echo json_encode(['success' => true, 'message' => 'Komponen berhasil diperbarui']);
        break;

    case 'DELETE':
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID wajib diisi']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM slip_detail WHERE komponen_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Komponen tidak bisa dihapus karena sudah digunakan. Nonaktifkan saja.']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM komponen_gaji WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Komponen berhasil dihapus']);
        break;
}
