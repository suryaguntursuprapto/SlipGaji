<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET = check session
if ($method === 'GET') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nama' => $_SESSION['nama']
            ]
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
    exit;
}

// POST = login
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'login';
    
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Berhasil logout']);
        exit;
    }
    
    if ($action === 'change_password') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
            exit;
        }
        $oldPass = $data['old_password'] ?? '';
        $newPass = $data['new_password'] ?? '';
        
        if (!$oldPass || !$newPass) {
            echo json_encode(['success' => false, 'message' => 'Password lama dan baru harus diisi']);
            exit;
        }
        if (strlen($newPass) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter']);
            exit;
        }
        
        $db = getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($oldPass, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Password lama salah']);
            exit;
        }
        
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
        exit;
    }
    
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (!$username || !$password) {
        echo json_encode(['success' => false, 'message' => 'Username dan password harus diisi']);
        exit;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
        exit;
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama'] = $user['nama'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'nama' => $user['nama']
        ]
    ]);
    exit;
}
