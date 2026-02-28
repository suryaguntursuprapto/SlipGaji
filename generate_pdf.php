<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

$db = getDB();

$id = $_GET['id'] ?? null;
if (!$id) {
    die('ID slip gaji tidak ditemukan');
}

// Get slip data
$stmt = $db->prepare("
    SELECT s.*, k.nik, k.nama, k.email, k.jabatan, k.departemen, k.no_rekening, k.bank
    FROM slip_gaji s
    JOIN karyawan k ON s.karyawan_id = k.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$slip = $stmt->fetch();

if (!$slip) {
    die('Slip gaji tidak ditemukan');
}

// Get slip details (dynamic components)
$detailStmt = $db->prepare("
    SELECT sd.jumlah, kg.nama as komponen_nama, kg.tipe,
           kk.nama as kategori_nama
    FROM slip_detail sd
    JOIN komponen_gaji kg ON sd.komponen_id = kg.id
    LEFT JOIN kategori_komponen kk ON kg.kategori_id = kk.id
    WHERE sd.slip_gaji_id = ?
    ORDER BY kg.tipe ASC, kg.urutan ASC
");
$detailStmt->execute([$id]);
$details = $detailStmt->fetchAll();

// Calculate totals
$totalPendapatan = 0;
$totalPotongan = 0;
$pendapatanItems = [];
$potonganItems = [];

foreach ($details as $d) {
    if ($d['tipe'] === 'pendapatan') {
        $totalPendapatan += $d['jumlah'];
        $pendapatanItems[] = $d;
    } else {
        $totalPotongan += $d['jumlah'];
        $potonganItems[] = $d;
    }
}
$gajiBersih = $totalPendapatan - $totalPotongan;

// Group items by category
function groupByCategory($items) {
    $groups = [];
    foreach ($items as $item) {
        $cat = $item['kategori_nama'] ?: 'Lainnya';
        if (!isset($groups[$cat])) $groups[$cat] = [];
        $groups[$cat][] = $item;
    }
    return $groups;
}
$pendapatanGroups = groupByCategory($pendapatanItems);
$potonganGroups = groupByCategory($potonganItems);

// Get company settings
$namaPerusahaan = getSetting('nama_perusahaan') ?: 'Spicy Lips x Bergamot Koffie';
$alamatPerusahaan = getSetting('alamat_perusahaan') ?: '';

// Create PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetCreator('Spicy Lips x Bergamot Koffie');
$pdf->SetAuthor($namaPerusahaan);
$pdf->SetTitle('Slip Gaji - ' . $slip['nama'] . ' - ' . getMonthName($slip['bulan']) . ' ' . $slip['tahun']);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20, 20, 20);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// ============ COLORS ============
$darkRed = [139, 30, 30];
$warmRed = [192, 57, 43];
$green = [39, 174, 96];
$darkGreen = [30, 130, 76];
$darkText = [44, 44, 44];
$grayText = [120, 120, 120];
$lightBg = [245, 252, 247];
$whiteBg = [255, 255, 255];
$tableHeaderBg = [45, 30, 30];
$tableHeaderText = [255, 255, 255];
$tableAltBg = [248, 253, 249];
$greenAccent = [39, 174, 96];
$borderColor = [180, 210, 185];

$pageW = 170; // usable width (A4 - margins)

// ============ DECORATIVE TOP BORDER ============
$pdf->SetFillColor($warmRed[0], $warmRed[1], $warmRed[2]);
$pdf->Rect(0, 0, 210, 3.5, 'F');
$pdf->SetFillColor($green[0], $green[1], $green[2]);
$pdf->Rect(0, 3.5, 210, 1, 'F');

// ============ HEADER SECTION ============
$logoPath = __DIR__ . '/assets/img/logo.png';
$logoW = 80;
$logoH = 20;
$headerY = 5;

// Logo - centered
if (file_exists($logoPath)) {
    $logoX = (210 - $logoW) / 2;
    $pdf->Image($logoPath, $logoX, $headerY, $logoW, $logoH, 'PNG');
}

// Address centered below logo
$pdf->SetY($headerY + $logoH + 2);
if ($alamatPerusahaan) {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor($grayText[0], $grayText[1], $grayText[2]);
    $pdf->Cell(0, 4, $alamatPerusahaan, 0, 1, 'C');
}

$pdf->Ln(1);

// Elegant double divider
$y = $pdf->GetY();
$pdf->SetDrawColor($green[0], $green[1], $green[2]);
$pdf->SetLineWidth(0.8);
$pdf->Line(20, $y, 190, $y);
$pdf->SetDrawColor($warmRed[0], $warmRed[1], $warmRed[2]);
$pdf->SetLineWidth(0.25);
$pdf->Line(20, $y + 1.8, 190, $y + 1.8);
$pdf->Ln(3);

// ============ TITLE ============
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor($darkRed[0], $darkRed[1], $darkRed[2]);
$pdf->Cell(0, 10, 'SLIP GAJI', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor($grayText[0], $grayText[1], $grayText[2]);
$pdf->Cell(0, 5, 'PAYSLIP', 0, 1, 'C');
$pdf->Ln(4);

// ============ PERIOD & NIP BOX ============
$boxY = $pdf->GetY();
$pdf->SetFillColor($lightBg[0], $lightBg[1], $lightBg[2]);
$pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);
$pdf->RoundedRect(20, $boxY, $pageW, 18, 3, '1111', 'DF');

$pdf->SetY($boxY + 3);
$pdf->SetX(28);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor($darkText[0], $darkText[1], $darkText[2]);
$pdf->Cell(30, 6, 'Periode', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(55, 6, ': ' . getMonthName($slip['bulan']) . ' ' . $slip['tahun'], 0, 0);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, 'Bank', 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(55, 6, ': ' . ($slip['bank'] ?: '-'), 0, 1);

$pdf->SetX(28);


$pdf->SetY($boxY + 22);

// ============ MAIN TABLE ============
$colLabel = 90;
$colValue = 80;

// Table Header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor($tableHeaderText[0], $tableHeaderText[1], $tableHeaderText[2]);
$pdf->SetFillColor($tableHeaderBg[0], $tableHeaderBg[1], $tableHeaderBg[2]);
$pdf->SetDrawColor($tableHeaderBg[0], $tableHeaderBg[1], $tableHeaderBg[2]);

$pdf->Cell($colLabel, 9, '   Keterangan', 1, 0, 'L', true);
$pdf->Cell($colValue, 9, '', 1, 1, 'R', true);

$pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);

// Info rows: NIP, Nama, Jabatan
$infoRows = [
    ['NIP', $slip['nik']],
    ['Nama', $slip['nama']],
    ['Jabatan', $slip['jabatan'] ?: '-'],
];

$alt = false;
foreach ($infoRows as $row) {
    $pdf->SetFillColor($alt ? $tableAltBg[0] : $whiteBg[0], $alt ? $tableAltBg[1] : $whiteBg[1], $alt ? $tableAltBg[2] : $whiteBg[2]);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor($darkText[0], $darkText[1], $darkText[2]);
    $pdf->Cell($colLabel, 8, '   ' . $row[0], 'LTB', 0, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($colValue, 8, $row[1] . '   ', 'RTB', 1, 'R', true);
    $alt = !$alt;
}

// Divider after info rows
$pdf->SetFillColor($green[0], $green[1], $green[2]);
$pdf->Cell($colLabel + $colValue, 1, '', 0, 1, '', true);

// ============ PENDAPATAN ITEMS ============
// Section header: Pendapatan
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor($darkGreen[0], $darkGreen[1], $darkGreen[2]);
$pdf->SetFillColor(235, 250, 238);
$pdf->Cell($colLabel, 7, '   PENDAPATAN', 'LTB', 0, 'L', true);
$pdf->Cell($colValue, 7, 'Jumlah (IDR)   ', 'RTB', 1, 'R', true);

$alt = false;
foreach ($pendapatanItems as $item) {
    if (floatval($item['jumlah']) <= 0) continue;
    $pdf->SetFillColor($alt ? $tableAltBg[0] : $whiteBg[0], $alt ? $tableAltBg[1] : $whiteBg[1], $alt ? $tableAltBg[2] : $whiteBg[2]);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor($darkText[0], $darkText[1], $darkText[2]);
    $pdf->Cell($colLabel, 8, '      ' . $item['komponen_nama'], 'LTB', 0, 'L', true);
    $pdf->Cell($colValue, 8, formatRupiah($item['jumlah']) . '   ', 'RTB', 1, 'R', true);
    $alt = !$alt;
}

// Total Pendapatan row
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(34, 139, 34); // green
$pdf->SetFillColor(240, 255, 240);
$pdf->Cell($colLabel, 8, '   Total Pendapatan', 'LTB', 0, 'L', true);
$pdf->Cell($colValue, 8, formatRupiah($totalPendapatan) . '   ', 'RTB', 1, 'R', true);

// Green divider after Total Pendapatan
$pdf->SetFillColor($green[0], $green[1], $green[2]);
$pdf->Cell($colLabel + $colValue, 1, '', 0, 1, '', true);


// ============ POTONGAN ITEMS ============
// Section header: Potongan
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor($warmRed[0], $warmRed[1], $warmRed[2]);
$pdf->SetFillColor(255, 240, 240);
$pdf->Cell($colLabel, 7, '   POTONGAN', 'LTB', 0, 'L', true);
$pdf->Cell($colValue, 7, 'Jumlah (IDR)   ', 'RTB', 1, 'R', true);

$alt = false;
foreach ($potonganItems as $item) {
    if (floatval($item['jumlah']) <= 0) continue;
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor($warmRed[0], $warmRed[1], $warmRed[2]);
    $pdf->SetFillColor($alt ? $tableAltBg[0] : $whiteBg[0], $alt ? $tableAltBg[1] : $whiteBg[1], $alt ? $tableAltBg[2] : $whiteBg[2]);
    $pdf->Cell($colLabel, 8, '      ' . $item['komponen_nama'], 'LTB', 0, 'L', true);
    $pdf->Cell($colValue, 8, formatRupiah($item['jumlah']) . '   ', 'RTB', 1, 'R', true);
    $alt = !$alt;
}

// Total Potongan row
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor($warmRed[0], $warmRed[1], $warmRed[2]);
$pdf->SetFillColor(255, 240, 240);
$pdf->Cell($colLabel, 8, '   Total Potongan', 'LTB', 0, 'L', true);
$pdf->Cell($colValue, 8, formatRupiah($totalPotongan) . '   ', 'RTB', 1, 'R', true);

// Green highlight separator
$pdf->SetFillColor($green[0], $green[1], $green[2]);
$pdf->Cell($colLabel + $colValue, 1.5, '', 0, 1, '', true);

// ============ GAJI BERSIH (Take Home Pay) ============
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor($whiteBg[0], $whiteBg[1], $whiteBg[2]);
$pdf->SetFillColor($warmRed[0], $warmRed[1], $warmRed[2]);
$pdf->Cell($colLabel, 10, '   Take Home Pay', 1, 0, 'L', true);
$pdf->Cell($colValue, 10, formatRupiah($gajiBersih) . '   ', 1, 1, 'R', true);

// ============ KETERANGAN ============
if ($slip['keterangan']) {
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetTextColor($grayText[0], $grayText[1], $grayText[2]);
    $pdf->MultiCell(0, 5, 'Catatan: ' . $slip['keterangan'], 0, 'L');
}

// ============ SIGNATURE SECTION ============
$pdf->Ln(8);
$sigSpacer = $pageW * 0.6;
$sigW = $pageW * 0.4;
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor($darkText[0], $darkText[1], $darkText[2]);
$pdf->Cell($sigSpacer, 6, '', 0, 0);
$pdf->Cell($sigW, 6, 'Mengetahui,', 0, 1, 'C');

$pdf->Ln(15);

$pdf->Cell($sigSpacer, 6, '', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell($sigW, 6, 'Maritza Raras', 0, 1, 'C');

$pdf->Cell($sigSpacer, 5, '', 0, 0);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor($grayText[0], $grayText[1], $grayText[2]);
$pdf->Cell($sigW, 5, 'Finance Supervisor', 0, 1, 'C');

// ============ DECORATIVE BOTTOM ============
$pdf->Ln(6);
$y = $pdf->GetY();
$pdf->SetDrawColor($green[0], $green[1], $green[2]);
$pdf->SetLineWidth(0.3);
$pdf->Line(20, $y, 190, $y);
$pdf->Ln(3);

// WIB timezone (UTC+7)
$wibTime = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(170, 170, 170);
$pdf->Cell(0, 4, 'This is a computer-generated document. | ' . $namaPerusahaan, 0, 1, 'C');
$pdf->Cell(0, 4, 'Generated on: ' . $wibTime->format('d M Y H:i') . ' WIB', 0, 1, 'C');

// ============ DECORATIVE BOTTOM BORDER ============
$pdf->SetFillColor($green[0], $green[1], $green[2]);
$pdf->Rect(0, 292, 210, 1.5, 'F');
$pdf->SetFillColor($warmRed[0], $warmRed[1], $warmRed[2]);
$pdf->Rect(0, 293.5, 210, 3.5, 'F');

// Output based on mode
$mode = $_GET['mode'] ?? 'download';
$filename = 'SlipGaji_' . str_replace(' ', '_', $slip['nama']) . '_' . getMonthName($slip['bulan']) . '_' . $slip['tahun'] . '.pdf';

// Handle file mode first (used when included by send_email.php)
// Don't touch output buffers or headers in this case
if ($mode === 'file') {
    $tempDir = __DIR__ . '/temp';
    if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
    $tempFile = $tempDir . '/' . $filename;
    $pdf->Output($tempFile, 'F');
    echo $tempFile;
} else {
    // Clean any previous output for direct browser modes
    if (ob_get_length()) ob_end_clean();

    if ($mode === 'inline') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        $pdf->Output($filename, 'I');
    } else {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        $pdf->Output($filename, 'D');
    }
}
