<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
requireAuth();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$db = getDB();
$data = json_decode(file_get_contents('php://input'), true);

// Handle test email
if (!empty($data['test'])) {
    $testEmail = $data['test_email'] ?? '';
    
    if (!$testEmail) {
        echo json_encode(['success' => false, 'message' => 'Email test tidak ditemukan']);
        exit;
    }

    try {
        $mail = createMailer();
        $mail->addAddress($testEmail);
        $mail->Subject = 'Test Email - Slip Gaji System';
        $mail->isHTML(true);
        $mail->Body = getTestEmailBody();
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Email test berhasil terkirim']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengirim email: ' . $mail->ErrorInfo]);
    }
    exit;
}

// Handle slip email
$slipId = $data['slip_id'] ?? null;
if (!$slipId) {
    echo json_encode(['success' => false, 'message' => 'ID slip gaji tidak ditemukan']);
    exit;
}

// Get slip data
$stmt = $db->prepare("
    SELECT s.*, k.nama, k.email, k.jabatan
    FROM slip_gaji s
    JOIN karyawan k ON s.karyawan_id = k.id
    WHERE s.id = ?
");
$stmt->execute([$slipId]);
$slip = $stmt->fetch();

if (!$slip) {
    echo json_encode(['success' => false, 'message' => 'Slip gaji tidak ditemukan']);
    exit;
}

// Generate PDF to temp file using isolated include (closure prevents variable scope conflicts)
$tempPdfPath = (function($id) {
    $_GET['id'] = $id;
    $_GET['mode'] = 'file';
    ob_start();
    include __DIR__ . '/../generate_pdf.php';
    return trim(ob_get_clean());
})($slipId);

if (!$tempPdfPath || !file_exists($tempPdfPath)) {
    echo json_encode(['success' => false, 'message' => 'Gagal generate PDF untuk lampiran email']);
    exit;
}

try {
    $mail = createMailer();
    $mail->addAddress($slip['email'], $slip['nama']);
    
    $monthName = getMonthName($slip['bulan']);
    $year = $slip['tahun'];
    
    $mail->Subject = "Payslip - {$monthName} {$year}";
    $mail->isHTML(true);
    $mail->Body = getPayslipEmailBody($slip['nama'], $monthName, $year);
    $mail->AltBody = getPayslipEmailBodyPlainText($slip['nama'], $monthName, $year);
    
    // Attach PDF
    $pdfFilename = 'Payslip_' . str_replace(' ', '_', $slip['nama']) . '_' . $monthName . '_' . $year . '.pdf';
    $mail->addAttachment($tempPdfPath, $pdfFilename);
    
    $mail->send();
    
    // Update email_sent status
    $updateStmt = $db->prepare("UPDATE slip_gaji SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
    $updateStmt->execute([$slipId]);
    
    // Clean up temp file
    unlink($tempPdfPath);
    
    echo json_encode(['success' => true, 'message' => 'Email berhasil dikirim ke ' . $slip['email']]);
} catch (Exception $e) {
    if (isset($tempPdfPath) && file_exists($tempPdfPath)) unlink($tempPdfPath);
    $errorMsg = $e->getMessage();
    if (isset($mail) && $mail->ErrorInfo) {
        $errorMsg = $mail->ErrorInfo;
    }
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim email: ' . $errorMsg]);
}

// ============ HELPER FUNCTIONS ============

function createMailer() {
    $mail = new PHPMailer(true);
    
    $smtpHost = getSetting('email_smtp_host') ?: 'smtp.gmail.com';
    $smtpPort = getSetting('email_smtp_port') ?: 587;
    $smtpUser = getSetting('email_smtp_user') ?: '';
    $smtpPass = getSetting('email_smtp_pass') ?: '';
    $fromName = getSetting('email_from_name') ?: 'Maritza Raras';
    $companyName = getSetting('nama_perusahaan') ?: 'Spicy Lips x Bergamot Koffie';
    
    if (!$smtpUser || !$smtpPass) {
        throw new Exception('SMTP email belum dikonfigurasi. Silakan set di menu Pengaturan.');
    }

    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = intval($smtpPort);
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($smtpUser, $fromName . ' - ' . $companyName);
    $mail->addReplyTo($smtpUser, $fromName);
    
    // Anti-spam headers
    $mail->XMailer = 'Spicy Lips Payroll System';
    $mail->addCustomHeader('Organization', $companyName);
    $mail->addCustomHeader('X-Priority', '3'); // Normal priority
    $mail->addCustomHeader('Precedence', 'bulk');
    $mail->Priority = 3;
    
    // Embed logo
    $logoPath = __DIR__ . '/../assets/img/logo.png';
    if (file_exists($logoPath)) {
        $mail->addEmbeddedImage($logoPath, 'company_logo', 'logo.png');
    }
    
    return $mail;
}

function getPayslipEmailBody($nama, $month, $year) {
    $fromName = getSetting('email_from_name') ?: 'Maritza Raras';
    $fromTitle = getSetting('email_from_title') ?: 'Finance Supervisor';
    
    // Dynamic greeting based on WIB time
    $wibTime = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $hour = (int) $wibTime->format('H');
    if ($hour >= 5 && $hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour >= 12 && $hour < 18) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin:0; padding:0; font-family: \'Segoe UI\', \'Helvetica Neue\', Arial, sans-serif; background-color: #f0f4f0; -webkit-font-smoothing: antialiased;">
        <table cellpadding="0" cellspacing="0" width="100%" style="background-color: #f0f4f0; padding: 30px 0;">
            <tr>
                <td align="center">
                    <table cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,0.08);">
                        
                        <!-- Green accent top bar -->
                        <tr>
                            <td style="height: 4px; background: linear-gradient(90deg, #27ae60, #2ecc71, #27ae60);"></td>
                        </tr>
                        
                        <!-- Header with logo -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #1a2a1a, #2c3e2c, #1e3320); padding: 36px 32px; text-align: center;">
                                <img src="cid:company_logo" alt="Spicy Lips x Bergamot" style="max-width: 220px; height: auto; margin-bottom: 12px;">
                                <p style="color: #7fad8a; font-size: 12px; margin: 0; letter-spacing: 2px; text-transform: uppercase;">Payroll Notification</p>
                            </td>
                        </tr>
                        
                        <!-- Period badge -->
                        <tr>
                            <td align="center" style="padding: 24px 32px 0;">
                                <table cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9); border-radius: 50px; overflow: hidden;">
                                    <tr>
                                        <td style="padding: 10px 28px; text-align: center;">
                                            <span style="color: #2e7d32; font-size: 13px; font-weight: 600; letter-spacing: 0.5px;">📄 Payslip Period: ' . $month . ' ' . $year . '</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Body content -->
                        <tr>
                            <td style="padding: 28px 40px 12px; color: #2c3e50; line-height: 1.8; font-size: 15px;">
                                <p style="margin: 0 0 20px; font-size: 16px; color: #2c3e50;">Dear <strong>' . htmlspecialchars($nama) . '</strong>,</p>
                                
                                <p style="margin: 0 0 20px; color: #555;">' . $greeting . '.</p>
                                
                                <p style="margin: 0 0 20px; color: #555;">Please find attached the payslip for the period of <strong style="color: #27ae60;">' . $month . ' ' . $year . '</strong>.</p>
                                
                                <p style="margin: 0 0 20px; color: #555;">Kindly review the details and let us know if there are any questions or discrepancies.</p>
                                
                                <p style="margin: 0 0 20px; color: #555;">Thank you for your continued hard work and contribution. 🙏</p>
                            </td>
                        </tr>
                        
                        <!-- Signature -->
                        <tr>
                            <td style="padding: 0 40px 32px;">
                                <table cellpadding="0" cellspacing="0" width="100%" style="border-top: 2px solid #e8f5e9; padding-top: 20px;">
                                    <tr>
                                        <td style="padding-top: 20px;">
                                            <p style="color: #888; font-size: 14px; margin: 0 0 12px;">Best regards,</p>
                                            <p style="font-weight: 700; color: #2c3e50; font-size: 16px; margin: 0 0 4px;">' . htmlspecialchars($fromName) . '</p>
                                            <p style="color: #27ae60; font-size: 13px; font-weight: 500; margin: 0;">' . htmlspecialchars($fromTitle) . '</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background: #f8faf8; padding: 24px 32px; text-align: center; border-top: 1px solid #e8f5e9;">
                                <img src="cid:company_logo" alt="Spicy Lips x Bergamot" style="max-width: 160px; height: auto; margin-bottom: 12px; opacity: 0.7;">
                                <p style="color: #a0b0a0; font-size: 11px; margin: 0; line-height: 1.6;">
                                    This email and any attachments are confidential and intended solely for the recipient.<br>
                                    &copy; ' . date('Y') . ' Spicy Lips x Bergamot Koffie. All rights reserved.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Green accent bottom bar -->
                        <tr>
                            <td style="height: 4px; background: linear-gradient(90deg, #27ae60, #2ecc71, #27ae60);"></td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}

function getPayslipEmailBodyPlainText($nama, $month, $year) {
    $fromName = getSetting('email_from_name') ?: 'Maritza Raras';
    $fromTitle = getSetting('email_from_title') ?: 'Supervisor Accounting';
    
    // Dynamic greeting based on WIB time
    $wibTime = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $hour = (int) $wibTime->format('H');
    if ($hour >= 5 && $hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour >= 12 && $hour < 18) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
    
    return "Dear {$nama},\n\n" .
           "{$greeting}.\n\n" .
           "Please find attached the payslip for the period of {$month} {$year}.\n\n" .
           "Kindly review the details and let us know if there are any questions or discrepancies.\n\n" .
           "Thank you for your continued hard work and contribution.\n\n" .
           "Best regards,\n\n" .
           "{$fromName}\n" .
           "{$fromTitle}";
}

function getTestEmailBody() {
    return '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px;">
        <div style="max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <img src="cid:company_logo" alt="Logo" style="max-width: 200px; margin-bottom: 20px;">
            <h2 style="color: #2c3e50;">✅ Email Test Berhasil!</h2>
            <p style="color: #7f8c8d;">Konfigurasi SMTP email Anda sudah benar. Sistem slip gaji siap mengirim email.</p>
            <p style="color: #95a5a6; font-size: 12px; margin-top: 20px;">Slip Gaji System - Spicy Lips x Bergamot Koffie</p>
        </div>
    </body>
    </html>';
}
