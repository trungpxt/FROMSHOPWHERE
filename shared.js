/* ═══════════════════════════════════════════
   FROMSHOPWHERE — shared.js  (v2)
   Cart, Theme, Toast, Cards
═══════════════════════════════════════════ */

const SITE_ROOT = (function() {
  const s = document.querySelector('script[src*="shared.js"]');
  if (s) {
    const url = new URL(s.src, location.href);
    return url.pathname.replace(/\/shared\.js$/, '').replace(/\/$/, '');
  }
  return '';
})();

/* ── TOAST ── */
function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.remove('show');
  void t.offsetWidth;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 2600);
}

/* ── THEME ── */
function toggleTheme() {
  const isDark = document.body.classList.toggle('dark');
  const k = document.getElementById('themeKnob');
  if (k) k.textContent = isDark ? '🌙' : '☀️';
  localStorage.setItem('fsw-theme', isDark ? 'dark' : 'light');
}
function restoreTheme() {
  if (localStorage.getItem('fsw-theme') === 'dark') {
    document.body.classList.add('dark');
    const k = document.getElementById('themeKnob');
    if (k) k.textContent = '🌙';
  }
}

/* ── FORMAT ── */
function fmt(n) {
  return Number(n).toLocaleString('vi-VN') + 'đ';
}

/* ── EMAIL / SĐT ── */
function normalizePhone(raw) {
  return String(raw || '').replace(/\D/g, '');
}
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(String(email || '').trim());
}
function isValidVietnamPhone(phone) {
  return /^0(3|5|7|8|9)\d{8}$/.test(normalizePhone(phone));
}
function validateCheckoutForm() {
  const name  = (document.getElementById('ckName')?.value || '').trim();
  const email = (document.getElementById('ckEmail')?.value || '').trim();
  const phone = (document.getElementById('ckPhone')?.value || '').trim();
  if (name.length < 2)           return 'Họ tên phải có ít nhất 2 ký tự';
  if (!isValidEmail(email))      return 'Email không đúng định dạng (vd: ten@gmail.com)';
  if (!isValidVietnamPhone(phone)) return 'Số điện thoại phải 10 số, bắt đầu 0 (vd: 0901234567)';
  return null;
}
function bindVnPhoneInput(el) {
  if (!el) return;
  el.addEventListener('input', () => {
    el.value = normalizePhone(el.value).slice(0, 10);
    if (!el.value || isValidVietnamPhone(el.value)) el.setCustomValidity('');
  });
  el.addEventListener('blur', () => {
    if (!el.value) { el.setCustomValidity(el.required ? 'Vui lòng nhập số điện thoại' : ''); return; }
    el.setCustomValidity(isValidVietnamPhone(el.value) ? '' : 'Số điện thoại VN: 10 số, đầu 03/05/07/08/09');
  });
}
function bindEmailInput(el) {
  if (!el) return;
  el.addEventListener('blur', () => {
    if (!el.value) { el.setCustomValidity(el.required ? 'Vui lòng nhập email' : ''); return; }
    el.setCustomValidity(isValidEmail(el.value) ? '' : 'Email không đúng định dạng (vd: ten@gmail.com)');
  });
  el.addEventListener('input', () => { if (!el.value || isValidEmail(el.value)) el.setCustomValidity(''); });
}
function initCheckoutFieldValidation() {
  bindEmailInput(document.getElementById('ckEmail'));
  bindVnPhoneInput(document.getElementById('ckPhone'));
}

/* ── CART ── */
function getCart() {
  try { return JSON.parse(localStorage.getItem('fsw-cart') || '[]'); } catch { return []; }
}
function saveCart(cart) {
  localStorage.setItem('fsw-cart', JSON.stringify(cart));
}
function getCartCount()  { return getCart().reduce((s, i) => s + i.qty, 0); }
function getCartTotal()  { return getCart().reduce((s, i) => s + i.price * i.qty, 0); }

function addToCart(id, name, price, img) {
  const cart = getCart();
  const existing = cart.find(i => i.id == id);
  if (existing) { existing.qty++; }
  else { cart.push({ id: parseInt(id), name, price: parseFloat(price), img, qty: 1 }); }
  saveCart(cart);
  updateCartBadge();
  syncCartPanel();
  showToast('✓ Đã thêm: ' + name);
}

function removeFromCart(id) {
  saveCart(getCart().filter(i => i.id != id));
  syncCartPanel();
  updateCartBadge();
}

function updateQty(id, delta) {
  const cart = getCart();
  const item = cart.find(i => i.id == id);
  if (!item) return;
  item.qty = Math.max(1, item.qty + delta);
  saveCart(cart);
  syncCartPanel();
  updateCartBadge();
}

function updateCartBadge() {
  const badge = document.getElementById('cartCount');
  if (badge) badge.textContent = getCartCount();
}

/* ── CART PANEL ── */
function toggleCart() {
  const el = document.getElementById('cartOverlay');
  if (el) el.classList.toggle('open');
}
function closeCartOnBackdrop(e) {
  if (e.target === document.getElementById('cartOverlay'))
    document.getElementById('cartOverlay').classList.remove('open');
}

function syncCartPanel() {
  const cart = getCart();
  const totalEl = document.getElementById('cartTotal');
  if (totalEl) totalEl.textContent = fmt(getCartTotal());

  const el = document.getElementById('cartItems');
  if (!el) return;

  if (cart.length === 0) {
    el.innerHTML = `
      <div style="text-align:center;padding:48px 0">
        <div style="font-size:40px;margin-bottom:12px">🛒</div>
        <p style="color:var(--text-muted);font-size:14px">Giỏ hàng trống</p>
      </div>`;
    return;
  }

  el.innerHTML = cart.map(i => {
    const imgSrc = i.img
      ? (i.img.startsWith('http') ? i.img : `${location.origin}${SITE_ROOT}/images/${i.img}`)
      : '';
    return `
    <div class="cart-item">
      <div class="ci-img">
        ${imgSrc ? `<img src="${imgSrc}" onerror="this.style.display='none'" alt="">` : ''}
      </div>
      <div class="ci-info">
        <div class="ci-name">${i.name}</div>
        <div class="ci-sub" style="display:flex;align-items:center;gap:8px;margin-top:4px">
          <button onclick="updateQty(${i.id},-1)" style="width:22px;height:22px;border:1px solid var(--border);border-radius:5px;background:none;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;color:var(--text)">−</button>
          <span style="font-weight:600;min-width:16px;text-align:center">${i.qty}</span>
          <button onclick="updateQty(${i.id},1)" style="width:22px;height:22px;border:1px solid var(--border);border-radius:5px;background:none;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;color:var(--text)">+</button>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
        <div class="ci-price">${fmt(i.price * i.qty)}</div>
        <button onclick="removeFromCart(${i.id})"
          style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:12px;padding:0">
          ✕ Xoá
        </button>
      </div>
    </div>`;
  }).join('');
}

/* ── AUTH helpers ── */
function isLoggedIn() {
  return localStorage.getItem('fsw-logged-in') === 'true';
}
function updateLoginBtn() { /* PHP xử lý */ }

/* ── CARD RENDERERS ── */
function _cardBtn(id, name, price, img) {
  const n = (name||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
  const i = (img||'').replace(/'/g,"\\'");
  return `<button class="btn-atc" onclick="addToCart(${id},'${n}',${price},'${i}')" title="Thêm vào giỏ">🛒 Thêm vào giỏ</button>`;
}
function _detailBtn(id, siteUrl) {
  return `<button class="btn-detail" onclick="window.location.href='${siteUrl}/product-demo.php?id=${id}'" title="Xem chi tiết">Chi tiết</button>`;
}

/* renderHomeCard — dùng cho index.php filterHome */
function renderHomeCard(p, siteUrl) {
  const giaGoc  = parseFloat(p.gia_goc) || 0;
  const giaBan  = parseFloat(p.gia_ban) || 0;
  const img     = p.hinh_anh || '';
  const imgSrc  = img ? `${siteUrl}/images/${img}` : `${siteUrl}/images/default.jpg`;
  const title   = (p.ten_san_pham||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const cat     = (p.ten_danh_muc||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const discount = (giaGoc > giaBan && giaGoc > 0) ? Math.round((1-giaBan/giaGoc)*100) : 0;
  const newTag  = parseInt(p.la_moi)
    ? `<span style="background:red;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;margin-left:5px;font-weight:700">MỚI</span>` : '';
  const oldPrice = giaGoc > giaBan
    ? `<span style="text-decoration:line-through;color:#aaa;font-size:12px;margin-left:6px">${fmt(giaGoc)}</span>` : '';
  const discBadge = discount > 0
    ? `<span style="background:#D92B2B;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:20px;margin-left:5px">-${discount}%</span>` : '';

  return `<div class="hero-card prod-card-wrap" style="display:flex;flex-direction:column;cursor:default">
    <div class="hc-icon" style="cursor:pointer;background:#E1F5EE"
         onclick="window.location.href='${siteUrl}/product-demo.php?id=${p.id}'">
      <img src="${imgSrc}" alt="${title}" onerror="this.src='${siteUrl}/images/default.jpg'">
    </div>
    <div class="hc-name" style="cursor:pointer;margin-top:10px"
         onclick="window.location.href='${siteUrl}/product-demo.php?id=${p.id}'">${title}${newTag}</div>
    <div class="hc-price" style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;margin-bottom:8px">
      ${fmt(giaBan)}${oldPrice}${discBadge}
    </div>
    <div class="hc-tag" style="margin-bottom:10px">${cat}</div>
    <div style="display:flex;gap:8px;margin-top:auto">
      ${_cardBtn(p.id, p.ten_san_pham, giaBan, img)}
      ${_detailBtn(p.id, siteUrl)}
    </div>
  </div>`;
}

/* renderCard — dùng cho products.php grid AJAX */
function renderCard(p, siteUrl) {
  const giaGoc   = parseFloat(p.gia_goc) || 0;
  const giaBan   = parseFloat(p.gia_ban) || 0;
  const discount = giaGoc > 0 ? Math.round((1-giaBan/giaGoc)*100) : 0;
  const oldPrice = giaGoc > 0 ? `<div class="pc-price-old">${fmt(giaGoc)}</div>` : '';
  const badge    = discount > 0 ? `<div class="pc-discount">-${discount}%</div>` : '';
  const newTag   = parseInt(p.la_moi) ? `<div class="pc-new-tag">MỚI</div>` : '';
  const img      = p.hinh_anh || '';
  const name     = (p.ten_san_pham||'').replace(/'/g,"\\'");
  return `
<div class="product-card" style="cursor:pointer" onclick="window.location.href='${siteUrl}/product-demo.php?id=${p.id}'">
  <div class="pc-img" style="padding:0;overflow:hidden">
    <img src="${siteUrl}/images/${img}" alt="${p.ten_san_pham||''}"
         style="width:100%;height:100%;object-fit:cover"
         onerror="this.parentElement.style.background='#f0f0f0'">
    ${badge}${newTag}
  </div>
  <div class="pc-body">
    <div class="pc-cat">${p.ten_danh_muc||''}</div>
    <div class="pc-name">${p.ten_san_pham||''}</div>
    <div class="pc-ver">${p.phien_ban||''}</div>
    <div class="pc-footer">
      <div>
        <div class="pc-price-main">${fmt(giaBan)}</div>
        ${oldPrice}
      </div>
      <button class="btn-add"
        onclick="event.stopPropagation();addToCart(${p.id},'${name}',${giaBan},'${img}')"
        title="Thêm vào giỏ">+</button>
    </div>
  </div>
</div>`;
}

/* buyNow */
function buyNow(id, name, price, img) {
  localStorage.setItem('fsw-buynow', JSON.stringify({ id, name, price, img, qty: 1 }));
  window.location.href = SITE_ROOT + '/checkout-buynow.php';
}
