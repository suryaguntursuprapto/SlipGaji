# 💼 Slip Gaji - Spicy Lips x Bergamot Koffie

Sistem Payroll / Slip Gaji otomatis berbasis web untuk manajemen gaji karyawan. Dilengkapi fitur generate PDF, kirim email otomatis, dan komponen gaji dinamis.

---

## 📋 Fitur

- 🔐 **Login** — Autentikasi dengan password terenkripsi (bcrypt)
- 📊 **Dashboard** — Ringkasan data karyawan dan slip gaji
- 👥 **Data Karyawan** — CRUD data karyawan dengan pencarian
- 📄 **Slip Gaji** — Buat, edit, dan kelola slip gaji per bulan
- 📥 **Generate PDF** — Cetak slip gaji dalam format PDF profesional
- 📧 **Kirim Email** — Kirim slip gaji via email (SMTP Gmail)
- 💰 **Komponen Gaji** — Kelola pendapatan & potongan secara dinamis
- ⚙️ **Pengaturan** — Konfigurasi perusahaan, SMTP, dan ubah password

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 7.4+ |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, JavaScript (Vanilla) |
| PDF | TCPDF |
| Email | PHPMailer |
| Server | XAMPP / Apache |

---

## 🚀 Cara Menjalankan

### 1. Install XAMPP

Download dan install [XAMPP](https://www.apachefriends.org/download.html) yang sudah include Apache, MySQL, dan PHP.

### 2. Clone Repository

```bash
cd /Applications/XAMPP/xamppfiles/htdocs    # macOS
# cd C:\xampp\htdocs                         # Windows

git clone https://github.com/suryaguntursuprapto/SlipGaji.git
cd SlipGaji
```

### 3. Install Dependencies (PHPMailer)

```bash
composer install
```

> Jika belum ada Composer, install dulu: [getcomposer.org](https://getcomposer.org/download/)

### 4. Buat Database

1. Buka **phpMyAdmin** di `http://localhost/phpmyadmin`
2. Buat database baru dengan nama `slip_gaji`
3. Import file `database.sql`:
   - Klik database `slip_gaji`
   - Pilih tab **Import**
   - Pilih file `database.sql` dari folder project
   - Klik **Go**

**Atau via terminal:**

```bash
# macOS (XAMPP)
/Applications/XAMPP/xamppfiles/bin/mysql -u root < database.sql

# Windows
C:\xampp\mysql\bin\mysql -u root < database.sql
```

### 5. Konfigurasi Database (Opsional)

Jika MySQL menggunakan password, edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'slip_gaji');
define('DB_USER', 'root');
define('DB_PASS', '');  // ← isi password MySQL kamu
```

### 6. Jalankan XAMPP

1. Buka **XAMPP Control Panel**
2. Start **Apache** dan **MySQL**
3. Buka browser dan akses: **`http://localhost/SlipGaji`**

---

## 🔐 Login Default

| Username | Password |
|----------|----------|
| `admin`  | `admin123` |

> ⚠️ **Segera ubah password default** setelah login pertama kali di menu **⚙️ Pengaturan → 🔒 Ubah Password**

---

## 📧 Konfigurasi Email (Gmail SMTP)

Untuk mengaktifkan fitur kirim email, ikuti langkah berikut:

1. Login ke aplikasi → buka **⚙️ Pengaturan**
2. Isi konfigurasi SMTP:
   - **SMTP Host**: `smtp.gmail.com`
   - **SMTP Port**: `587`
   - **Username**: `emailkamu@gmail.com`
   - **Password**: App Password (bukan password Gmail biasa)
3. Klik **💾 Simpan**

### Cara Membuat App Password Gmail:
1. Buka [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
2. Login dengan akun Gmail
3. Pilih app: **Mail**, device: **Other** → beri nama "SlipGaji"
4. Copy password 16 karakter yang digenerate
5. Paste ke field **Password** di pengaturan SMTP

---

## 📁 Struktur Folder

```
SlipGaji/
├── api/                  # Backend API endpoints
│   ├── auth.php          # Login, logout, ubah password
│   ├── dashboard.php     # Data dashboard
│   ├── karyawan.php      # CRUD karyawan
│   ├── komponen_gaji.php # CRUD komponen gaji
│   ├── kategori_komponen.php  # CRUD kategori
│   ├── pengaturan.php    # Settings
│   ├── send_email.php    # Kirim email
│   └── slip_gaji.php     # CRUD slip gaji
├── assets/
│   ├── css/style.css     # Stylesheet
│   ├── img/logo.png      # Logo perusahaan
│   └── js/app.js         # Frontend logic
├── config/
│   └── database.php      # Konfigurasi database
├── vendor/               # PHPMailer (via Composer)
├── composer.json         # Dependencies
├── database.sql          # Database schema + seed data
├── generate_pdf.php      # PDF generator (TCPDF)
├── index.php             # Halaman utama
└── README.md             # File ini
```

---

## 📝 License

© 2026 Spicy Lips x Bergamot Koffie. All rights reserved.
