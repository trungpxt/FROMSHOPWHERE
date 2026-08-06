document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});

let currentQty = 1;
let couponApplied = false;
let couponPercent = 0;
let couponCodeApplied = '';
let item = null;

function fmt(n) { return Number(n).toLocaleString('vi-VN') + 'đ'; }

async function validateCouponOnServer(code) {
  const res = await fetch('api/validate-coupon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ code })
  });
  return res.json();
}

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
  document.getElementById('qtyDisplay').value = currentQty;
  updateTotals();
}

function setQty(value) {
  let qty = parseInt(value, 10);
  if (!Number.isFinite(qty) || qty < 1) qty = 1;
  currentQty = qty;
  document.getElementById('qtyDisplay').value = currentQty;
  updateTotals();
}

function updateTotals() {
  if (!item) return;
  const unit  = item.price;
  const sub   = unit * currentQty;
  const disc  = couponApplied ? Math.round(sub * couponPercent / 100) : 0;
  const total = sub - disc;
  document.getElementById('unitPrice').textContent = fmt(unit);
  document.getElementById('grandTotal').textContent = fmt(total);
}

async function applyCoupon() {
  const code = document.getElementById('couponInput').value.trim().toUpperCase();
  if (!code) { showToast('⚠ Nhập mã giảm giá'); return; }
  try {
    const data = await validateCouponOnServer(code);
    if (data.ok) {
      couponApplied = true;
      couponPercent = data.percent;
      couponCodeApplied = code;
      showToast('✓ Áp dụng mã ' + code + ' — giảm ' + data.percent + '%');
      updateTotals();
    } else {
      couponApplied = false;
      couponPercent = 0;
      couponCodeApplied = '';
      showToast('⚠ ' + (data.error || 'Mã giảm giá không hợp lệ!'));
      updateTotals();
    }
  } catch (e) {
    showToast('⚠ Lỗi kết nối, thử lại sau');
  }
}

const VNPAY_URL = 'vnpay-payment.php';

function getSelectedPaymentMethod() {
  const sel = document.querySelector('.pay-option.selected');
  return sel?.dataset.method || 'VNPay';
}

async function handleCheckout() {
  if (!item) { showToast('⚠ Không có sản phẩm!'); return; }
  const err = validateCheckoutForm();
  if (err) { showToast('⚠ ' + err); return; }

  const method = getSelectedPaymentMethod();

  if (method === 'VNPay') {
    await handleVNPayBuyNow();
  } else {
    showQR();
  }
}

async function handleVNPayBuyNow() {
  const btn = document.querySelector('.btn-checkout');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Đang xử lý...'; }
  const loadingModal = document.getElementById('loadingModal');
  if (loadingModal) loadingModal.style.display = 'flex';

  const sub   = item.price * currentQty;
  const total = couponApplied ? Math.round(sub * (100 - couponPercent) / 100) : sub;
  const couponCode = couponApplied ? couponCodeApplied : null;

  try {
    const res = await fetch(VNPAY_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        items: [{ id: item.id, qty: currentQty, price: item.price }],
        phuong_thuc_tt: 'VNPay',
        ma_giam_gia: couponCode,
        ho_ten:  document.getElementById('ckName')?.value.trim()  || '',
        email:   document.getElementById('ckEmail')?.value.trim() || '',
        phone:   document.getElementById('ckPhone')?.value.trim() || '',
      })
    });
    const data = await res.json();

    if (res.status === 401) {
      showToast('⚠ Vui lòng đăng nhập trước');
      setTimeout(() => {
        window.location.href = 'login.php?redirect=' + encodeURIComponent('checkout-buynow.php');
      }, 1500);
      return;
    }

    if (data.ok && data.pay_url) {
      localStorage.removeItem('fsw-buynow');
      window.location.href = data.pay_url;
    } else {
      if (loadingModal) loadingModal.style.display = 'none';
      showToast('⚠ ' + (data.error || 'Không tạo được link VNPay'));
      if (btn) { btn.disabled = false; btn.textContent = 'Đặt hàng ngay →'; }
    }
  } catch(e) {
    if (loadingModal) loadingModal.style.display = 'none';
    showToast('⚠ Lỗi kết nối. Thử lại sau.');
    if (btn) { btn.disabled = false; btn.textContent = 'Đặt hàng ngay →'; }
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
  const total = couponApplied ? Math.round(sub * (100 - couponPercent) / 100) : sub;
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

  const couponCode = couponApplied ? couponCodeApplied : null;
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

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('qrModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeQR();
  });

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
  document.getElementById('qtyDisplay').value = currentQty;

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
