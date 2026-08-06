<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/email_verify.php';
startSession();
if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$msg     = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!$email || !isValidEmailPhp($email)) {
        $msg     = 'Vui lòng nhập email hợp lệ.';
        $msgType = 'error';
    } else {
        try {
            $stmt = db()->prepare(
                "SELECT id, ho_ten, email_verified, vai_tro FROM users WHERE LOWER(email) = :e LIMIT 1"
            );
            $stmt->execute([':e' => $email]);
            $user = $stmt->fetch();

            if ($user && !empty($user['email_verified'])) {
                $msg     = 'Email này đã được xác nhận. Bạn có thể đăng nhập.';
                $msgType = 'success';
            } elseif ($user && ($user['vai_tro'] ?? '') === 'admin') {
                $msg     = 'Tài khoản admin không cần xác nhận email.';
                $msgType = 'success';
            } elseif ($user) {
                $sent = sendUserVerificationEmail(
                    (int) $user['id'],
                    $email,
                    $user['ho_ten']
                );
                if ($sent) {
                    $msg     = 'Đã gửi lại email xác nhận. Kiểm tra hộp thư Gmail (kể cả Spam).';
                    $msgType = 'success';
                } else {
                    $msg     = 'Không gửi được email. Thử lại sau vài phút.';
                    $msgType = 'error';
                }
            } else {
                $msg     = 'Nếu email đã đăng ký và chưa xác nhận, chúng tôi đã gửi link. Kiểm tra hộp thư.';
                $msgType = 'success';
            }
        } catch (Exception $e) {
            error_log('[ResendVerify] ' . $e->getMessage());
            $msg     = 'Lỗi hệ thống. Vui lòng thử lại.';
            $msgType = 'error';
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
  <title>Gửi lại xác nhận email — FROMSHOPWHERE</title>
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
    <h2 style="text-align:center;margin:0 0 6px;font-size:20px">Gửi lại email xác nhận</h2>
    <p style="text-align:center;color:var(--text-muted,#888);font-size:13px;margin:0 0 24px">
      Nhập email đăng ký để nhận link kích hoạt tài khoản qua Gmail.
    </p>

    <?php if ($msg): ?>
      <?php $alertCls = $msgType === 'success' ? 'auth-alert-ok' : 'auth-alert-err';
            $ic = $msgType === 'success' ? '✅' : '⚠'; ?>
      <div class="auth-alert <?= $alertCls ?>">
        <?= $ic ?> <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($msgType !== 'success'): ?>
    <form method="POST" autocomplete="on">
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" required
               placeholder="email@gmail.com"
               value="<?= e($_POST['email'] ?? $_GET['email'] ?? '') ?>">
      </div>
      <button type="submit" class="btn-submit">Gửi lại link xác nhận →</button>
    </form>
    <?php endif; ?>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted,#888)">
      <a href="login.php" style="color:var(--teal-700);font-weight:600">← Quay lại đăng nhập</a>
    </p>
  </div>
</div>

<script src="assets/js/shared.js"></script>
<script src="assets/js/page-init.js"></script>
</body>
</html>
