<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail-config.php';
startSession();
$currentPage = 'contact';
$_user = currentUser();

/* ── Tạo bảng nếu chưa có ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        ho_ten      VARCHAR(100) NOT NULL,
        email       VARCHAR(150) NOT NULL,
        chu_de      VARCHAR(200) NOT NULL,
        noi_dung    TEXT NOT NULL,
        nguoi_dung_id INT DEFAULT NULL,
        trang_thai  ENUM('chua_doc','da_doc','da_tra_loi') DEFAULT 'chua_doc',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (trang_thai),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

$success  = '';
$errors   = [];

/* Khởi tạo biến form — tránh Undefined variable */
$ho_ten   = '';
$email    = '';
$chu_de   = '';
$noi_dung = '';

/* ── XỬ LÝ FORM ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten   = trim($_POST['ho_ten']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $chu_de   = trim($_POST['chu_de']   ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');

    /* Validation */
    if (mb_strlen($ho_ten) < 2)   $errors[] = 'Họ tên phải có ít nhất 2 ký tự.';
    if (!isValidEmailPhp($email))  $errors[] = 'Email không đúng định dạng (vd: ten@gmail.com).';
    if (!$chu_de)                  $errors[] = 'Vui lòng chọn chủ đề.';
    if (mb_strlen($noi_dung) < 10) $errors[] = 'Nội dung phải có ít nhất 10 ký tự.';

    /* Chống spam: 3 tin / 10 phút từ cùng IP */
    if (!$errors) {
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $recent = (int)db()->query(
            "SELECT COUNT(*) FROM contact_messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
        )->fetchColumn();
        if ($recent >= 5) $errors[] = 'Bạn đã gửi quá nhiều tin nhắn. Vui lòng thử lại sau 10 phút.';
    }

    if (!$errors) {
        /* Lưu vào DB */
        $uid = $_user['id'] ?? null;
        db()->prepare("INSERT INTO contact_messages (ho_ten,email,chu_de,noi_dung,nguoi_dung_id) VALUES (?,?,?,?,?)")
           ->execute([$ho_ten, $email, $chu_de, $noi_dung, $uid]);
        $msgId = db()->lastInsertId();

        /* Gửi email xác nhận cho người gửi */
        $sent = false;
        try {
            $mail = createMailer();
            $mail->addAddress($email, $ho_ten);
            $mail->Subject = "[FROMSHOPWHERE] Đã nhận tin nhắn của bạn — #$msgId";
            $mail->isHTML(true);
            $mail->Body = "
            <div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:24px'>
              <div style='background:#04342C;padding:18px 24px;border-radius:12px 12px 0 0;text-align:center'>
                <h2 style='color:#E1FCF6;margin:0;font-size:20px'>FROMSHOPWHERE</h2>
              </div>
              <div style='background:#fff;border:1px solid #e0e0e0;border-top:none;padding:28px 24px;border-radius:0 0 12px 12px'>
                <p style='font-size:15px;color:#333'>Xin chào <strong>".e($ho_ten)."</strong>,</p>
                <p style='color:#555;line-height:1.7'>Chúng tôi đã nhận được tin nhắn <strong>#$msgId</strong> của bạn về chủ đề: <strong>".e($chu_de)."</strong></p>
                <div style='background:#f5f5f5;border-left:3px solid #0F6E56;padding:14px 16px;border-radius:4px;margin:16px 0;font-size:14px;color:#444;line-height:1.65'>
                  ".nl2br(e(mb_substr($noi_dung,0,300)))."...
                </div>
                <p style='color:#555;font-size:14px'>Đội ngũ hỗ trợ sẽ phản hồi email này trong vòng <strong>2 giờ</strong> (8:00–22:00 hàng ngày).</p>
                <p style='color:#999;font-size:12px;margin-top:20px'>© ".date('Y')." FROMSHOPWHERE — Phần mềm bản quyền chính hãng</p>
              </div>
            </div>";
            $mail->send();
            $sent = true;
        } catch(Exception $ex) {
            error_log('[Contact] Email error: ' . $ex->getMessage());
        }

        /* Gửi thông báo cho admin */
        try {
            $mail2 = createMailer();
            $mail2->addAddress(MAIL_FROM, 'Admin FSW');
            $mail2->Subject = "📩 Liên hệ mới #$msgId — ".e($chu_de)." từ ".e($ho_ten);
            $mail2->isHTML(true);
            $mail2->Body = "<div style='font-family:sans-serif;max-width:560px'>
              <h3 style='color:#04342C'>Tin nhắn mới #$msgId</h3>
              <table style='width:100%;border-collapse:collapse;font-size:14px'>
                <tr><td style='padding:8px 0;color:#666;width:110px'>Họ tên:</td><td><strong>".e($ho_ten)."</strong></td></tr>
                <tr><td style='padding:8px 0;color:#666'>Email:</td><td><a href='mailto:".e($email)."'>".e($email)."</a></td></tr>
                <tr><td style='padding:8px 0;color:#666'>Chủ đề:</td><td>".e($chu_de)."</td></tr>
                <tr><td style='padding:8px 0;color:#666'>Thời gian:</td><td>".date('H:i d/m/Y')."</td></tr>
              </table>
              <div style='background:#f5f5f5;border-left:3px solid #0F6E56;padding:14px 16px;margin-top:12px;font-size:14px;line-height:1.65;color:#333'>
                ".nl2br(e($noi_dung))."
              </div>
              <p style='margin-top:16px'><a href='".SITE_URL."/admin/contacts.php' style='background:#0F6E56;color:#E1FCF6;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:700'>Xem trong Admin →</a></p>
            </div>";
            $mail2->send();
        } catch(Exception $ex) {
            error_log('[Contact] Admin email error: ' . $ex->getMessage());
        }

        $success = $sent
            ? "✓ Tin nhắn đã được gửi thành công! Chúng tôi sẽ phản hồi qua email <strong>".e($email)."</strong> trong vòng 2 giờ."
            : "✓ Tin nhắn đã được lưu! Chúng tôi sẽ liên hệ lại bạn sớm nhất.";

        /* Reset form */
        $ho_ten = $email = $chu_de = $noi_dung = '';
    }
}

/* Prefill từ tài khoản */
$prefill_name  = $_user['ho_ten'] ?? '';
$prefill_email = $_user['email']  ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Liên hệ — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── Contact page styles ── */
.contact-hero {
  background: linear-gradient(135deg,#04342C 0%,#0F6E56 100%);
  padding: 56px 24px 44px; text-align: center;
  position: relative; overflow: hidden;
}
.contact-hero::before {
  content:''; position:absolute; inset:0;
  background-image: linear-gradient(rgba(225,252,246,.03) 1px,transparent 1px),
                    linear-gradient(90deg,rgba(225,252,246,.03) 1px,transparent 1px);
  background-size: 40px 40px; pointer-events:none;
}
.contact-hero-inner { max-width:520px; margin:0 auto; position:relative; z-index:1; }
.contact-hero h1 { font-size:clamp(26px,4vw,38px); font-weight:800; color:#fff; margin:0 0 10px; letter-spacing:-.02em; }
.contact-hero h1 span { color:#E1FCF6; }
.contact-hero p  { font-size:15px; color:rgba(255,255,255,.55); margin:0; }

.contact-wrap {
  max-width:1080px; margin:0 auto; padding:48px 24px;
  display:grid; grid-template-columns:1.2fr 1fr; gap:32px; align-items:start;
}
@media(max-width:820px){ .contact-wrap{ grid-template-columns:1fr; } }

/* Form card */
.ct-form-card {
  background: var(--card-bg,#fff);
  border: 1.5px solid var(--border,#E0E8E0);
  border-radius: 20px; padding: 32px;
  box-shadow: 0 4px 24px rgba(0,0,0,.06);
}
.ct-form-card h3 {
  font-size: 18px; font-weight: 800; margin: 0 0 22px;
  color: var(--text,#1A1A18); letter-spacing: -.01em;
}
body.dark .ct-form-card { background: var(--card-bg,rgba(255,255,255,.05)); border-color: rgba(255,255,255,.1); }
body.dark .ct-form-card h3 { color: #E8F5EE; }

.ct-group { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
.ct-group:last-of-type { margin-bottom:20px; }
.ct-label {
  font-size:11px; font-weight:700; color:var(--text-muted,#6B7F6E);
  text-transform:uppercase; letter-spacing:.07em;
}
.ct-input, .ct-select, .ct-textarea {
  padding: 11px 14px;
  border: 1.5px solid var(--border,#E0E8E0);
  border-radius: 10px;
  font-size: 14px; font-family: 'Plus Jakarta Sans',sans-serif;
  color: var(--text,#1A1A18); background: var(--card-bg,#fff);
  outline: none; width: 100%;
  transition: border-color .18s, box-shadow .18s;
}
.ct-input:focus, .ct-select:focus, .ct-textarea:focus {
  border-color: #0F6E56;
  box-shadow: 0 0 0 3px rgba(15,110,86,.08);
}
.ct-select { cursor: pointer; }
.ct-textarea { min-height: 120px; resize: vertical; line-height: 1.6; }
body.dark .ct-input, body.dark .ct-select, body.dark .ct-textarea {
  background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.1); color: #E8F5EE;
}
body.dark .ct-input:focus, body.dark .ct-select:focus, body.dark .ct-textarea:focus {
  border-color: #5DCAA5; box-shadow: 0 0 0 3px rgba(93,202,165,.1);
}
.ct-input.error { border-color: #EF4444; box-shadow: 0 0 0 3px rgba(239,68,68,.08); }

.ct-submit {
  width: 100%; padding: 13px;
  background: linear-gradient(135deg,#0F6E56,#1D9E75);
  color: #E1FCF6; border: none; border-radius: 11px;
  font-size: 15px; font-weight: 800; cursor: pointer;
  font-family: 'Plus Jakarta Sans',sans-serif;
  transition: all .2s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.ct-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(15,110,86,.3); }
.ct-submit:active { transform: translateY(0); }
.ct-submit:disabled { opacity:.6; cursor:not-allowed; transform:none; }

/* Alert boxes */
.ct-alert {
  padding: 13px 16px; border-radius: 11px;
  font-size: 14px; line-height: 1.6; margin-bottom: 20px;
  display: flex; gap: 10px; align-items: flex-start;
}
.ct-alert-ok  { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
.ct-alert-err { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

/* Info sidebar */
.ct-info-stack { display: flex; flex-direction: column; gap: 14px; }
.ct-info-card {
  background: var(--card-bg,#fff);
  border: 1.5px solid var(--border,#E0E8E0);
  border-radius: 16px; padding: 20px;
  display: flex; align-items: flex-start; gap: 14px;
  transition: border-color .2s, box-shadow .2s;
}
.ct-info-card:hover {
  border-color: rgba(15,110,86,.3);
  box-shadow: 0 6px 20px rgba(15,110,86,.1);
}
body.dark .ct-info-card { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); }
body.dark .ct-info-card:hover { border-color: rgba(93,202,165,.25); }
.ct-ico {
  width: 46px; height: 46px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; flex-shrink: 0;
}
.ct-info-label { font-size: 11px; font-weight: 700; color: var(--text-muted,#6B7F6E); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 4px; }
.ct-info-val   { font-size: 15px; font-weight: 700; color: var(--text,#1A1A18); margin-bottom: 3px; }
.ct-info-note  { font-size: 12px; color: var(--text-muted,#6B7F6E); }
body.dark .ct-info-val { color: #E8F5EE; }

.ct-cta-box {
  background: linear-gradient(135deg,#04342C,#0F6E56);
  border-radius: 16px; padding: 22px;
  color: rgba(255,255,255,.9);
}
.ct-cta-box h4 { font-size: 15px; font-weight: 800; color: #E1FCF6; margin: 0 0 8px; }
.ct-cta-box p  { font-size: 13px; color: rgba(255,255,255,.55); line-height: 1.65; margin: 0; }

/* Spinner */
.spinner { display:none; width:18px; height:18px; border:2.5px solid rgba(255,255,255,.3); border-top-color:#E1FCF6; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.ct-submit.loading .spinner { display:block; }
.ct-submit.loading .btn-txt { display:none; }
</style>
</head>
<body>

<div class="toast" id="toast"></div>

<!-- CART OVERLAY -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBackdrop(event)">
  <div class="cart-panel">
    <div class="cart-header"><h3>Giỏ hàng</h3><button class="close-btn" onclick="toggleCart()">✕</button></div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer">
      <div class="cart-total"><span class="ct-label">Tổng cộng</span><span class="ct-value" id="cartTotal">0đ</span></div>
      <button class="btn-checkout" onclick="window.location.href='<?= SITE_URL ?>/checkout.php'">Tiến hành thanh toán →</button>
    </div>
  </div>
</div>

<!-- NAV -->
<nav>
  <div class="nav-inner">
    <a class="logo" href="<?= SITE_URL ?>/index.php">
      <img src="<?= SITE_URL ?>/images/logo.png" alt="FROMSHOPWHERE" style="height:44px;width:auto;object-fit:contain">
    </a>
    <ul class="nav-links">
      <li><a href="<?= SITE_URL ?>/index.php">Trang chủ</a></li>
      <li><a href="<?= SITE_URL ?>/products.php">Sản phẩm</a></li>
      <li><a href="<?= SITE_URL ?>/blog.php">Blog</a></li>
      <li><a href="<?= SITE_URL ?>/contact.php" class="active">Liên hệ</a></li>
    </ul>
    <div class="nav-right">
      <div class="search-wrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="search-box" type="search" placeholder="Tìm phần mềm..."
               onkeydown="if(event.key==='Enter')window.location.href='<?= SITE_URL ?>/products.php?q='+encodeURIComponent(this.value)">
      </div>
      <button class="theme-toggle" onclick="toggleTheme()"><div class="theme-knob" id="themeKnob">☀️</div></button>
      <div class="cart-btn" onclick="toggleCart()">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        <span class="cart-badge" id="cartCount">0</span>
      </div>
      <?php if($_user): ?>
      <div style="position:relative">
        <button class="btn-login" onclick="document.getElementById('uDrop').classList.toggle('open')" style="cursor:pointer;display:flex;align-items:center;gap:6px">
          👤 <?= e($_user['ho_ten']) ?> ▾
        </button>
        <div id="uDrop" class="user-dropdown">
          <?php if(isAdmin()):?><a href="<?= SITE_URL ?>/admin/">⚙️ Quản trị</a><?php endif;?>
          <a href="<?= SITE_URL ?>/profile.php">👤 Tài khoản</a>
          <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
        </div>
      </div>
      <?php else: ?>
      <a class="btn-login" href="<?= SITE_URL ?>/login.php">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- HERO -->
<div class="contact-hero">
  <div class="contact-hero-inner">
    <h1>Liên hệ <span>hỗ trợ</span></h1>
    <p>Đội ngũ hỗ trợ luôn sẵn sàng giải đáp mọi thắc mắc của bạn 24/7</p>
  </div>
</div>

<!-- MAIN CONTENT -->
<div class="contact-wrap">

  <!-- FORM -->
  <div class="ct-form-card">
    <h3>✉️ Gửi tin nhắn cho chúng tôi</h3>

    <?php if($success): ?>
    <div class="ct-alert ct-alert-ok">
      <span>✅</span>
      <div><?= $success ?></div>
    </div>
    <?php endif; ?>

    <?php if($errors): ?>
    <div class="ct-alert ct-alert-err">
      <span>⚠️</span>
      <div><?= implode('<br>', array_map('e', $errors)) ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" id="ctForm" onsubmit="return handleSubmit(event)">
      <div class="ct-group">
        <label class="ct-label">Họ và tên *</label>
        <input class="ct-input" type="text" name="ho_ten" id="ctName"
               value="<?= e($ho_ten ?: $prefill_name) ?>"
               placeholder="Nguyễn Văn A" required minlength="2" maxlength="100" autocomplete="name">
      </div>
      <div class="ct-group">
        <label class="ct-label">Email *</label>
        <input class="ct-input" type="email" name="email" id="ctEmail"
               value="<?= e($email ?: $prefill_email) ?>"
               placeholder="ten@gmail.com" required autocomplete="email">
      </div>
      <div class="ct-group">
        <label class="ct-label">Chủ đề *</label>
        <select class="ct-input ct-select" name="chu_de" id="ctSubject">
          <?php
          $subjects = ['Hỗ trợ kỹ thuật','Tư vấn sản phẩm','Khiếu nại đơn hàng','Hợp tác kinh doanh','Câu hỏi khác'];
          foreach($subjects as $s):
            $sel = ($chu_de === $s || (!$chu_de && $s === 'Hỗ trợ kỹ thuật')) ? 'selected' : '';
          ?>
          <option value="<?= e($s) ?>" <?= $sel ?>><?= e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="ct-group">
        <label class="ct-label">Nội dung * <span id="charHint" style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-muted)"></span></label>
        <textarea class="ct-input ct-textarea" name="noi_dung" id="ctMsg"
                  placeholder="Mô tả chi tiết vấn đề của bạn để chúng tôi hỗ trợ nhanh nhất..."
                  required minlength="10" maxlength="2000"
                  oninput="document.getElementById('charHint').textContent='('+this.value.length+'/2000)'"><?= e($noi_dung) ?></textarea>
      </div>
      <button type="submit" class="ct-submit" id="ctSubmitBtn">
        <div class="spinner"></div>
        <span class="btn-txt">📨 Gửi tin nhắn</span>
      </button>
    </form>
  </div>

  <!-- INFO SIDEBAR -->
  <div class="ct-info-stack">
    <div class="ct-info-card">
      <div class="ct-ico" style="background:#E1F5EE">📧</div>
      <div>
        <div class="ct-info-label">Email hỗ trợ</div>
        <div class="ct-info-val">support@fromshopwhere.com</div>
        <div class="ct-info-note">Phản hồi trong 2 giờ (8:00–22:00)</div>
      </div>
    </div>
    <div class="ct-info-card">
      <div class="ct-ico" style="background:#E6F1FB">📱</div>
      <div>
        <div class="ct-info-label">Hotline miễn phí</div>
        <div class="ct-info-val">1900 1234</div>
        <div class="ct-info-note">Thứ 2 – Chủ nhật, 8:00–22:00</div>
      </div>
    </div>
    <div class="ct-info-card">
      <div class="ct-ico" style="background:#FEF3C7">💬</div>
      <div>
        <div class="ct-info-label">Zalo OA</div>
        <div class="ct-info-val">FROMSHOPWHERE Official</div>
        <div class="ct-info-note">Chat trực tiếp 24/7</div>
      </div>
    </div>
    <div class="ct-info-card">
      <div class="ct-ico" style="background:#EDE9FE">⏱️</div>
      <div>
        <div class="ct-info-label">Thời gian phản hồi</div>
        <div class="ct-info-val">Trong vòng 2 giờ</div>
        <div class="ct-info-note">Trung bình thực tế: ~15 phút</div>
      </div>
    </div>
    <div class="ct-cta-box">
      <h4>⚡ Cần hỗ trợ ngay?</h4>
      <p>Nhắn tin qua Zalo hoặc Facebook Messenger để được hỗ trợ nhanh nhất. Đội kỹ thuật luôn sẵn sàng từ 8:00 đến 22:00 hàng ngày, kể cả cuối tuần.</p>
    </div>
  </div>

</div>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div style="margin-bottom:12px"><img src="images/logo.png" alt="FROMSHOPWHERE" style="height:50px;width:auto;object-fit:contain"></div>
        <p>Nền tảng mua bán phần mềm bản quyền uy tín hàng đầu Việt Nam.</p>
        <div class="social-links"><a class="social-link" href="#">f</a><a class="social-link" href="#">in</a><a class="social-link" href="#">yt</a><a class="social-link" href="#">tk</a></div>
      </div>
      <div class="footer-col"><h4>Sản phẩm</h4><ul><li><a href="products.php">Thiết kế đồ hoạ</a></li><li><a href="products.php">Văn phòng</a></li><li><a href="products.php">Bảo mật</a></li></ul></div>
      <div class="footer-col"><h4>Hỗ trợ</h4><ul><li><a href="blog.php">Hướng dẫn</a></li><li><a href="contact.php">Liên hệ</a></li></ul></div>
      <div class="footer-col"><h4>Công ty</h4><ul><li><a href="#">Giới thiệu</a></li><li><a href="blog.php">Blog</a></li><li><a href="#">Điều khoản</a></li></ul></div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons"><div class="pay-badge">VISA</div><div class="pay-badge">MC</div><div class="pay-badge">MOMO</div><div class="pay-badge">ZALO</div><div class="pay-badge">ATM</div></div>
    </div>
  </div>
</footer>

<style>
.user-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:6px;min-width:170px;box-shadow:0 8px 32px rgba(0,0,0,.2);z-index:300;display:none;flex-direction:column;gap:2px}
.user-dropdown.open{display:flex}
.user-dropdown a{padding:9px 13px;border-radius:8px;text-decoration:none;color:var(--text);font-size:13px;font-weight:500;transition:background .12s}
.user-dropdown a:hover{background:var(--bg-alt);color:#0F6E56}
</style>
<script src="shared.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  restoreTheme();
  updateCartBadge();
  syncCartPanel();
});

document.addEventListener('click', e => {
  const m = document.getElementById('uDrop');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});

function handleSubmit(e) {
  // Client-side validation before submit
  const name  = document.getElementById('ctName').value.trim();
  const email = document.getElementById('ctEmail').value.trim();
  const msg   = document.getElementById('ctMsg').value.trim();
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;

  document.querySelectorAll('.ct-input').forEach(el => el.classList.remove('error'));

  let ok = true;
  if (name.length < 2)       { document.getElementById('ctName').classList.add('error');    ok = false; }
  if (!emailRe.test(email))  { document.getElementById('ctEmail').classList.add('error');   ok = false; }
  if (msg.length < 10)       { document.getElementById('ctMsg').classList.add('error');     ok = false; }

  if (!ok) {
    showToast('⚠ Vui lòng kiểm tra lại các trường thông tin.');
    return false;
  }

  // Show loading
  const btn = document.getElementById('ctSubmitBtn');
  btn.classList.add('loading');
  btn.disabled = true;
  return true; // allow normal form POST
}
</script>
</body>
</html>
