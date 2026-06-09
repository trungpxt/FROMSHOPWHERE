<?php
/**
 * Gửi email qua Gmail SMTP (PHPMailer).
 */
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

define('MAIL_FROM',      'iddd83715@gmail.com');
define('MAIL_FROM_NAME', 'FROMSHOPWHERE');
define('MAIL_PASSWORD',  'cnyibljkifbpwcds');
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('VERIFY_TOKEN_HOURS', 24);

function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_FROM;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = 0;
    return $mail;
}

function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
{
    $link = SITE_URL . '/verify-email.php?token=' . urlencode($token);
    $hours = VERIFY_TOKEN_HOURS;

    $body = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 0">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
        <tr>
          <td style="background:#0A8A4C;padding:28px 40px;text-align:center">
            <h1 style="margin:0;color:#fff;font-size:22px">FROMSHOPWHERE</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 40px 24px">
            <h2 style="margin:0 0 16px;font-size:18px;color:#111">Xác nhận tài khoản</h2>
            <p style="margin:0 0 12px;color:#444;line-height:1.6">Xin chào <strong>{$toName}</strong>,</p>
            <p style="margin:0 0 24px;color:#444;line-height:1.6">
              Cảm ơn bạn đã đăng ký. Nhấn nút bên dưới để kích hoạt tài khoản — link có hiệu lực
              <strong>{$hours} giờ</strong>.
            </p>
            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px">
              <tr>
                <td style="background:#0A8A4C;border-radius:8px">
                  <a href="{$link}"
                     style="display:inline-block;padding:14px 36px;color:#fff;font-size:15px;
                            font-weight:700;text-decoration:none">Xác nhận email</a>
                </td>
              </tr>
            </table>
            <p style="margin:0 0 8px;color:#888;font-size:12px">Hoặc mở link:</p>
            <p style="margin:0;font-size:11px;word-break:break-all">
              <a href="{$link}" style="color:#0A8A4C">{$link}</a>
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f9fafb;padding:16px 40px;text-align:center;border-top:1px solid #e5e7eb">
            <p style="margin:0;color:#aaa;font-size:11px">© 2025 FROMSHOPWHERE</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    try {
        $mail = createMailer();
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = '[FROMSHOPWHERE] Xác nhận đăng ký tài khoản';
        $mail->Body    = $body;
        $mail->AltBody = "Xin chào $toName,\n\nXác nhận tài khoản tại:\n$link\n\n(hết hạn sau $hours giờ)\n\nFROMSHOPWHERE";
        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[VerifyEmail] PHPMailer: ' . $e->getMessage());
        return false;
    }
}
