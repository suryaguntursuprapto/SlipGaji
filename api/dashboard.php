<?php
require_once __DIR__ . '/../config/database.php';
requireAuth();

header('Content-Type: application/json');
$db = getDB();

// Total karyawan
$totalKaryawan = $db->query("SELECT COUNT(*) FROM karyawan")->fetchColumn();

// Total slip
$totalSlip = $db->query("SELECT COUNT(*) FROM slip_gaji")->fetchColumn();

// Total email sent
$totalEmailSent = $db->query("SELECT COUNT(*) FROM slip_gaji WHERE email_sent = 1")->fetchColumn();

// Total gaji bulan ini (from slip_detail)
$bulan = date('n');
$tahun = date('Y');
$stmt = $db->prepare("
    SELECT COALESCE(SUM(CASE WHEN kg.tipe = 'pendapatan' THEN sd.jumlah ELSE 0 END), 0) -
           COALESCE(SUM(CASE WHEN kg.tipe = 'potongan' THEN sd.jumlah ELSE 0 END), 0) as total_gaji
    FROM slip_detail sd
    JOIN komponen_gaji kg ON sd.komponen_id = kg.id
    JOIN slip_gaji s ON sd.slip_gaji_id = s.id
    WHERE s.bulan = ? AND s.tahun = ?
");
$stmt->execute([$bulan, $tahun]);
$totalGaji = $stmt->fetchColumn();

// Recent slips
$stmt = $db->prepare("
    SELECT s.id, s.bulan, s.tahun, s.email_sent, k.nama, k.nik, k.jabatan
    FROM slip_gaji s
    JOIN karyawan k ON s.karyawan_id = k.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentSlips = $stmt->fetchAll();

// Add gaji_bersih for each recent slip
foreach ($recentSlips as &$slip) {
    $detailStmt = $db->prepare("
        SELECT kg.tipe, SUM(sd.jumlah) as total
        FROM slip_detail sd
        JOIN komponen_gaji kg ON sd.komponen_id = kg.id
        WHERE sd.slip_gaji_id = ?
        GROUP BY kg.tipe
    ");
    $detailStmt->execute([$slip['id']]);
    $totals = $detailStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $slip['gaji_bersih'] = floatval($totals['pendapatan'] ?? 0) - floatval($totals['potongan'] ?? 0);
}

echo json_encode([
    'total_karyawan' => intval($totalKaryawan),
    'total_slip' => intval($totalSlip),
    'total_email_sent' => intval($totalEmailSent),
    'total_gaji' => floatval($totalGaji),
    'bulan' => intval($bulan),
    'tahun' => intval($tahun),
    'recent_slips' => $recentSlips
]);
