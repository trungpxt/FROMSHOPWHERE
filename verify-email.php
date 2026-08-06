<?php
require_once __DIR__ . '/config.php';
startSession();

$token   = trim($_GET['token'] ?? '');
$msg     = '';
$msgType = 'error';
$ok      = false;

if (!$token) {
    $msg = 'Link xác nhận không hợp lệ.';
} else {
    try {
        $stmt = db()->prepare(
            "SELECT ev.*, u.ho_ten
               FROM email_verifications ev
               JOIN users u ON u.id = ev.user_id
              WHERE ev.token = :t
                AND ev.used = 0
                AND ev.expires_at > NOW()
              LIMIT 1"
        );
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch();

        if ($row) {
            db()->beginTransaction();
            $up = db()->prepare("UPDATE users SET email_verified = 1 WHERE id = :id");
            $up->execute([':id' => $row['user_id']]);
            $used = db()->prepare(
                "UPDATE email_verifications SET used = 1 WHERE id = :id"
            );
            $used->execute([':id' => $row['id']]);
            db()->commit();
            redirect(SITE_URL . '/login.php?verified=1');
        } else {
            $chk = db()->prepare(
                "SELECT used, expires_at FROM email_verifications WHERE token = :t LIMIT 1"
            );
            $chk->execute([':t' => $token]);
            $old = $chk->fetch();
            if ($old && $old['used']) {
                $msg = 'Link đã được dùng. Hãy đăng nhập hoặc yêu cầu gửi lại email xác nhận.';
            } elseif ($old) {
                $msg = 'Link đã hết hạn. Vui lòng gửi lại email xác nhận.';
            } else {
                $msg = 'Link không hợp lệ.';
            }
        }
    } catch (Exception $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        error_log('[VerifyEmail] ' . $e->getMessage());
        $msg = 'Lỗi hệ thống. Vui lòng thử lại sau.';
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
  <title>Xác nhận email — FROMSHOPWHERE</title>
  <meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="auth-wrap">
  <div class="auth-card" style="text-align:center">
    <div class="auth-logo" style="margin-bottom:16px">
      <img src="images/ui/logo.png" alt="FROMSHOPWHERE" class="logo-img-light" style="height:64px;width:auto">
      <img src="images/ui/logo-dark.png" alt="FROMSHOPWHERE" class="logo-img-dark" style="height:64px;width:auto">
    </div>
    <?php
      $alertCls = $msgType === 'success' ? 'auth-alert-ok' : 'auth-alert-err';
      $ic = $msgType === 'success' ? '✅' : '⚠';
    ?>
    <div class="auth-alert <?= $alertCls ?>">
      <?= $ic ?> <?= e($msg) ?>
    </div>
    <p style="margin:0 0 12px;font-size:13px;color:var(--text-muted,#888)">
      <?php if ($ok): ?>
        Tài khoản của bạn đã được kích hoạt.
      <?php else: ?>
        Cần link mới?
        <a href="resend-verification.php" style="color:var(--teal-700);font-weight:600">Gửi lại email xác nhận</a>
      <?php endif; ?>
    </p>
    <a href="login.php" class="btn-submit" style="display:inline-block;text-decoration:none;margin-top:8px">
      Đăng nhập →
    </a>
  </div>
</div>

<script src="assets/js/shared.js"></script>
<script src="assets/js/page-init.js"></script>
</body>
</html>
