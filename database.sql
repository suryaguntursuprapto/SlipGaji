-- Buat Database
CREATE DATABASE IF NOT EXISTS slip_gaji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE slip_gaji;

-- Drop tables jika sudah ada (untuk fresh install)
DROP TABLE IF EXISTS slip_detail;
DROP TABLE IF EXISTS slip_gaji;
DROP TABLE IF EXISTS komponen_gaji;
DROP TABLE IF EXISTS kategori_komponen;
DROP TABLE IF EXISTS karyawan;
DROP TABLE IF EXISTS pengaturan;

-- Tabel Karyawan
CREATE TABLE IF NOT EXISTS karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    jabatan VARCHAR(100) DEFAULT NULL,
    departemen VARCHAR(100) DEFAULT NULL,
    no_rekening VARCHAR(50) DEFAULT NULL,
    bank VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Kategori Komponen (dynamic categories)
CREATE TABLE IF NOT EXISTS kategori_komponen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tipe ENUM('pendapatan', 'potongan') NOT NULL,
    icon VARCHAR(10) DEFAULT '📋',
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Komponen Gaji (master data pendapatan & potongan)
CREATE TABLE IF NOT EXISTS komponen_gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tipe ENUM('pendapatan', 'potongan') NOT NULL,
    kategori_id INT DEFAULT NULL,
    urutan INT DEFAULT 0,
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_komponen(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel Slip Gaji (header)
CREATE TABLE IF NOT EXISTS slip_gaji (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karyawan_id INT NOT NULL,
    bulan INT NOT NULL,
    tahun INT NOT NULL,
    keterangan TEXT DEFAULT NULL,
    email_sent TINYINT(1) DEFAULT 0,
    email_sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slip (karyawan_id, bulan, tahun)
) ENGINE=InnoDB;

-- Tabel Detail Slip Gaji
CREATE TABLE IF NOT EXISTS slip_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slip_gaji_id INT NOT NULL,
    komponen_id INT NOT NULL,
    jumlah DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (slip_gaji_id) REFERENCES slip_gaji(id) ON DELETE CASCADE,
    FOREIGN KEY (komponen_id) REFERENCES komponen_gaji(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Pengaturan
CREATE TABLE IF NOT EXISTS pengaturan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- Default pengaturan
INSERT INTO pengaturan (setting_key, setting_value) VALUES
('nama_perusahaan', 'Spicy Lips x Bergamot Koffie'),
('alamat_perusahaan', 'Jl. Contoh Alamat No. 123, Kota'),
('email_smtp_host', 'smtp.gmail.com'),
('email_smtp_port', '587'),
('email_smtp_user', ''),
('email_smtp_pass', ''),
('email_from_name', 'Maritza Raras'),
('email_from_title', 'Finance Supervisor');

-- Default kategori pendapatan
INSERT INTO kategori_komponen (nama, tipe, icon, urutan) VALUES
('Gaji Pokok', 'pendapatan', '🏦', 1),
('Tunjangan', 'pendapatan', '📋', 2),
('Lembur', 'pendapatan', '⏰', 3);

-- Default kategori potongan
INSERT INTO kategori_komponen (nama, tipe, icon, urutan) VALUES
('Potongan', 'potongan', '📉', 1);

-- Default komponen pendapatan
INSERT INTO komponen_gaji (nama, tipe, kategori_id, urutan) VALUES
('Gaji Pokok', 'pendapatan', 1, 1),
('Tunj. Transportasi', 'pendapatan', 2, 2),
('Tunj. Kehadiran', 'pendapatan', 2, 3),
('Tunj. Jabatan', 'pendapatan', 2, 4),
('Tunj. Lain-lain', 'pendapatan', 2, 5),
('Lembur', 'pendapatan', 3, 6);

-- Default komponen potongan
INSERT INTO komponen_gaji (nama, tipe, kategori_id, urutan) VALUES
('Potongan', 'potongan', 4, 1);

-- Data contoh karyawan
INSERT INTO karyawan (nik, nama, email, jabatan, departemen, no_rekening, bank) VALUES
('025', 'Maritza Raras', 'maritza@example.com', 'SPV Accounting', 'Keuangan', '1234567890', 'BCA'),
('026', 'Ahmad Fauzi', 'ahmad@example.com', 'Barista', 'Operasional', '0987654321', 'BRI'),
('027', 'Siti Nurhaliza', 'siti@example.com', 'Kasir', 'Keuangan', '1122334455', 'Mandiri');

-- Tabel Users (Login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin user (password: admin123)
INSERT INTO users (username, password, nama) VALUES
('admin', '$2y$10$spZzlfm4KjKD42iyAAcChOwTgywQyEfZhQ01K4qsXZ49ckEUiUJI2', 'Administrator');
