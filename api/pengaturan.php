<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();
header('Content-Type: application/json');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $db->query("SELECT * FROM pengaturan ORDER BY setting_key ASC");
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        foreach ($data as $key => $value) {
            setSetting($key, $value);
        }
        
        echo json_encode(['success' => true, 'message' => 'Pengaturan berhasil disimpan']);
        break;
}
