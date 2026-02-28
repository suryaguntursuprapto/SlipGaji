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
        $sql = "SELECT * FROM kategori_komponen";
        $params = [];
        if ($tipe) {
            $sql .= " WHERE tipe = ?";
            $params[] = $tipe;
        }
        $sql .= " ORDER BY tipe ASC, urutan ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $nama = $data['nama'] ?? '';
        $tipe = $data['tipe'] ?? '';
        $icon = $data['icon'] ?? '📋';
        
        if (!$nama || !$tipe) {
            echo json_encode(['success' => false, 'message' => 'Nama dan tipe wajib diisi']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 FROM kategori_komponen WHERE tipe = ?");
        $stmt->execute([$tipe]);
        $nextUrutan = $stmt->fetchColumn();

        $stmt = $db->prepare("INSERT INTO kategori_komponen (nama, tipe, icon, urutan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $tipe, $icon, $nextUrutan]);
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil ditambahkan', 'id' => $db->lastInsertId()]);
        break;

    case 'PUT':
        $id = $data['id'] ?? null;
        $nama = $data['nama'] ?? '';
        $icon = $data['icon'] ?? '📋';
        
        if (!$id || !$nama) {
            echo json_encode(['success' => false, 'message' => 'ID dan nama wajib diisi']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE kategori_komponen SET nama = ?, icon = ? WHERE id = ?");
        $stmt->execute([$nama, $icon, $id]);
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil diperbarui']);
        break;

    case 'DELETE':
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID wajib diisi']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM komponen_gaji WHERE kategori_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak bisa dihapus karena masih digunakan komponen']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM kategori_komponen WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Kategori berhasil dihapus']);
        break;
}
