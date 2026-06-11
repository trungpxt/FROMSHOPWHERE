<?php
/**
 * mail-config.php — Cấu hình email dùng chung toàn site
 * Include file này trước khi gửi bất kỳ email nào
 */
if (!defined('MAIL_FROM')) {
    define('MAIL_FROM',     'iddd83715@gmail.com');
    define('MAIL_FROM_NAME','FROMSHOPWHERE');
    define('MAIL_PASSWORD', 'cnyibljkifbpwcds');
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
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    return $mail;
}
