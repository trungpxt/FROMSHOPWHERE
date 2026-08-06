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
    csrfCheck();
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
              <div style='background:#16123F;padding:18px 24px;border-radius:12px 12px 0 0;text-align:center'>
                <h2 style='color:#EEECFB;margin:0;font-size:20px'>FROMSHOPWHERE</h2>
              </div>
              <div style='background:#fff;border:1px solid #e0e0e0;border-top:none;padding:28px 24px;border-radius:0 0 12px 12px'>
                <p style='font-size:15px;color:#333'>Xin chào <strong>".e($ho_ten)."</strong>,</p>
                <p style='color:#555;line-height:1.7'>Chúng tôi đã nhận được tin nhắn <strong>#$msgId</strong> của bạn về chủ đề: <strong>".e($chu_de)."</strong></p>
                <div style='background:#f5f5f5;border-left:3px solid #3B2FA0;padding:14px 16px;border-radius:4px;margin:16px 0;font-size:14px;color:#444;line-height:1.65'>
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
              <h3 style='color:#16123F'>Tin nhắn mới #$msgId</h3>
              <table style='width:100%;border-collapse:collapse;font-size:14px'>
                <tr><td style='padding:8px 0;color:#666;width:110px'>Họ tên:</td><td><strong>".e($ho_ten)."</strong></td></tr>
                <tr><td style='padding:8px 0;color:#666'>Email:</td><td><a href='mailto:".e($email)."'>".e($email)."</a></td></tr>
                <tr><td style='padding:8px 0;color:#666'>Chủ đề:</td><td>".e($chu_de)."</td></tr>
                <tr><td style='padding:8px 0;color:#666'>Thời gian:</td><td>".date('H:i d/m/Y')."</td></tr>
              </table>
              <div style='background:#f5f5f5;border-left:3px solid #3B2FA0;padding:14px 16px;margin-top:12px;font-size:14px;line-height:1.65;color:#333'>
                ".nl2br(e($noi_dung))."
              </div>
              <p style='margin-top:16px'><a href='".SITE_URL."/admin/contacts.php' style='background:#3B2FA0;color:#EEECFB;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:700'>Xem trong Admin →</a></p>
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

/* Prefill chủ đề/nội dung khi đến từ link "Yêu cầu bảo hành" ở trang chi tiết đơn hàng */
if (!$chu_de && isset($_GET['subject'])) {
    $chu_de = trim($_GET['subject']);
}
if (!$noi_dung && isset($_GET['order'])) {
    $noi_dung = 'Mã đơn hàng: #' . (int)$_GET['order'] . "\n\nMô tả vấn đề: ";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Liên Hệ Hỗ Trợ — FROMSHOPWHERE</title>
<meta name="description" content="Liên hệ FROMSHOPWHERE để được tư vấn phần mềm bản quyền phù hợp, hỗ trợ kỹ thuật, xử lý đơn hàng và bảo hành.">
<link rel="canonical" href="<?= SITE_URL ?>/contact.php">
<meta property="og:type" content="website">
<meta property="og:title" content="Liên Hệ — FROMSHOPWHERE">
<meta property="og:description" content="Liên hệ để được tư vấn phần mềm bản quyền, hỗ trợ kỹ thuật và xử lý đơn hàng.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/contact.php">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/contact.css">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>


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
      <?= csrfField() ?>
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
          $subjects = ['Hỗ trợ kỹ thuật','Tư vấn sản phẩm','Khiếu nại đơn hàng','Bảo hành / Đổi trả Key','Hợp tác kinh doanh','Câu hỏi khác'];
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
<?php include __DIR__ . '/includes/footer.php'; ?>


<script src="assets/js/contact.js"></script>
</body>
</html>
