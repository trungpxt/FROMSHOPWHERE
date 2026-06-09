/* ═══════════════════════════════════════════
   FROMSHOPWHERE — shared.js
   Cart, Theme, Toast — không hardcode data
═══════════════════════════════════════════ */

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

/* ── CART ── */
function getCart() {
  try { return JSON.parse(localStorage.getItem('fsw-cart') || '[]'); } catch { return []; }
}
function saveCart(cart) {
  localStorage.setItem('fsw-cart', JSON.stringify(cart));
}
function getCartCount() {
  return getCart().reduce((s, i) => s + i.qty, 0);
}
function getCartTotal() {
  return getCart().reduce((s, i) => s + i.price * i.qty, 0);
}

/* addToCart nhận đầy đủ tham số từ PHP-rendered buttons */
function addToCart(id, name, price, img) {
  const cart     = getCart();
  const existing = cart.find(i => i.id == id);
  if (existing) {
    existing.qty++;
  } else {
    cart.push({ id: parseInt(id), name, price: parseFloat(price), img, qty: 1 });
  }
  saveCart(cart);
  updateCartBadge();
  showToast('✓ Đã thêm: ' + name);
}

function removeFromCart(id) {
  saveCart(getCart().filter(i => i.id != id));
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
  const cart    = getCart();
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

  el.innerHTML = cart.map(i => `
    <div class="cart-item">
      <div class="ci-img">
        <img src="images/${i.img}" onerror="this.style.display='none'" alt="">
      </div>
      <div class="ci-info">
        <div class="ci-name">${i.name}</div>
        <div class="ci-sub">Số lượng: ${i.qty}</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
        <div class="ci-price">${fmt(i.price * i.qty)}</div>
        <button onclick="removeFromCart(${i.id})"
          style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:12px;padding:0">
          ✕ Xoá
        </button>
      </div>
    </div>`).join('');
}

/* ── AUTH helpers (compat) ── */
function isLoggedIn() {
  return localStorage.getItem('fsw-logged-in') === 'true';
}
function updateLoginBtn() {
  // Không cần nữa vì PHP xử lý
}

/* ── Render product card từ JSON (cho AJAX filter) ── */
function renderCard(p, siteUrl) {
  const giaGoc   = parseFloat(p.gia_goc) || 0;
  const giaBan   = parseFloat(p.gia_ban) || 0;
  const discount = giaGoc > 0 ? Math.round((1 - giaBan / giaGoc) * 100) : 0;
  const oldPrice = giaGoc > 0 ? `<div class="pc-price-old">${fmt(giaGoc)}</div>` : '';
  const badge    = discount > 0 ? `<div class="pc-discount">-${discount}%</div>` : '';
  const newTag   = parseInt(p.la_moi) ? `<div class="pc-new-tag">MỚI</div>` : '';
  const name     = (p.ten_san_pham || '').replace(/'/g, "\\'");
  const img      = p.hinh_anh || '';

  return `
    <div class="product-card" onclick="addToCart(${p.id},'${name}',${giaBan},'${img}')">
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
