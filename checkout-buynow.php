<?php
require_once __DIR__ . '/config.php';
startSession();
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode(SITE_URL . '/checkout-buynow.php'));
}
$currentPage = '';
$_user = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mua ngay — FROMSHOPWHERE</title>
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

<div class="page-header">
  <div class="page-header-inner">
    <h1>⚡ Mua ngay</h1>
    <p>Điền thông tin để nhận license key qua email</p>
  </div>
</div>

<div class="checkout-wrap">
  <div id="emptyMsg" style="display:none;text-align:center;padding:60px 0">
    <div style="font-size:48px;margin-bottom:12px">🛒</div>
    <p style="color:var(--text-muted)">Không có sản phẩm. <a href="products.php" style="color:var(--teal-700)">Quay lại mua hàng →</a></p>
  </div>

  <div class="checkout-grid" id="mainContent">
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
       placeholder="ten@gmail.com" autocomplete="email"
       pattern="[^\s@]+@[^\s@]+\.[^\s@]{2,}"
       title="Email phải có dạng: ten@gmail.com">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Số điện thoại</label>
         <input class="form-input" type="tel" id="ckPhone" required
       placeholder="0901234567" autocomplete="tel"
       inputmode="numeric"
       pattern="0(3|5|7|8|9)[0-9]{8}"
       maxlength="10"
       title="Số điện thoại VN: 10 số, đầu 03/05/07/08/09">
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

    <!-- RIGHT: Tóm tắt -->
    <div class="checkout-box" style="position:sticky;top:80px">
      <h3>Sản phẩm</h3>

      <!-- Sản phẩm + điều chỉnh số lượng -->
      <div id="productSummary" style="margin-bottom:16px"></div>

      <!-- Số lượng -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding:14px;background:var(--bg-alt);border-radius:10px">
        <span style="font-size:14px;font-weight:600;color:var(--text)">Số lượng</span>
        <div style="display:flex;align-items:center;gap:0">
          <button onclick="changeQty(-1)" style="
              width:34px;height:34px;border:1.5px solid var(--border);
              border-radius:8px 0 0 8px;background:var(--card-bg);
              font-size:18px;cursor:pointer;color:var(--text);
              display:flex;align-items:center;justify-content:center;
              transition:background .15s;"
              onmouseover="this.style.background='var(--bg-alt)'"
              onmouseout="this.style.background='var(--card-bg)'">−</button>
          <div id="qtyDisplay" style="
              width:48px;height:34px;border-top:1.5px solid var(--border);
              border-bottom:1.5px solid var(--border);background:var(--card-bg);
              display:flex;align-items:center;justify-content:center;
              font-size:15px;font-weight:700;color:var(--text)">1</div>
          <button onclick="changeQty(1)" style="
              width:34px;height:34px;border:1.5px solid var(--border);
              border-radius:0 8px 8px 0;background:var(--card-bg);
              font-size:18px;cursor:pointer;color:var(--text);
              display:flex;align-items:center;justify-content:center;
              transition:background .15s;"
              onmouseover="this.style.background='var(--bg-alt)'"
              onmouseout="this.style.background='var(--card-bg)'">+</button>
        </div>
      </div>

      <div class="order-line"><span class="lbl">Đơn giá</span><span class="val" id="unitPrice">0đ</span></div>
      <div class="order-line total"><span class="lbl">Tổng cộng</span><span class="val" id="grandTotal">0đ</span></div>

      <div class="coupon-row" style="margin-top:14px">
        <input class="form-input" type="text" id="couponInput"
               placeholder="Mã giảm giá (VD: FIRST15)"
               style="font-size:13px;padding:9px 12px">
        <button class="btn-apply" onclick="applyCoupon()">Áp dụng</button>
      </div>

      <button class="btn-checkout" onclick="showQR()" style="margin-top:14px">
        Đặt hàng ngay →
      </button>

      <p style="text-align:center;margin-top:12px;font-size:13px">
        <a href="products.php" style="color:var(--teal-700)">← Tiếp tục mua sắm</a>
        &nbsp;·&nbsp;
        <a href="checkout.php" style="color:var(--teal-700)">Xem giỏ hàng</a>
      </p>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div>
        <div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div>
        <div class="pay-badge">ZALO</div>
      </div>
    </div>
  </div>
</footer>

<!-- QR MODAL -->
<div id="qrModal" style="
    display:none;position:fixed;inset:0;z-index:500;
    background:rgba(0,0,0,.65);backdrop-filter:blur(6px);
    align-items:center;justify-content:center;padding:20px;">
  <div style="
      background:var(--card-bg);border-radius:20px;padding:32px;
      width:min(380px,100%);text-align:center;position:relative;
      box-shadow:0 24px 80px rgba(0,0,0,.35);border:1px solid var(--border);
      animation:qrFadeIn .3s cubic-bezier(.22,1,.36,1);">
    <button onclick="closeQR()" style="
        position:absolute;top:14px;right:14px;width:30px;height:30px;
        border:none;background:var(--bg-alt);border-radius:8px;
        cursor:pointer;font-size:16px;color:var(--text-muted);
        display:flex;align-items:center;justify-content:center;">✕</button>
    <div style="font-size:28px;margin-bottom:6px">💳</div>
    <h3 style="font-size:18px;font-weight:800;margin-bottom:4px">Quét mã để thanh toán</h3>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">
      Sử dụng MoMo, ZaloPay, VietQR hoặc Internet Banking
    </p>
    <div style="background:#fff;border-radius:14px;padding:16px;display:inline-block;box-shadow:0 4px 20px rgba(0,0,0,.1);margin-bottom:18px;">
      <img src="images/qr-payment.jpg" alt="QR Payment" style="width:220px;height:220px;object-fit:contain;display:block;">
    </div>
    <div id="qrAmount" style="font-size:22px;font-weight:800;color:var(--teal-700);margin-bottom:6px"></div>
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:20px">
      ⚡ Sau khi chuyển khoản, nhấn xác nhận bên dưới
    </p>
    <button onclick="confirmPayment()" style="
        width:100%;padding:13px;background:linear-gradient(135deg,var(--teal-700,#065E34),var(--teal-900,#022314));
        color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;
        cursor:pointer;transition:transform .2s,box-shadow .2s;margin-bottom:10px;"
        onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(6,94,52,.4)'"
        onmouseout="this.style.transform='';this.style.boxShadow=''">
      ✅ Đã chuyển khoản xong
    </button>
    <button onclick="closeQR()" style="
        width:100%;padding:11px;background:none;border:1.5px solid var(--border);
        border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;color:var(--text-muted);">
      Quay lại
    </button>
  </div>
</div>
<style>
@keyframes qrFadeIn {
  from{transform:scale(.9) translateY(20px);opacity:0}
  to{transform:scale(1) translateY(0);opacity:1}
}
</style>

<script src="shared.js"></script>
<script>
let currentQty = 1;
let couponApplied = false;
let item = null;

function fmt(n) { return Number(n).toLocaleString('vi-VN') + 'đ'; }

function selectPayment(el) {
  document.querySelectorAll('.pay-option').forEach(o => {
    o.classList.remove('selected');
    o.querySelector('input').checked = false;
  });
  el.classList.add('selected');
  el.querySelector('input').checked = true;
}

function changeQty(delta) {
  currentQty = Math.max(1, currentQty + delta);
  document.getElementById('qtyDisplay').textContent = currentQty;
  updateTotals();
}

function updateTotals() {
  if (!item) return;
  const unit  = item.price;
  const sub   = unit * currentQty;
  const disc  = couponApplied ? Math.round(sub * 0.15) : 0;
  const total = sub - disc;
  document.getElementById('unitPrice').textContent = fmt(unit);
  document.getElementById('grandTotal').textContent = fmt(total);
}

function applyCoupon() {
  const code = document.getElementById('couponInput').value.trim().toUpperCase();
  if (code === 'FIRST15') {
    couponApplied = true;
    showToast('✓ Áp dụng mã FIRST15 — giảm 15%!');
    updateTotals();
  } else {
    showToast('⚠ Mã giảm giá không hợp lệ!');
  }
}

function showQR() {
  if (!item) { showToast('⚠ Không có sản phẩm!'); return; }
  const err = validateCheckoutForm();
  if (err) {
    showToast('⚠ ' + err);
    document.getElementById('ckEmail')?.reportValidity();
    document.getElementById('ckPhone')?.reportValidity();
    return;
  }

  const sub   = item.price * currentQty;
  const total = couponApplied ? Math.round(sub * 0.85) : sub;
  document.getElementById('qrAmount').textContent = fmt(total);
  document.getElementById('qrModal').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeQR() {
  document.getElementById('qrModal').style.display = 'none';
  document.body.style.overflow = '';
}

const PLACE_ORDER_URL = 'api/place-order.php';

function getSelectedPaymentLabel() {
  const sel = document.querySelector('.pay-option.selected');
  return sel ? sel.textContent.trim() : 'Chuyển khoản ngân hàng';
}

async function confirmPayment() {
  if (!item) {
    showToast('⚠ Không có sản phẩm!');
    return;
  }
  const err = validateCheckoutForm();
  if (err) {
    showToast('⚠ ' + err);
    document.getElementById('ckEmail')?.reportValidity();
    document.getElementById('ckPhone')?.reportValidity();
    return;
  }

  const couponCode = couponApplied ? 'FIRST15' : null;
  const payload = {
    items: [{ id: item.id, qty: currentQty, price: item.price }],
    phuong_thuc_tt: getSelectedPaymentLabel(),
    ma_giam_gia: couponCode
  };

  try {
    const res = await fetch(PLACE_ORDER_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (res.status === 401) {
      showToast('⚠ Vui lòng đăng nhập');
      setTimeout(() => {
        window.location.href = 'login.php?redirect=' + encodeURIComponent('checkout-buynow.php');
      }, 1500);
      return;
    }

    if (!data.ok) {
      showToast('⚠ ' + (data.error || 'Không lưu được đơn'));
      return;
    }

    localStorage.removeItem('fsw-buynow');
    closeQR();
    showToast('🎉 Đặt hàng thành công! Mã đơn #' + data.order_id);
    setTimeout(() => { window.location.href = 'profile.php?tab=orders'; }, 1500);

  } catch (e) {
    showToast('⚠ Lỗi kết nối server');
  }
}

document.getElementById('qrModal').addEventListener('click', function(e) {
  if (e.target === this) closeQR();
});

document.addEventListener('DOMContentLoaded', () => {
  restoreTheme(); updateCartBadge(); syncCartPanel();
  initCheckoutFieldValidation();

  const raw = localStorage.getItem('fsw-buynow');
  if (!raw) {
    document.getElementById('mainContent').style.display = 'none';
    document.getElementById('emptyMsg').style.display = 'block';
    return;
  }

  item = JSON.parse(raw);
  currentQty = item.qty || 1;
  document.getElementById('qtyDisplay').textContent = currentQty;

  // Hiện thông tin sản phẩm
  const imgSrc = item.img ? 'images/' + item.img : '';
  document.getElementById('productSummary').innerHTML = `
    <div style="display:flex;gap:14px;align-items:center;padding:14px;background:var(--bg-alt);border-radius:10px">
      <div style="width:60px;height:60px;border-radius:10px;overflow:hidden;flex-shrink:0;background:var(--card-bg);border:1px solid var(--border)">
        ${imgSrc ? `<img src="${imgSrc}" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none'">` : ''}
      </div>
      <div style="flex:1">
        <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:3px">${item.name}</div>
        <div style="font-size:13px;color:var(--teal-700);font-weight:700">${fmt(item.price)}</div>
      </div>
    </div>`;

  updateTotals();
});
</script>
</body>
</html>
