<?php 
session_start();
require_once __DIR__ . '/config/database.php'; 
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['nama'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - Spicy Lips x Bergamot Koffie</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <meta name="description" content="Sistem Slip Gaji Otomatis - Spicy Lips x Bergamot Koffie">
</head>
<body>
    <!-- LOGIN PAGE -->
    <div id="loginPage" class="login-page" style="display: <?= $isLoggedIn ? 'none' : 'flex' ?>;">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/img/logo.png" alt="Spicy Lips x Bergamot" class="login-logo">
                <h1 class="login-title">Payroll System</h1>
                <p class="login-subtitle">Silakan masuk untuk melanjutkan</p>
            </div>
            <form id="loginForm" onsubmit="App.doLogin(event)">
                <div class="login-form-group">
                    <label>👤 Username</label>
                    <input type="text" class="form-control" id="login_username" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="login-form-group">
                    <label>🔒 Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="login_password" placeholder="Masukkan password" required>
                        <button type="button" class="password-toggle" onclick="App.togglePassword()">👁️</button>
                    </div>
                </div>
                <div id="loginError" class="login-error" style="display:none;"></div>
                <button type="submit" class="btn btn-primary login-btn" id="loginBtn">🔐 Masuk</button>
            </form>
            <div class="login-footer">
                <small>© 2026 Spicy Lips x Bergamot Koffie</small>
            </div>
        </div>
    </div>

    <!-- MAIN APP -->
    <div id="mainApp" style="display: <?= $isLoggedIn ? 'block' : 'none' ?>;">
        <button class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
        
        <div class="app-container">
            <!-- Sidebar Navigation -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <img src="assets/img/logo.png" alt="Spicy Lips x Bergamot" class="sidebar-logo">
                    <div class="sidebar-subtitle">Payroll System</div>
                </div>
                <nav class="sidebar-nav">
                    <div class="nav-section-title">Menu</div>
                    <a href="#dashboard" class="nav-item active" data-page="dashboard">
                        <span class="icon">📊</span> Dashboard
                    </a>
                    <a href="#karyawan" class="nav-item" data-page="karyawan">
                        <span class="icon">👥</span> Data Karyawan
                    </a>
                    <a href="#slip-gaji" class="nav-item" data-page="slip-gaji">
                        <span class="icon">📄</span> Slip Gaji
                    </a>
                    <a href="#kirim-email" class="nav-item" data-page="kirim-email">
                        <span class="icon">📧</span> Kirim Email
                    </a>
                    <div class="nav-section-title">Komponen Gaji</div>
                    <a href="#pendapatan" class="nav-item" data-page="pendapatan">
                        <span class="icon">💰</span> Kelola Pendapatan
                    </a>
                    <a href="#potongan" class="nav-item" data-page="potongan">
                        <span class="icon">📉</span> Kelola Potongan
                    </a>
                    <div class="nav-section-title">Sistem</div>
                    <a href="#pengaturan" class="nav-item" data-page="pengaturan">
                        <span class="icon">⚙️</span> Pengaturan
                    </a>
                </nav>
                <div class="sidebar-footer">
                    <div class="user-info">
                        <span class="user-name">👤 <?= htmlspecialchars($userName) ?></span>
                        <button class="btn btn-xs btn-outline logout-btn" onclick="App.doLogout()">🚪 Logout</button>
                    </div>
                    <small>© 2026 Spicy Lips x Bergamot</small>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <div id="page-content">
                    <!-- Pages will be loaded here -->
                </div>
            </main>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script>
        window._isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    </script>
    <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
