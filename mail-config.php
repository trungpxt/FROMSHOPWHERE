<?php
/**
 * mail-config.php — Cấu hình email dùng chung toàn site
 * Include file này trước khi gửi bất kỳ email nào
 */
if (!defined('MAIL_FROM')) {
    // Không tìm thấy config.php đã nạp trước đó -> đây là dấu hiệu thiếu require_once config.php
    // ở nơi gọi file này. Đặt giá trị rỗng để lỗi hiện rõ ràng thay vì âm thầm dùng sai cấu hình.
    define('MAIL_FROM',     '');
    define('MAIL_FROM_NAME','FROMSHOPWHERE');
    define('MAIL_PASSWORD', '');
    define('MAIL_HOST',     'smtp.gmail.com');
    define('MAIL_PORT',      587);
}

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Tạo PHPMailer đã cấu hình SMTP sẵn
 */
if (!function_exists('createMailer')) {
function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_FROM;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 8;   // giây — tránh treo request nếu SMTP chậm/lỗi
    $mail->SMTPKeepAlive = false;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    return $mail;
}
}
