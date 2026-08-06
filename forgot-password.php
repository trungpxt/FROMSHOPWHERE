<?php
/**
 * forgot-password.php
 * Bước 1 — Người dùng nhập email, hệ thống gửi link đặt lại mật khẩu.
 *
 * Yêu cầu:  composer require phpmailer/phpmailer
 * Đặt file: FROMSHOPWHERE/forgot-password.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';   // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

startSession();
if (isLoggedIn()) redirect(SITE_URL . '/index.php');

/* ── Cấu hình Gmail SMTP ─────────────────────────────────────────────── */
if (!defined('MAIL_FROM')) {
    define('MAIL_FROM',     '');
    define('MAIL_FROM_NAME','FROMSHOPWHERE');
    define('MAIL_PASSWORD', '');
    define('MAIL_HOST',     'smtp.gmail.com');
    define('MAIL_PORT',      587);
}
if (!defined('TOKEN_EXPIRE_MINUTES')) {
    define('TOKEN_EXPIRE_MINUTES', 30);
}

/* ── Gửi email qua PHPMailer ─────────────────────────────────────────── */
function sendResetEmail(string $toEmail, string $toName, string $token): bool
{
    $resetLink = SITE_URL . '/reset-password.php?token=' . urlencode($token);

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;
    try {
        // Server
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

        // Người gửi / nhận
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Nội dung
        $mail->isHTML(true);
        $mail->Subject = '[FROMSHOPWHERE] Đặt lại mật khẩu của bạn';
        $mail->Body    = buildEmailHtml($toName, $resetLink);
        $mail->AltBody = "Xin chào $toName,\n\n"
                       . "Nhấn vào link sau để đặt lại mật khẩu (có hiệu lực trong " . TOKEN_EXPIRE_MINUTES . " phút):\n"
                       . "$resetLink\n\n"
                       . "Nếu bạn không yêu cầu, hãy bỏ qua email này.\n\n"
                       . "FROMSHOPWHERE";

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[ForgotPassword] PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/* ── HTML email ──────────────────────────────────────────────────────── */
function buildEmailHtml(string $name, string $link): string
{
    $expire = TOKEN_EXPIRE_MINUTES;
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png"><meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 0">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
        <!-- Header -->
        <tr>
          <td style="background:#3B2FA0;padding:28px 40px;text-align:center">
            <h1 style="margin:0;color:#fff;font-size:22px;letter-spacing:1px">FROMSHOPWHERE</h1>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:36px 40px 24px">
            <h2 style="margin:0 0 16px;font-size:18px;color:#111">Đặt lại mật khẩu</h2>
            <p style="margin:0 0 12px;color:#444;line-height:1.6">Xin chào <strong>{$name}</strong>,</p>
            <p style="margin:0 0 24px;color:#444;line-height:1.6">
              Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
              Nhấn nút bên dưới để tiếp tục — link có hiệu lực trong
              <strong>{$expire} phút</strong>.
            </p>
            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px">
              <tr>
                <td style="background:#3B2FA0;border-radius:8px">
                  <a href="{$link}"
                     style="display:inline-block;padding:14px 36px;color:#fff;font-size:15px;
                            font-weight:700;text-decoration:none;letter-spacing:.5px">
                    Đặt lại mật khẩu
                  </a>
                </td>
              </tr>
            </table>
            <p style="margin:0 0 8px;color:#888;font-size:12px;line-height:1.5">
              Hoặc sao chép đường dẫn này vào trình duyệt:
            </p>
            <p style="margin:0 0 24px;font-size:11px;word-break:break-all">
              <a href="{$link}" style="color:#3B2FA0">{$link}</a>
            </p>
            <p style="margin:0;color:#aaa;font-size:12px;line-height:1.5">
              Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.
              Tài khoản của bạn vẫn an toàn.
            </p>
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;padding:16px 40px;text-align:center;border-top:1px solid #e5e7eb">
            <p style="margin:0;color:#aaa;font-size:11px">
              © {$year} FROMSHOPWHERE · Email này được gửi tự động, vui lòng không trả lời.
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

/* ── Xử lý POST ──────────────────────────────────────────────────────── */
$msg     = '';
$msgType = '';   // 'success' | 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg     = 'Vui lòng nhập địa chỉ email hợp lệ.';
        $msgType = 'error';
    } else {
        try {
            // Kiểm tra email tồn tại
            $stmt = db()->prepare("SELECT id, ho_ten FROM users WHERE email = :e LIMIT 1");
            $stmt->execute([':e' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Tạo token ngẫu nhiên 64 ký tự
                $token   = bin2hex(random_bytes(32));
                $expires = date(
                  'Y-m-d H:i:s',
                  time() + (TOKEN_EXPIRE_MINUTES * 60)
              );

                // Vô hiệu hoá token cũ của email này (chưa used)
                $del = db()->prepare(
                    "UPDATE password_resets SET used = 1 WHERE email = :e AND used = 0"
                );
                $del->execute([':e' => $email]);

                // Lưu token mới
                $ins = db()->prepare(
                    "INSERT INTO password_resets (email, token, expires_at) VALUES (:e, :t, :x)"
                );
                $ins->execute([':e' => $email, ':t' => $token, ':x' => $expires]);

                // Gửi email
                $sent = sendResetEmail($email, $user['ho_ten'], $token);
                if (!$sent) {
                    $msg     = 'Không thể gửi email. Vui lòng thử lại sau.';
                    $msgType = 'error';
                }
            }

            // Luôn hiển thị thông báo thành công (tránh lộ email tồn tại)
            if ($msgType !== 'error') {
                $msg     = 'Nếu email này tồn tại trong hệ thống, chúng tôi đã gửi link đặt lại mật khẩu. Kiểm tra hộp thư (kể cả Spam).';
                $msgType = 'success';
            }
        } catch (MailException $e) {
          error_log('forgot-password.php mail error: ' . $mail->ErrorInfo);
          // Không die() lộ chi tiết SMTP — vẫn hiển thị thông báo chung như trên
          // để không lộ email nào tồn tại trong hệ thống.
          $msg     = 'Nếu email này tồn tại trong hệ thống, chúng tôi đã gửi link đặt lại mật khẩu. Kiểm tra hộp thư (kể cả Spam).';
          $msgType = 'success';
      }
    }
}

$currentPage = '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Quên mật khẩu — FROMSHOPWHERE</title>
  <meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-logo" style="text-align:center;margin-bottom:16px">
      <img src="images/ui/logo.png" alt="FROMSHOPWHERE" class="logo-img-light" style="height:64px;width:auto">
      <img src="images/ui/logo-dark.png" alt="FROMSHOPWHERE" class="logo-img-dark" style="height:64px;width:auto">
    </div>

    <h2 style="text-align:center;margin:0 0 6px;font-size:20px">Quên mật khẩu?</h2>
    <p style="text-align:center;color:var(--text-muted,#888);font-size:13px;margin:0 0 24px">
      Nhập email đăng ký — chúng tôi sẽ gửi link đặt lại mật khẩu.
    </p>

    <?php if ($msg): ?>
      <?php $alertCls = $msgType === 'success' ? 'auth-alert-ok' : 'auth-alert-err';
            $ic = $msgType === 'success' ? '✅' : '⚠'; ?>
      <div class="auth-alert <?= $alertCls ?>">
        <?= $ic ?> <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if ($msgType !== 'success'): ?>
    <form method="POST" autocomplete="on" novalidate>
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label">Địa chỉ Email</label>
        <input class="form-input" type="email" name="email" required
               placeholder="email@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <button type="submit" class="btn-submit">Gửi link đặt lại mật khẩu →</button>
    </form>
    <?php endif; ?>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted,#888)">
      Nhớ ra mật khẩu rồi?
      <a href="login.php" style="color:var(--teal-700);font-weight:600">Đăng nhập</a>
    </p>

  </div>
</div>

<footer>
  <div class="footer-inner">
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div><div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div><div class="pay-badge">ZALO</div>
      </div>
    </div>
  </div>
</footer>
<script src="assets/js/shared.js"></script>
<script src="assets/js/page-init.js"></script>
</body>
</html>
