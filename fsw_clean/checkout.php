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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đặt hàng — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/nav.php'; ?>

<!-- ══════════════════════════════════════ -->
<!--  PAGE HEADER                          -->
<!-- ══════════════════════════════════════ -->
<div class="page-header">
  <div class="page-header-inner">
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
    <div>
      <div class="checkout-box" style="margin-bottom:20px">
        <h3>Thông tin nhận hàng</h3>
        <div class="form-group">
          <label class="form-label">Họ và tên</label>
          <input class="form-input" type="text" id="ckName" required minlength="2" maxlength="100"
       placeholder="Nguyễn Văn A" autocomplete="name">
        </div>
        <div class="form-group">
          <label class="form-label">Email nhận license key</label>
         <input class="form-input" type="email" id="ckEmail" required
       placeholder="email@example.com" autocomplete="email"
       pattern="[^\s@]+@[^\s@]+\.[^\s@]+"
       title="Email phải có dạng: ten@domain.com">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Số điện thoại</label>
         <input class="form-input" type="tel" id="ckPhone" required
       placeholder="0901234567" autocomplete="tel"
       inputmode="numeric"
       pattern="0[0-9]{9}"
       maxlength="10"
       title="Số điện thoại Việt Nam: 10 số, bắt đầu bằng 0">
        </div>
      </div>

      <div class="checkout-box">
        <h3>Phương thức thanh toán</h3>
        <div class="pay-option selected" onclick="selectPayment(this)">
          <input type="radio" name="pay" checked> 🏦 Chuyển khoản ngân hàng
        </div>
        <div class="pay-option" onclick="selectPayment(this)">
          <input type="radio" name="pay"> 📱 MoMo / ZaloPay
        </div>
        <div class="pay-option" onclick="selectPayment(this)">
          <input type="radio" name="pay"> 💳 Thẻ Visa / Mastercard
        </div>
      </div>
    </div>

    <!-- Right: Summary -->
    <div class="checkout-box" style="position:sticky;top:80px">
      <h3>Tóm tắt đơn hàng</h3>
      <div id="checkoutItems" style="margin-bottom:14px"></div>

      <div class="order-line"><span class="lbl">Tạm tính</span><span class="val" id="checkoutSub">0đ</span></div>
      <div class="order-line"><span class="lbl">Giảm giá</span><span class="val" id="checkoutDiscount" style="color:#A32D2D">−0đ</span></div>
      <div class="order-line total">
        <span class="lbl">Tổng cộng</span>
        <span class="val" id="checkoutTotal">0đ</span>
      </div>

      <div class="coupon-row">
        <input class="form-input" type="text" placeholder="Mã giảm giá (VD: FIRST15)" style="font-size:13px;padding:9px 12px" id="couponInput">
        <button class="btn-apply" onclick="applyCoupon()">Áp dụng</button>
      </div>

      <button class="btn-checkout" onclick="showQR()">Đặt hàng ngay →</button>

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
      <p>© 2025 FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
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

<script src="shared.js"></script>
<script>
  function selectPayment(el) {
    document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;
  }
  let couponApplied = false;

const COUPONS = {
  FIRST15: { percent: 15, label: 'Giảm 15% đơn đầu' }
  // thêm mã khác: SAVE10: { percent: 10, label: '...' }
};

function getSubtotal() {
  return getCart().reduce((s, i) => s + i.price * i.qty, 0);
}

function getDiscountAmount() {
  if (!couponApplied) return 0;
  const code = document.getElementById('couponInput')?.value.trim().toUpperCase();
  const c = COUPONS[code];
  if (!c) return 0;
  return Math.round(getSubtotal() * c.percent / 100);
}

function getFinalTotal() {
  return getSubtotal() - getDiscountAmount();
}

function recalcCheckoutTotals() {
  const sub = getSubtotal();
  const disc = getDiscountAmount();
  const final = sub - disc;

  const subEl = document.getElementById('checkoutSub');
  const discEl = document.getElementById('checkoutDiscount');
  const totEl = document.getElementById('checkoutTotal');

  if (subEl) subEl.textContent = fmt(sub);
  if (discEl) discEl.textContent = disc > 0 ? '−' + fmt(disc) : '−0đ';
  if (totEl) totEl.textContent = fmt(final);
}

function applyCoupon() {
  const code = document.getElementById('couponInput').value.trim().toUpperCase();
  if (!code) {
    showToast('⚠ Nhập mã giảm giá');
    return;
  }
  if (COUPONS[code]) {
    couponApplied = true;
    recalcCheckoutTotals();
    showToast('✓ Áp dụng mã ' + code + ' — giảm ' + COUPONS[code].percent + '%');
  } else {
    couponApplied = false;
    recalcCheckoutTotals();
    showToast('⚠ Mã giảm giá không hợp lệ!');
  }
}

  function placeOrder() {
    const name  = document.getElementById('ckName').value.trim();
    const email = document.getElementById('ckEmail').value.trim();
    const phone = document.getElementById('ckPhone').value.trim();
    const cart  = getCart();

    if (cart.length === 0) {
      showToast('⚠ Giỏ hàng đang trống!');
      return;
    }
    if (!name || !email || !phone) {
      showToast('⚠ Vui lòng điền đầy đủ thông tin nhận hàng!');
      return;
    }
    showToast('🎉 Đặt hàng thành công! License key sẽ gửi qua email.');
    localStorage.removeItem('fsw-cart');
    setTimeout(() => { window.location.href = 'index.php'; }, 1800);
  }

function syncCheckoutPage() {
  const cart  = getCart();
  const el    = document.getElementById('checkoutItems');

  if (cart.length === 0) {
    el.innerHTML = `<p style="font-size:13px;color:var(--text-muted)">Chưa có sản phẩm. <a href="products.php" style="color:var(--teal-700)">Mua ngay →</a></p>`;
  } else {
    el.innerHTML = cart.map(i => `
      <div class="order-line">
        <span class="lbl">${i.name} ×${i.qty}</span>
        <span class="val" style="font-weight:600;color:var(--teal-700)">${fmt(i.price * i.qty)}</span>
      </div>`).join('');
  }
  recalcCheckoutTotals();
}

  document.addEventListener('DOMContentLoaded', () => {
    restoreTheme();
    updateLoginBtn();
    syncCheckoutPage();
  });
</script>

<!-- ══ QR PAYMENT MODAL ══ -->
<div id="qrModal" style="
    display:none; position:fixed; inset:0; z-index:500;
    background:rgba(0,0,0,.65); backdrop-filter:blur(6px);
    align-items:center; justify-content:center; padding:20px;">
  <div style="
      background:var(--card-bg); border-radius:20px;
      padding:32px; width:min(380px,100%);
      text-align:center; position:relative;
      box-shadow:0 24px 80px rgba(0,0,0,.35);
      border:1px solid var(--border);
      animation:qrFadeIn .3s cubic-bezier(.22,1,.36,1);">

    <!-- Close -->
    <button onclick="closeQR()" style="
        position:absolute; top:14px; right:14px;
        width:30px; height:30px; border:none;
        background:var(--bg-alt); border-radius:8px;
        cursor:pointer; font-size:16px; color:var(--text-muted);
        display:flex; align-items:center; justify-content:center;">✕</button>

    <!-- Header -->
    <div style="font-size:28px; margin-bottom:6px">💳</div>
    <h3 style="font-size:18px; font-weight:800; margin-bottom:4px; color:var(--text)">Quét mã để thanh toán</h3>
    <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px">
      Sử dụng MoMo, ZaloPay, VietQR hoặc Internet Banking
    </p>

    <!-- QR Image -->
    <div style="
        background:#fff; border-radius:14px;
        padding:16px; display:inline-block;
        box-shadow:0 4px 20px rgba(0,0,0,.1);
        margin-bottom:18px;">
      <img src="qr-payment.jpg" alt="QR Payment"
           style="width:220px; height:220px; object-fit:contain; display:block;">
    </div>

    <!-- Amount -->
    <div id="qrAmount" style="
        font-size:22px; font-weight:800;
        color:var(--teal-700); margin-bottom:6px;
        font-family:var(--font-display,'Syne',sans-serif)"></div>
    <p style="font-size:12px; color:var(--text-muted); margin-bottom:20px">
      ⚡ Sau khi chuyển khoản, nhấn xác nhận bên dưới
    </p>

    <!-- Confirm button -->
    <button onclick="confirmPayment()" style="
        width:100%; padding:13px;
        background:linear-gradient(135deg,var(--teal-700,#0B4220),var(--teal-900,#041409));
        color:#fff; border:none; border-radius:10px;
        font-size:15px; font-weight:700; cursor:pointer;
        font-family:inherit; transition:transform .2s, box-shadow .2s;
        margin-bottom:10px;"
        onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(11,66,32,.4)'"
        onmouseout="this.style.transform='';this.style.boxShadow=''">
      ✅ Đã chuyển khoản xong
    </button>
    <button onclick="closeQR()" style="
        width:100%; padding:11px;
        background:none; border:1.5px solid var(--border);
        border-radius:10px; font-size:13px; font-weight:600;
        cursor:pointer; color:var(--text-muted); font-family:inherit;">
      Quay lại
    </button>
  </div>
</div>

<style>
@keyframes qrFadeIn {
  from { transform:scale(.9) translateY(20px); opacity:0 }
  to   { transform:scale(1)  translateY(0);    opacity:1 }
}
</style>


<script>
const PLACE_ORDER_URL = 'api/place-order.php';

function getSelectedPaymentLabel() {
  const sel = document.querySelector('.pay-option.selected');
  if (!sel) return 'Chuyển khoản ngân hàng';
  return sel.textContent.trim();
}

function getCheckoutPayload() {
  const code = couponApplied
    ? document.getElementById('couponInput').value.trim().toUpperCase()
    : null;
  return {
    items: getCart().map(i => ({ id: i.id, qty: i.qty, price: i.price })),
    phuong_thuc_tt: getSelectedPaymentLabel(),
    ma_giam_gia: code && COUPONS[code] ? code : null
  };
}

function showQR() {
  const name  = document.getElementById('ckName').value.trim();
  const email = document.getElementById('ckEmail').value.trim();
  const items = getCart();

  if (items.length === 0) { showToast('⚠ Giỏ hàng đang trống!'); return; }
  if (!name || !email)    { showToast('⚠ Vui lòng điền tên và email!'); return; }

  const finalTotal = getFinalTotal();
  document.getElementById('qrAmount').textContent = finalTotal.toLocaleString('vi-VN') + 'đ';

  document.getElementById('qrModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeQR() {
  document.getElementById('qrModal').style.display = 'none';
  document.body.style.overflow = '';
}

async function confirmPayment() {
  const cart = getCart();
  if (cart.length === 0) {
    showToast('⚠ Giỏ hàng đang trống!');
    return;
  }

  const btn = document.querySelector('#qrModal button[onclick="confirmPayment()"]');
  if (btn) { btn.disabled = true; btn.textContent = 'Đang lưu đơn...'; }

  try {
    const res = await fetch(PLACE_ORDER_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(getCheckoutPayload())
    });
    const data = await res.json();

    if (res.status === 401) {
      showToast('⚠ Vui lòng đăng nhập để lưu đơn hàng');
      setTimeout(() => {
        window.location.href = 'login.php?redirect=' + encodeURIComponent('checkout.php');
      }, 1500);
      return;
    }

    if (!data.ok) {
      showToast('⚠ ' + (data.error || 'Không lưu được đơn hàng'));
      return;
    }

    localStorage.removeItem('fsw-cart');
    closeQR();
    showToast('🎉 Đặt hàng thành công! Mã đơn #' + data.order_id);
    setTimeout(() => {
      window.location.href = 'profile.php?tab=orders';
    }, 1500);

  } catch (err) {
    showToast('⚠ Lỗi kết nối server');
    console.error(err);
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '✅ Đã chuyển khoản xong'; }
  }
}

document.getElementById('qrModal').addEventListener('click', function(e) {
  if (e.target === this) closeQR();
});

// Đóng modal khi click nền
document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) closeQR();
});
</script>

</body>
</html>
