<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();
header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM karyawan WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch());
        } else {
            $stmt = $db->query("SELECT * FROM karyawan ORDER BY nama ASC");
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['nik']) || empty($data['nama']) || empty($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'NIK, Nama, dan Email wajib diisi']);
            break;
        }

        $stmt = $db->prepare("INSERT INTO karyawan (nik, nama, email, jabatan, departemen, no_rekening, bank) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['nik'],
            $data['nama'],
            $data['email'],
            $data['jabatan'] ?? null,
            $data['departemen'] ?? null,
            $data['no_rekening'] ?? null,
            $data['bank'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Karyawan berhasil ditambahkan', 'id' => $db->lastInsertId()]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID karyawan tidak ditemukan']);
            break;
        }

        $stmt = $db->prepare("UPDATE karyawan SET nik=?, nama=?, email=?, jabatan=?, departemen=?, no_rekening=?, bank=? WHERE id=?");
        $stmt->execute([
            $data['nik'],
            $data['nama'],
            $data['email'],
            $data['jabatan'] ?? null,
            $data['departemen'] ?? null,
            $data['no_rekening'] ?? null,
            $data['bank'] ?? null,
            $data['id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Data karyawan berhasil diperbarui']);
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID karyawan tidak ditemukan']);
            break;
        }

        $stmt = $db->prepare("DELETE FROM karyawan WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Karyawan berhasil dihapus']);
        break;
}
