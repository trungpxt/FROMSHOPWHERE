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

<?php
/* ── inline nav ── */
if (!defined('SITE_URL')) require_once __DIR__ . '/config.php';
startSession();
$_user        = currentUser();
$_currentPage = $currentPage ?? '';
?>
<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<!-- ── CART OVERLAY ── -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBackdrop(event)">
  <div class="cart-panel">
    <div class="cart-header">
      <h3>Giỏ hàng</h3>
      <button class="close-btn" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
      <div style="text-align:center;padding:48px 0">
        <div style="font-size:40px;margin-bottom:12px">🛒</div>
        <p style="color:var(--text-muted);font-size:14px">Giỏ hàng trống</p>
      </div>
    </div>
    <div class="cart-footer">
      <div class="cart-total">
        <span class="ct-label">Tổng cộng</span>
        <span class="ct-value" id="cartTotal">0đ</span>
      </div>
      <button class="btn-checkout" onclick="window.location.href='<?= SITE_URL ?>/checkout.php'">Tiến hành thanh toán →</button>
    </div>
  </div>
</div>

<!-- ══ NAV ══ -->
<nav>
  <div class="nav-inner">
    <a class="logo" href="<?= SITE_URL ?>/index.php">
      <img src="<?= SITE_URL ?>/images/logo.png" alt="FROMSHOPWHERE"
           style="height:44px;width:auto;object-fit:contain;filter:drop-shadow(0 0 6px rgba(0,0,0,.3))">
    </a>

    <ul class="nav-links">
      <li><a href="<?= SITE_URL ?>/index.php"    <?= $_currentPage==='home'     ?'class="active"':'' ?>>Trang chủ</a></li>
      <li><a href="<?= SITE_URL ?>/products.php" <?= $_currentPage==='products' ?'class="active"':'' ?>>Sản phẩm</a></li>
      <li><a href="<?= SITE_URL ?>/blog.php"     <?= $_currentPage==='blog'     ?'class="active"':'' ?>>Blog</a></li>
      <li><a href="<?= SITE_URL ?>/contact.php"  <?= $_currentPage==='contact'  ?'class="active"':'' ?>>Liên hệ</a></li>
    </ul>

    <div class="nav-right">
      <div class="search-wrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input class="search-box" type="search" placeholder="Tìm phần mềm..."
               onkeydown="if(event.key==='Enter')window.location.href='<?= SITE_URL ?>/products.php?q='+encodeURIComponent(this.value)">
      </div>

      <button class="theme-toggle" onclick="toggleTheme()" title="Chuyển sáng/tối" aria-label="Theme">
        <div class="theme-knob" id="themeKnob">☀️</div>
      </button>

      <div class="cart-btn" onclick="toggleCart()">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 01-8 0"/>
        </svg>
        <span class="cart-badge" id="cartCount">0</span>
      </div>

      <?php if ($_user): ?>
        <div style="position:relative">
          <button class="btn-login"
                  onclick="document.getElementById('userMenu').classList.toggle('open')"
                  style="cursor:pointer;display:flex;align-items:center;gap:6px">
            <span style="font-size:16px">👤</span>
            <?= e($_user['ho_ten']) ?> <span style="font-size:10px;opacity:.7">▾</span>
          </button>
          <div id="userMenu" class="user-dropdown">
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/">⚙️ Quản trị Admin</a>
            <?php endif; ?>
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

<style>
.user-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:6px;min-width:170px;box-shadow:0 8px 32px rgba(0,0,0,.2);z-index:300;display:none;flex-direction:column;gap:2px}
.user-dropdown.open{display:flex}
.user-dropdown a{padding:9px 13px;border-radius:8px;text-decoration:none;color:var(--text);font-size:13px;font-weight:500;transition:background .12s}
.user-dropdown a:hover{background:var(--bg-alt);color:var(--green-600,#0A8A4C)}
</style>
<script>
document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});
</script>

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
             style="border-color:#0F6E56;background:rgba(15,110,86,.04)">
          <input type="radio" name="pay" value="VNPay" checked>
          <div style="display:flex;align-items:center;gap:10px;flex:1">
            <span style="font-size:22px">💳</span>
            <div>
              <div style="font-weight:700;font-size:14px">VNPay</div>
              <div style="font-size:12px;color:var(--text-muted)">ATM · Visa · Mastercard · QR Code</div>
            </div>
            <span style="background:#0F6E56;color:#E1FCF6;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:auto;white-space:nowrap">Khuyến nghị</span>
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
    document.querySelectorAll('.pay-option').forEach(o => {
      o.classList.remove('selected');
      o.style.borderColor = '';
      o.style.background  = '';
    });
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
    const err = validateCheckoutForm();
    if (err) {
      showToast('⚠ ' + err);
      document.getElementById('ckEmail')?.reportValidity();
      document.getElementById('ckPhone')?.reportValidity();
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
    initCheckoutFieldValidation();
  });
</script>

<!-- ══ VNPAY CHECKOUT SCRIPT ══ -->
<div id="loadingModal" style="display:none;position:fixed;inset:0;z-index:600;background:rgba(0,0,0,.6);backdrop-filter:blur(6px);align-items:center;justify-content:center">
  <div style="background:var(--card-bg,#fff);border-radius:20px;padding:40px 32px;text-align:center;min-width:280px;box-shadow:0 24px 80px rgba(0,0,0,.3)">
    <div style="font-size:48px;margin-bottom:14px">🔄</div>
    <div style="font-size:16px;font-weight:700;color:var(--text,#1A1A18);margin-bottom:8px">Đang chuyển đến VNPay...</div>
    <div style="font-size:13px;color:var(--text-muted,#6B7F6E)">Vui lòng không đóng trang này</div>
    <div style="margin-top:20px;width:40px;height:40px;border:3px solid rgba(15,110,86,.2);border-top-color:#0F6E56;border-radius:50%;animation:spin .8s linear infinite;margin:20px auto 0"></div>
  </div>
</div>
<style>
@keyframes spin { to { transform:rotate(360deg) } }
</style>

<!-- QR Modal (cho phương thức chuyển khoản) -->
<div id="qrModal" style="display:none;position:fixed;inset:0;z-index:500;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:20px">
  <div style="background:var(--card-bg);border-radius:20px;padding:32px;width:min(380px,100%);text-align:center;position:relative;box-shadow:0 24px 80px rgba(0,0,0,.35);border:1px solid var(--border);animation:qrFadeIn .3s cubic-bezier(.22,1,.36,1)">
    <button onclick="closeQR()" style="position:absolute;top:14px;right:14px;width:30px;height:30px;border:none;background:var(--bg-alt);border-radius:8px;cursor:pointer;font-size:16px;color:var(--text-muted);display:flex;align-items:center;justify-content:center">✕</button>
    <div style="font-size:28px;margin-bottom:6px">💳</div>
    <h3 style="font-size:18px;font-weight:800;margin-bottom:4px;color:var(--text)">Quét mã để thanh toán</h3>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">Sử dụng MoMo, ZaloPay, VietQR hoặc Internet Banking</p>
    <div style="background:#fff;border-radius:14px;padding:16px;display:inline-block;box-shadow:0 4px 20px rgba(0,0,0,.1);margin-bottom:18px">
      <img src="qr-payment.jpg" alt="QR Payment" style="width:220px;height:220px;object-fit:contain;display:block">
    </div>
    <div id="qrAmount" style="font-size:22px;font-weight:800;color:#0F6E56;margin-bottom:6px"></div>
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:20px">⚡ Sau khi chuyển khoản, nhấn xác nhận bên dưới</p>
    <button onclick="confirmPayment()" style="width:100%;padding:13px;background:linear-gradient(135deg,#0F6E56,#1D9E75);color:#E1FCF6;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;margin-bottom:10px">
      ✅ Đã chuyển khoản xong
    </button>
    <button onclick="closeQR()" style="width:100%;padding:11px;background:none;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;color:var(--text-muted);font-family:inherit">Quay lại</button>
  </div>
</div>
<style>
@keyframes qrFadeIn { from{transform:scale(.9) translateY(20px);opacity:0} to{transform:scale(1) translateY(0);opacity:1} }
</style>

<script>
const PLACE_ORDER_URL = 'api/place-order.php';
const VNPAY_URL       = 'vnpay-payment.php';

function getSelectedPaymentMethod() {
  const sel = document.querySelector('.pay-option.selected');
  return sel?.dataset.method || 'VNPay';
}

function selectPayment(el) {
  document.querySelectorAll('.pay-option').forEach(o => {
    o.classList.remove('selected');
    o.style.borderColor = '';
    o.style.background  = '';
  });
  el.classList.add('selected');
  el.querySelector('input[type=radio]').checked = true;
  if (el.dataset.method === 'VNPay') {
    el.style.borderColor = '#0F6E56';
    el.style.background  = 'rgba(15,110,86,.04)';
  }
}

function getCheckoutPayload() {
  const code = couponApplied
    ? document.getElementById('couponInput').value.trim().toUpperCase()
    : null;
  return {
    items:         getCart().map(i => ({ id: i.id, qty: i.qty, price: i.price })),
    phuong_thuc_tt: getSelectedPaymentMethod(),
    ma_giam_gia:   code && COUPONS[code] ? code : null,
    ho_ten:        document.getElementById('ckName').value.trim(),
    email:         document.getElementById('ckEmail').value.trim(),
    phone:         document.getElementById('ckPhone').value.trim(),
  };
}

async function handleCheckout() {
  const cart = getCart();
  if (cart.length === 0) { showToast('⚠ Giỏ hàng đang trống!'); return; }
  const err = validateCheckoutForm();
  if (err) { showToast('⚠ ' + err); return; }

  const method = getSelectedPaymentMethod();

  if (method === 'VNPay') {
    await handleVNPay();
  } else {
    showQR();
  }
}

async function handleVNPay() {
  const btn = document.getElementById('checkoutBtn');
  btn.disabled = true;
  btn.textContent = '⏳ Đang xử lý...';
  document.getElementById('loadingModal').style.display = 'flex';

  try {
    const res  = await fetch(VNPAY_URL, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body:    JSON.stringify(getCheckoutPayload()),
    });
    const data = await res.json();

    if (data.ok && data.pay_url) {
      localStorage.removeItem('fsw-cart');
      window.location.href = data.pay_url;  // Chuyển đến cổng VNPay
    } else {
      document.getElementById('loadingModal').style.display = 'none';
      showToast('⚠ ' + (data.error || 'Không tạo được link thanh toán'));
      btn.disabled = false;
      btn.textContent = 'Đặt hàng ngay →';
    }
  } catch(e) {
    document.getElementById('loadingModal').style.display = 'none';
    showToast('⚠ Lỗi kết nối. Vui lòng thử lại.');
    btn.disabled = false;
    btn.textContent = 'Đặt hàng ngay →';
    console.error(e);
  }
}

// ── QR fallback (chuyển khoản ngân hàng) ──
function showQR() {
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
  if (cart.length === 0) { showToast('⚠ Giỏ hàng trống!'); return; }
  const err = validateCheckoutForm();
  if (err) { showToast('⚠ ' + err); return; }

  const btn = document.querySelector('#qrModal button[onclick="confirmPayment()"]');
  if (btn) { btn.disabled = true; btn.textContent = 'Đang lưu đơn...'; }
  try {
    const res  = await fetch(PLACE_ORDER_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(getCheckoutPayload()),
    });
    const data = await res.json();
    if (!data.ok) { showToast('⚠ ' + (data.error || 'Lỗi lưu đơn')); return; }
    localStorage.removeItem('fsw-cart');
    closeQR();
    showToast('🎉 Đặt hàng thành công! Mã đơn #' + data.order_id);
    setTimeout(() => { window.location.href = 'profile.php?tab=orders'; }, 1600);
  } catch(e) {
    showToast('⚠ Lỗi kết nối');
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '✅ Đã chuyển khoản xong'; }
  }
}

document.getElementById('qrModal').addEventListener('click', e => { if(e.target===document.getElementById('qrModal')) closeQR(); });
</script>
</body>
</html>