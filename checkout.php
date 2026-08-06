<?php
require_once __DIR__ . '/config.php';
startSession();
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode(SITE_URL . '/checkout.php'));
}
$currentPage = '';
$_user = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đặt hàng — FROMSHOPWHERE</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>


<link rel="stylesheet" href="assets/css/checkout.css">
<script src="assets/js/checkout.js"></script>

<!-- ══════════════════════════════════════ -->
<!--  PAGE HEADER                          -->
<!-- ══════════════════════════════════════ -->
<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">🔒 Thanh toán an toàn</span></div>
    <h1>Đặt hàng</h1>
    <p>Điền thông tin để nhận license key qua email</p>
  </div>
</div>

<!-- ══════════════════════════════════════ -->
<!--  CHECKOUT FORM                        -->
<!-- ══════════════════════════════════════ -->
<div class="checkout-wrap">
  <div class="checkout-grid">

    <!-- Left: Form -->
    <?php
    // Lấy thêm thông tin từ DB để auto-fill
    $ck_user = db()->prepare("SELECT ho_ten, email, so_dien_thoai FROM users WHERE id=:id");
    $ck_user->execute([':id' => $_user['id']]);
    $ck_info = $ck_user->fetch();
    ?>
    <div>
      <div class="checkout-box" style="margin-bottom:20px">
        <h3>Thông tin nhận hàng</h3>
        <div class="form-group">
          <label class="form-label">Họ và tên</label>
          <input class="form-input" type="text" id="ckName" required minlength="2" maxlength="100"
                 value="<?= e($ck_info['ho_ten'] ?? '') ?>"
                 placeholder="Nguyễn Văn A" autocomplete="name">
        </div>
        <div class="form-group">
          <label class="form-label">Email nhận license key</label>
          <input class="form-input" type="email" id="ckEmail" required
                 value="<?= e($ck_info['email'] ?? '') ?>"
                 placeholder="ten@gmail.com" autocomplete="email"
                 pattern="[^\s@]+@[^\s@]+\.[^\s@]{2,}"
                 title="Email phải có dạng: ten@gmail.com">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Số điện thoại</label>
          <input class="form-input" type="tel" id="ckPhone" required
                 value="<?= e($ck_info['so_dien_thoai'] ?? '') ?>"
                 placeholder="0901234567" autocomplete="tel"
                 inputmode="numeric"
                 pattern="0(3|5|7|8|9)[0-9]{8}"
                 maxlength="10"
                 title="Số điện thoại VN: 10 số, đầu 03/05/07/08/09">
        </div>
      </div>

      <div class="checkout-box">
        <h3>Phương thức thanh toán</h3>
        <div class="pay-option selected" data-method="VNPay" onclick="selectPayment(this)"
             style="border-color:var(--teal-700);background:var(--teal-50)">
          <input type="radio" name="pay" value="VNPay" checked>
          <div style="display:flex;align-items:center;gap:10px;flex:1">
            <span style="font-size:22px">💳</span>
            <div>
              <div style="font-weight:700;font-size:14px">VNPay</div>
              <div style="font-size:12px;color:var(--text-muted)">ATM · Visa · Mastercard · QR Code</div>
            </div>
            <span style="background:var(--teal-700);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:auto;white-space:nowrap">Khuyến nghị</span>
          </div>
        </div>
        <div class="pay-option" data-method="Chuyển khoản ngân hàng" onclick="selectPayment(this)">
          <input type="radio" name="pay" value="Chuyen khoan">
          <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:22px">🏦</span>
            <div>
              <div style="font-weight:700;font-size:14px">Chuyển khoản ngân hàng</div>
              <div style="font-size:12px;color:var(--text-muted)">Quét mã QR sau khi đặt hàng</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Summary -->
    <div class="checkout-box checkout-summary-sticky">
      <h3>Tóm tắt đơn hàng</h3>
      <div id="checkoutItems" style="margin-bottom:14px"></div>

      <div class="order-line"><span class="lbl">Tạm tính</span><span class="val" id="checkoutSub">0đ</span></div>
      <div class="order-line"><span class="lbl">Giảm giá</span><span class="val" id="checkoutDiscount" style="color:#B0242B">−0đ</span></div>
      <div class="order-line total">
        <span class="lbl">Tổng cộng</span>
        <span class="val" id="checkoutTotal">0đ</span>
      </div>

      <div class="coupon-row">
        <input class="form-input" type="text" placeholder="Mã giảm giá (VD: FIRST15)" style="font-size:13px;padding:9px 12px" id="couponInput">
        <button class="btn-apply" onclick="applyCoupon()">Áp dụng</button>
      </div>

      <button class="btn-checkout" id="checkoutBtn" onclick="handleCheckout()">Đặt hàng ngay →</button>

      <p style="text-align:center;margin-top:12px;font-size:13px">
        <a href="products.php" style="color:var(--teal-700)">← Tiếp tục mua sắm</a>
      </p>
    </div>

  </div>
</div>

<!-- ══════════════════════════════════════ -->
<!--  FOOTER                               -->
<!-- ══════════════════════════════════════ -->
<footer>
  <div class="footer-inner">
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div>
        <div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div>
        <div class="pay-badge">ZALO</div>
        <div class="pay-badge">ATM</div>
      </div>
    </div>
  </div>
</footer>


<!-- ══ VNPAY CHECKOUT SCRIPT ══ -->
<div id="loadingModal" style="display:none;position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.6);backdrop-filter:blur(6px);align-items:center;justify-content:center">
  <div style="background:var(--card-bg,#fff);border-radius:20px;padding:40px 32px;text-align:center;min-width:280px;box-shadow:0 24px 80px rgba(0,0,0,.3)">
    <div style="font-size:48px;margin-bottom:14px">🔄</div>
    <div style="font-size:16px;font-weight:700;color:var(--text,#1A1A18);margin-bottom:8px">Đang chuyển đến VNPay...</div>
    <div style="font-size:13px;color:var(--text-muted,#6B7F6E)">Vui lòng không đóng trang này</div>
    <div style="margin-top:20px;width:40px;height:40px;border:3px solid rgba(240,73,35,.2);border-top-color:var(--teal-500);border-radius:50%;animation:spin .8s linear infinite;margin:20px auto 0"></div>
  </div>
</div>


<!-- QR Modal (cho phương thức chuyển khoản) -->
<div id="qrModal">
  <div class="qr-box">
    <button onclick="closeQR()" class="qr-close-btn">✕</button>
    <div class="qr-icon">💳</div>
    <h3 class="qr-title">Quét mã để thanh toán</h3>
    <p class="qr-desc">Sử dụng MoMo, ZaloPay, VietQR hoặc Internet Banking</p>
    <div class="qr-image-wrap">
      <img src="images/ui/qr-payment.jpg" alt="QR Payment" class="qr-image">
    </div>
    <div id="qrAmount" class="qr-amount"></div>
    <p class="qr-hint">⚡ Sau khi chuyển khoản, nhấn xác nhận bên dưới</p>
    <button onclick="confirmPayment()" class="qr-confirm-btn">
      ✅ Đã chuyển khoản xong
    </button>
    <button onclick="closeQR()" class="qr-back-btn">Quay lại</button>
  </div>
</div>



</body>
</html>