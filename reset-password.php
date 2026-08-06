<?php
/**
 * reset-password.php
 * Bước 2 — Xác thực token & cho phép đặt mật khẩu mới.
 *
 * Đặt file: FROMSHOPWHERE/reset-password.php
 */
require_once __DIR__ . '/config.php';
startSession();
if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$token   = trim($_GET['token'] ?? '');
$msg     = '';
$msgType = '';
$valid   = false;   // token còn hiệu lực?
$done    = false;   // đã đổi mật khẩu thành công?

/* ── Hàm tiện ích ─────────────────────────────────────────────────────── */


/* ── Kiểm tra token ──────────────────────────────────────────────────── */
if (!$token) {
    $msg     = 'Link không hợp lệ. Vui lòng yêu cầu đặt lại mật khẩu lại.';
    $msgType = 'error';
} else {
    try {
        $stmt = db()->prepare(
            "SELECT * FROM password_resets
              WHERE token = :t
                AND used = 0
                AND expires_at > NOW()
              LIMIT 1"
        );
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch();

        if ($row) {
            $valid = true;
        } else {
            // Kiểm tra xem token đã dùng hay hết hạn
            $check = db()->prepare("SELECT used, expires_at FROM password_resets WHERE token = :t LIMIT 1");
            $check->execute([':t' => $token]);
            $old = $check->fetch();

            if ($old && $old['used']) {
                $msg = 'Link này đã được sử dụng. Vui lòng yêu cầu link mới.';
            } elseif ($old) {
                $msg = 'Link đã hết hạn. Vui lòng yêu cầu đặt lại mật khẩu lại.';
            } else {
                $msg = 'Link không hợp lệ hoặc đã bị xoá.';
            }
            $msgType = 'error';
        }
    } catch (Exception $e) {
        error_log('[ResetPassword] DB error (check token): ' . $e->getMessage());
        $msg     = 'Lỗi hệ thống. Vui lòng thử lại sau.';
        $msgType = 'error';
    }
}

/* ── Xử lý POST ──────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    csrfCheck();
    $newPass  = $_POST['password']  ?? '';
    $newPass2 = $_POST['password2'] ?? '';
    $postToken = trim($_POST['token'] ?? '');

    if ($postToken !== $token) {
        $msg     = 'Yêu cầu không hợp lệ.';
        $msgType = 'error';
        $valid   = false;
    } elseif (strlen($newPass) < 8) {
        $msg     = 'Mật khẩu phải có ít nhất 8 ký tự.';
        $msgType = 'error';
    } elseif ($newPass !== $newPass2) {
        $msg     = 'Mật khẩu nhập lại không khớp.';
        $msgType = 'error';
    } else {
        try {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);

            // Cập nhật mật khẩu người dùng
            $upd = db()->prepare("UPDATE users SET mat_khau = :p WHERE email = :e");
            $upd->execute([':p' => $hash, ':e' => $row['email']]);

            // Đánh dấu token đã dùng
            $mark = db()->prepare("UPDATE password_resets SET used = 1 WHERE token = :t");
            $mark->execute([':t' => $token]);

            $done    = true;
            $valid   = false;
            $msg     = 'Mật khẩu đã được đặt lại thành công! Bạn có thể đăng nhập ngay bây giờ.';
            $msgType = 'success';
        } catch (Exception $e) {
            error_log('[ResetPassword] DB error (update): ' . $e->getMessage());
            $msg     = 'Lỗi hệ thống khi lưu mật khẩu. Vui lòng thử lại.';
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
  <title>Đặt lại mật khẩu — FROMSHOPWHERE</title>
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

    <h2 style="text-align:center;margin:0 0 6px;font-size:20px">Đặt lại mật khẩu</h2>
    <p style="text-align:center;color:var(--text-muted,#888);font-size:13px;margin:0 0 24px">
      Nhập mật khẩu mới cho tài khoản của bạn.
    </p>

    <?php if ($msg): ?>
      <?php $alertCls = $msgType === 'success' ? 'auth-alert-ok' : 'auth-alert-err';
            $ic = $msgType === 'success' ? '✅' : '⚠'; ?>
      <div class="auth-alert <?= $alertCls ?>">
        <?= $ic ?> <?= e($msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($valid): ?>
    <!-- Form đặt mật khẩu mới -->
    <form method="POST" autocomplete="off" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">

      <div class="form-group">
        <label class="form-label">Mật khẩu mới</label>
        <input class="form-input" type="password" name="password" required
               placeholder="Tối thiểu 8 ký tự"
               autocomplete="new-password">
        <span style="font-size:11px;color:var(--text-muted,#aaa);margin-top:4px;display:block">
          Tối thiểu 8 ký tự.
        </span>
      </div>

      <div class="form-group">
        <label class="form-label">Nhập lại mật khẩu mới</label>
        <input class="form-input" type="password" name="password2" required
               placeholder="••••••••"
               autocomplete="new-password">
      </div>

      <button type="submit" class="btn-submit">Lưu mật khẩu mới →</button>
    </form>

    <?php elseif ($done): ?>
    <!-- Thành công → nút đăng nhập -->
    <div style="text-align:center;margin-top:8px">
      <a href="login.php" class="btn-submit"
         style="display:inline-block;text-decoration:none;padding:12px 32px">
        Đăng nhập ngay →
      </a>
    </div>

    <?php else: ?>
    <!-- Token không hợp lệ / hết hạn → nút yêu cầu lại -->
    <div style="text-align:center;margin-top:8px">
      <a href="forgot-password.php" class="btn-submit"
         style="display:inline-block;text-decoration:none;padding:12px 32px">
        Yêu cầu link mới →
      </a>
    </div>
    <?php endif; ?>

    <p style="text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted,#888)">
      <a href="login.php" style="color:var(--teal-700)">← Quay lại đăng nhập</a>
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
