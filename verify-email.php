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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Xác nhận email — FROMSHOPWHERE</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="auth-wrap">
  <div class="auth-card" style="text-align:center">
    <div class="auth-logo" style="margin-bottom:16px">
      <img src="images/logo.png" alt="FROMSHOPWHERE" style="height:64px;width:auto">
    </div>
    <?php
      $bg = $msgType === 'success' ? '#D1FAE5' : '#FEE2E2';
      $cl = $msgType === 'success' ? '#065F46' : '#991B1B';
      $ic = $msgType === 'success' ? '✅' : '⚠';
    ?>
    <div style="background:<?= $bg ?>;color:<?= $cl ?>;padding:14px 18px;border-radius:8px;
                font-size:14px;margin-bottom:20px;line-height:1.5">
      <?= $ic ?> <?= e($msg) ?>
    </div>
    <p style="margin:0 0 12px;font-size:13px;color:var(--text-muted,#888)">
      <?php if ($ok): ?>
        Tài khoản của bạn đã được kích hoạt.
      <?php else: ?>
        Cần link mới?
        <a href="resend-verification.php" style="color:var(--green-600,#0A8A4C);font-weight:600">Gửi lại email xác nhận</a>
      <?php endif; ?>
    </p>
    <a href="login.php" class="btn-submit" style="display:inline-block;text-decoration:none;margin-top:8px">
      Đăng nhập →
    </a>
  </div>
</div>

<script src="shared.js"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{restoreTheme();updateCartBadge();syncCartPanel();})</script>
</body>
</html>
