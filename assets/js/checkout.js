document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});

  let couponApplied = false;
  let couponPercent = 0;
  let couponCodeApplied = '';

async function validateCouponOnServer(code) {
  const res = await fetch('api/validate-coupon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ code })
  });
  return res.json();
}

function getSubtotal() {
  return getCart().reduce((s, i) => s + i.price * i.qty, 0);
}

function getDiscountAmount() {
  if (!couponApplied) return 0;
  return Math.round(getSubtotal() * couponPercent / 100);
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
  if (totEl) {
    const newTxt = fmt(final);
    if (totEl.textContent !== newTxt) {
      totEl.textContent = newTxt;
      totEl.classList.remove('fsw-bump');
      void totEl.offsetWidth;
      totEl.classList.add('fsw-bump');
    }
  }
}

async function applyCoupon() {
  const code = document.getElementById('couponInput').value.trim().toUpperCase();
  if (!code) {
    showToast('⚠ Nhập mã giảm giá');
    return;
  }
  const btn = document.querySelector('[onclick="applyCoupon()"]');
  if (btn) { btn.disabled = true; }
  try {
    const data = await validateCouponOnServer(code);
    if (data.ok) {
      couponApplied = true;
      couponPercent = data.percent;
      couponCodeApplied = code;
      recalcCheckoutTotals();
      showToast('✓ Áp dụng mã ' + code + ' — giảm ' + data.percent + '%');
    } else {
      couponApplied = false;
      couponPercent = 0;
      couponCodeApplied = '';
      recalcCheckoutTotals();
      showToast('⚠ ' + (data.error || 'Mã giảm giá không hợp lệ!'));
    }
  } catch (e) {
    showToast('⚠ Lỗi kết nối, thử lại sau');
  } finally {
    if (btn) { btn.disabled = false; }
  }
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
  el.classList.remove('fsw-pop');
  void el.offsetWidth;
  el.classList.add('fsw-pop');
  el.querySelector('input[type=radio]').checked = true;
  if (el.dataset.method === 'VNPay') {
    el.style.borderColor = document.body.classList.contains('dark') ? '#8B7CF0' : '#3B2FA0';
    el.style.background  = document.body.classList.contains('dark') ? 'rgba(139,124,240,.08)' : 'rgba(59,47,160,.04)';
  }
}

function getCheckoutPayload() {
  const code = couponApplied ? couponCodeApplied : null;
  return {
    items:         getCart().map(i => ({ id: i.id, qty: i.qty, price: i.price })),
    phuong_thuc_tt: getSelectedPaymentMethod(),
    ma_giam_gia:   code,
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

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('qrModal')?.addEventListener('click', e => { if(e.target===document.getElementById('qrModal')) closeQR(); });
});
