<?php
require_once __DIR__ . '/config.php';
startSession();

// Đã đăng nhập rồi thì về trang chủ
if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error  = '';
$mode   = $_GET['mode'] ?? 'login';
$redirect = $_GET['redirect'] ?? SITE_URL . '/index.php';

/* ══ XỬ LÝ POST ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── ĐĂNG NHẬP ── */
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password']  ?? '';
        if (!$email || !$pass) {
            $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
        } else {
            try {
                $stmt = db()->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
                $stmt->execute([':e' => $email]);
                $user = $stmt->fetch();
                if ($user && password_verify($pass, $user['mat_khau'])) {
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_name']  = $user['ho_ten'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role']  = $user['vai_tro'];
                    redirect($redirect);
                } else {
                    $error = 'Email hoặc mật khẩu không đúng.';
                }
            } catch (Exception $e) {
                $error = 'Lỗi kết nối database. Kiểm tra lại config.php';
            }
        }
    }

    /* ── ĐĂNG KÝ ── */
    if ($action === 'register') {
        $name  = trim($_POST['ho_ten']   ?? '');
        $email = trim($_POST['email']    ?? '');
        $pass  = $_POST['password']      ?? '';
        $pass2 = $_POST['password2']     ?? '';
        if (!$name || !$email || !$pass) {
            $error = 'Vui lòng điền đầy đủ thông tin.';
        } elseif (strlen($pass) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
        } elseif ($pass !== $pass2) {
            $error = 'Mật khẩu nhập lại không khớp.';
        } else {
            try {
                $chk = db()->prepare("SELECT id FROM users WHERE email=:e");
                $chk->execute([':e' => $email]);
                if ($chk->fetch()) {
                    $error = 'Email này đã được đăng ký.';
                } else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $ins  = db()->prepare("INSERT INTO users (ho_ten,email,mat_khau) VALUES (:n,:e,:p)");
                    $ins->execute([':n'=>$name, ':e'=>$email, ':p'=>$hash]);
                    $_SESSION['user_id']    = db()->lastInsertId();
                    $_SESSION['user_name']  = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role']  = 'khach_hang';
                    redirect(SITE_URL . '/index.php');
                }
            } catch (Exception $e) {
                $error = 'Lỗi kết nối database. Kiểm tra lại config.php';
            }
        }
        $mode = 'register';
    }
}
$currentPage = '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $mode==='register' ? 'Đăng ký' : 'Đăng nhập' ?> — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="auth-wrap">
  <div class="auth-card">

    <div class="auth-logo" style="text-align:center;margin-bottom:16px">
      <img src="images/logo.png" alt="FROMSHOPWHERE" style="height:64px;width:auto">
    </div>

    <!-- Tabs -->
    <div style="display:flex;border-bottom:2px solid var(--border);margin-bottom:24px">
      <a href="login.php" style="flex:1;text-align:center;padding:10px;font-weight:700;font-size:14px;text-decoration:none;
         border-bottom:<?= $mode==='login' ? '2px solid var(--green-600,#0A8A4C)' : 'none' ?>;margin-bottom:-2px;
         color:<?= $mode==='login' ? 'var(--green-600,#0A8A4C)' : 'var(--text-muted,#888)' ?>">
        Đăng nhập
      </a>
      <a href="login.php?mode=register" style="flex:1;text-align:center;padding:10px;font-weight:700;font-size:14px;text-decoration:none;
         border-bottom:<?= $mode==='register' ? '2px solid var(--green-600,#0A8A4C)' : 'none' ?>;margin-bottom:-2px;
         color:<?= $mode==='register' ? 'var(--green-600,#0A8A4C)' : 'var(--text-muted,#888)' ?>">
        Đăng ký
      </a>
    </div>

    <?php if ($error): ?>
      <div class="form-error" style="background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px">
        ⚠ <?= e($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($mode === 'login'): ?>
    <!-- ĐĂNG NHẬP -->
    <form method="POST" autocomplete="on">
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" required
               placeholder="email@example.com"
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu</label>
        <input class="form-input" type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-submit">Đăng nhập →</button>
      <p style="text-align:center;margin-top:14px;font-size:12px;color:var(--text-muted)">
        Demo admin: <b>admin@fromshopwhere.com</b> / <b>admin123</b>
      </p>
    </form>

    <?php else: ?>
    <!-- ĐĂNG KÝ -->
    <form method="POST" autocomplete="on">
      <input type="hidden" name="action" value="register">
      <div class="form-group">
        <label class="form-label">Họ và tên</label>
        <input class="form-input" type="text" name="ho_ten" required
               placeholder="Nguyễn Văn A"
               value="<?= e($_POST['ho_ten'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" name="email" required
               placeholder="email@example.com"
               value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Mật khẩu</label>
        <input class="form-input" type="password" name="password" required placeholder="Tối thiểu 6 ký tự">
      </div>
      <div class="form-group">
        <label class="form-label">Nhập lại mật khẩu</label>
        <input class="form-input" type="password" name="password2" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-submit">Tạo tài khoản →</button>
    </form>
    <?php endif; ?>

  </div>
</div>

<footer>
  <div class="footer-inner">
    <div class="footer-bottom">
      <p>© 2025 FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div><div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div><div class="pay-badge">ZALO</div>
      </div>
    </div>
  </div>
</footer>
<script src="shared.js"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{restoreTheme();updateCartBadge();syncCartPanel();})</script>
</body>
</html>
