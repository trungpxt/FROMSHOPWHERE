/* ═══════════════════════════════════════════
   FROMSHOPWHERE — shared.js  (v2)
   Cart, Theme, Toast, Cards
═══════════════════════════════════════════ */

const SITE_ROOT = (function() {
  const s = document.querySelector('script[src*="shared.js"]');
  if (s) {
    const url = new URL(s.src, location.href);
    // shared.js nằm ở assets/js/shared.js → bỏ 2 cấp để về root
    return url.pathname.replace(/\/assets\/js\/shared\.js$/, '').replace(/\/$/, '');
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
  if (k) {
    k.textContent = isDark ? '🌙' : '☀️';
    k.style.setProperty('--knob-x', isDark ? '18px' : '0px');
    k.classList.remove('fsw-flip');
    void k.offsetWidth;
    k.classList.add('fsw-flip');
  }
  const ab = document.getElementById('admThemeBtn');
  if (ab) ab.textContent = isDark ? '🌙' : '☀️';
  localStorage.setItem('fsw-theme', isDark ? 'dark' : 'light');
}
function restoreTheme() {
  if (localStorage.getItem('fsw-theme') === 'dark') {
    document.body.classList.add('dark');
    document.documentElement.classList.add('dark');
    const k = document.getElementById('themeKnob');
    if (k) { k.textContent = '🌙'; k.style.setProperty('--knob-x', '18px'); }
    const ab = document.getElementById('admThemeBtn');
    if (ab) ab.textContent = '🌙';
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
function _fswShake(el) {
  if (!el) return;
  el.classList.remove('fsw-shake');
  void el.offsetWidth;
  el.classList.add('fsw-shake');
}
function validateCheckoutForm() {
  const nameEl  = document.getElementById('ckName');
  const emailEl = document.getElementById('ckEmail');
  const phoneEl = document.getElementById('ckPhone');
  const name  = (nameEl?.value || '').trim();
  const email = (emailEl?.value || '').trim();
  const phone = (phoneEl?.value || '').trim();
  if (name.length < 2)             { _fswShake(nameEl);  return 'Họ tên phải có ít nhất 2 ký tự'; }
  if (!isValidEmail(email))        { _fswShake(emailEl); return 'Email không đúng định dạng (vd: ten@gmail.com)'; }
  if (!isValidVietnamPhone(phone)) { _fswShake(phoneEl); return 'Số điện thoại phải 10 số, bắt đầu 0 (vd: 0901234567)'; }
  return null;
}
function bindVnPhoneInput(el) {
  if (!el) return;
  el.addEventListener('input', () => {
    el.value = normalizePhone(el.value).slice(0, 10);
    el.classList.remove('fsw-field-ok', 'fsw-field-bad');
    if (!el.value || isValidVietnamPhone(el.value)) el.setCustomValidity('');
  });
  el.addEventListener('blur', () => {
    el.classList.remove('fsw-field-ok', 'fsw-field-bad');
    if (!el.value) { el.setCustomValidity(el.required ? 'Vui lòng nhập số điện thoại' : ''); return; }
    const ok = isValidVietnamPhone(el.value);
    el.setCustomValidity(ok ? '' : 'Số điện thoại VN: 10 số, đầu 03/05/07/08/09');
    el.classList.add(ok ? 'fsw-field-ok' : 'fsw-field-bad');
  });
}
function bindEmailInput(el) {
  if (!el) return;
  el.addEventListener('blur', () => {
    el.classList.remove('fsw-field-ok', 'fsw-field-bad');
    if (!el.value) { el.setCustomValidity(el.required ? 'Vui lòng nhập email' : ''); return; }
    const ok = isValidEmail(el.value);
    el.setCustomValidity(ok ? '' : 'Email không đúng định dạng (vd: ten@gmail.com)');
    el.classList.add(ok ? 'fsw-field-ok' : 'fsw-field-bad');
  });
  el.addEventListener('input', () => {
    el.classList.remove('fsw-field-ok', 'fsw-field-bad');
    if (!el.value || isValidEmail(el.value)) el.setCustomValidity('');
  });
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

/* ── SẢN PHẨM ĐÃ XEM GẦN ĐÂY ──
   Lưu localStorage, tối đa 8 sản phẩm, mới nhất lên đầu, tự loại trùng.
   Gọi recordRecentlyViewed() ở trang chi tiết sản phẩm khi tải trang. */
const RECENTLY_VIEWED_MAX = 8;
function getRecentlyViewed() {
  try { return JSON.parse(localStorage.getItem('fsw-recently-viewed') || '[]'); } catch { return []; }
}
function recordRecentlyViewed(id, name, price, img, cat) {
  let list = getRecentlyViewed().filter(p => p.id != id);
  list.unshift({ id: parseInt(id), name, price: parseFloat(price), img, cat });
  if (list.length > RECENTLY_VIEWED_MAX) list = list.slice(0, RECENTLY_VIEWED_MAX);
  localStorage.setItem('fsw-recently-viewed', JSON.stringify(list));
}
/* Hiển thị vào 1 khối #wrapId / #gridId, tự ẩn nếu không có gì (hoặc chỉ có
   đúng sản phẩm đang xem — trường hợp mới xem lần đầu). */
function renderRecentlyViewed(wrapId, gridId, excludeId) {
  const wrap = document.getElementById(wrapId);
  const grid = document.getElementById(gridId);
  if (!wrap || !grid) return;
  const list = getRecentlyViewed().filter(p => p.id != excludeId).slice(0, 4);
  if (list.length === 0) { wrap.style.display = 'none'; return; }
  wrap.style.display = '';
  grid.innerHTML = list.map(p => {
    const nameSafe = String(p.name).replace(/'/g, "\\'");
    const imgSafe = String(p.img || '').replace(/'/g, "\\'");
    return `<article class="prod-card">
      <a class="pc-thumb" href="product-demo.php?id=${p.id}">
        <img src="images/${p.img || 'default.jpg'}" alt="${String(p.name).replace(/"/g,'&quot;')}" loading="lazy" onerror="this.src='images/default.jpg'">
      </a>
      <div class="pc-badges"><span class="pc-badge-cat">${p.cat || ''}</span></div>
      <a class="pc-name" href="product-demo.php?id=${p.id}">${p.name}</a>
      <div class="pc-price-row"><span class="pc-price">${fmt(p.price)}</span></div>
      <div class="pc-btns">
        <button class="pc-btn-cart" onclick="addToCart(${p.id},'${nameSafe}',${p.price},'${imgSafe}')">🛒 Thêm giỏ</button>
        <a class="pc-btn-detail" href="product-demo.php?id=${p.id}">Chi tiết</a>
      </div>
    </article>`;
  }).join('');
}

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

function setQty(id, value) {
  const cart = getCart();
  const item = cart.find(i => i.id == id);
  if (!item) return;
  let qty = parseInt(value, 10);
  if (!Number.isFinite(qty) || qty < 1) qty = 1;
  item.qty = qty;
  saveCart(cart);
  syncCartPanel();
  updateCartBadge();
}

function updateCartBadge() {
  const badge = document.getElementById('cartCount');
  if (!badge) return;
  const newCount = getCartCount();
  const changed = badge.textContent !== String(newCount);
  badge.textContent = newCount;
  if (changed) {
    badge.classList.remove('fsw-bump');
    void badge.offsetWidth;
    badge.classList.add('fsw-bump');
  }
}

/* ── HIỆU ỨNG TOÀN SITE (ripple + scroll-reveal) ── */
function _fswRipple(e) {
  const btn = e.currentTarget;
  const rect = btn.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const ripple = document.createElement('span');
  ripple.className = 'fsw-ripple';
  ripple.style.width = ripple.style.height = size + 'px';
  ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
  ripple.style.top  = (e.clientY - rect.top  - size / 2) + 'px';
  btn.appendChild(ripple);
  setTimeout(() => ripple.remove(), 650);
}

function initFswEffects() {
  // Ripple khi bấm nút
  const btnSel = '.btn-primary,.btn-ghost,.btn-login,.btn-submit,.btn-checkout,' +
                 '.btn-add,.btn-atc,.btn-detail,.hbtn-primary,.hbtn-ghost,.btn-home,.btn-retry,' +
                 '.pc-btn-cart,.btn-buy-now,.btn-add-cart,.newsletter-btn';
  document.querySelectorAll(btnSel).forEach(btn => {
    if (btn._fswRippleBound) return;
    btn._fswRippleBound = true;
    btn.addEventListener('click', _fswRipple);
  });

  // Scroll-reveal: gắn hiệu ứng trồi lên khi cuộn tới cho card/section
  const revealSel = '.product-card,.hero-card,.blog-card-new,.footer-col,.footer-brand,' +
                     'section,.cta-banner,.auth-card';
  const targets = document.querySelectorAll(revealSel + ':not(.fsw-reveal)');
  if (targets.length && 'IntersectionObserver' in window) {
    targets.forEach((el, i) => {
      el.classList.add('fsw-reveal');
      el.style.transitionDelay = Math.min(i % 6, 5) * 0.06 + 's';
    });
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('fsw-in');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    targets.forEach(el => io.observe(el));
  }

  _fswInitScrollFx();
}

/* Thanh tiến trình cuộn trang + nút "lên đầu trang" (tự tạo, chạy mọi trang) */
function _fswInitScrollFx() {
  if (document.getElementById('fswProgress')) return; // đã khởi tạo rồi

  const bar = document.createElement('div');
  bar.id = 'fswProgress';
  document.body.appendChild(bar);

  const topBtn = document.createElement('button');
  topBtn.id = 'fswToTop';
  topBtn.type = 'button';
  topBtn.title = 'Lên đầu trang';
  topBtn.innerHTML = '↑';
  topBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  document.body.appendChild(topBtn);

  function onScroll() {
    const h = document.documentElement;
    const scrolled = h.scrollTop;
    const max = h.scrollHeight - h.clientHeight;
    bar.style.width = (max > 0 ? (scrolled / max) * 100 : 0) + '%';
    topBtn.classList.toggle('fsw-show', scrolled > 420);
  }
  document.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
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

function clearCartConfirm() {
  if (getCart().length === 0) return;
  if (!confirm('Xoá toàn bộ sản phẩm trong giỏ hàng?')) return;
  saveCart([]);
  updateCartBadge();
  syncCartPanel();
  if (typeof syncCheckoutPage === 'function') syncCheckoutPage();
  showToast('Đã xoá toàn bộ giỏ hàng');
}

function syncCartPanel() {
  const cart = getCart();
  const totalEl = document.getElementById('cartTotal');
  if (totalEl) totalEl.textContent = fmt(getCartTotal());

  const clearBtn = document.getElementById('cartClearBtn');
  if (clearBtn) clearBtn.style.display = cart.length > 0 ? '' : 'none';

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
          <input type="number" min="1" step="1" value="${i.qty}"
            onchange="setQty(${i.id}, this.value)"
            onclick="this.select()"
            style="width:38px;height:22px;border:1px solid var(--border);border-radius:5px;background:none;font-size:13px;font-weight:600;text-align:center;color:var(--text);-moz-appearance:textfield">
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
  return `<button class="btn-atc" onclick="event.stopPropagation();addToCart(${id},'${n}',${price},'${i}')" title="Thêm vào giỏ">🛒 Thêm vào giỏ</button>`;
}
function _detailBtn(id, siteUrl) {
  return `<button class="btn-detail" onclick="event.stopPropagation();window.location.href='${siteUrl}/product-demo.php?id=${id}'" title="Xem chi tiết">Chi tiết</button>`;
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
    ? `<span class="ribbon ribbon-new">MỚI</span>` : '';
  const oldPrice = giaGoc > giaBan
    ? `<span style="text-decoration:line-through;color:#aaa;font-size:12px;margin-left:6px">${fmt(giaGoc)}</span>` : '';
  const discBadge = discount > 0
    ? `<span class="ribbon ribbon-discount">-${discount}%</span>` : '';

  return `<div class="hero-card prod-card-wrap" style="display:flex;flex-direction:column;cursor:pointer" onclick="window.location.href='${siteUrl}/product-demo.php?id=${p.id}'">
    <div class="hc-icon" style="background:#E1F5EE">
      <img src="${imgSrc}" alt="${title}" onerror="this.src='${siteUrl}/images/default.jpg'">
      ${newTag}${discBadge}
    </div>
    <div class="hc-name" style="margin-top:10px">${title}</div>
    <div class="hc-price" style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;margin-bottom:8px">
      ${fmt(giaBan)}${oldPrice}
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
  const badge    = discount > 0 ? `<span class="ribbon ribbon-discount">-${discount}%</span>` : '';
  const newTag   = parseInt(p.la_moi) ? `<span class="ribbon ribbon-new">MỚI</span>` : '';
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

/* ── MÀN HÌNH CHÀO khi mới mở web (1 lần / phiên truy cập) ── */
(function _fswSplashInit() {
  try {
    if (sessionStorage.getItem('fsw-splash-shown')) return;
    sessionStorage.setItem('fsw-splash-shown', '1');
  } catch (e) { return; }
  if (!document.body) return;

  const isDark = document.body.classList.contains('dark');
  const logoSrc = SITE_ROOT + '/images/ui/' + (isDark ? 'logo-dark.png' : 'logo.png');

  const overlay = document.createElement('div');
  overlay.id = 'fswSplash';
  overlay.innerHTML =
    '<div class="fsw-splash-logo"><img src="' + logoSrc + '" alt="FROMSHOPWHERE"></div>' +
    '<div class="fsw-splash-name">FROMSHOPWHERE</div>' +
    '<div class="fsw-splash-dots"><span></span><span></span><span></span></div>';
  document.body.appendChild(overlay);

  const MIN_MS = 900;
  const startedAt = Date.now();
  let hidden = false;
  function hideSplash() {
    if (hidden) return;
    hidden = true;
    const wait = Math.max(0, MIN_MS - (Date.now() - startedAt));
    setTimeout(() => {
      overlay.classList.add('fsw-splash-out');
      setTimeout(() => overlay.remove(), 600);
    }, wait);
  }
  if (document.readyState === 'complete') hideSplash();
  else window.addEventListener('load', hideSplash);
  setTimeout(hideSplash, 3000); // an toàn: không để treo màn hình chào quá lâu
})();

/* ── WISHLIST (yêu thích) ── */
function toggleWishlist(productId, btnEl) {
  if (!window.FSW_IS_LOGGED_IN) {
    showToast('Vui lòng đăng nhập để lưu yêu thích');
    setTimeout(() => { window.location.href = SITE_ROOT + '/login.php?redirect=' + encodeURIComponent(location.pathname + location.search); }, 900);
    return;
  }
  const fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('product_id', productId);
  fetch(SITE_ROOT + '/api/wishlist.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.ok) { showToast(data.error || 'Có lỗi xảy ra'); return; }
      document.querySelectorAll('.wl-heart[data-pid="' + productId + '"]').forEach(btn => {
        btn.classList.toggle('active', data.in_wishlist);
        btn.classList.remove('wl-pop');
        void btn.offsetWidth;
        btn.classList.add('wl-pop');
      });
      updateWishlistBadge(data.count);
      showToast(data.in_wishlist ? '🔖 Đã thêm vào yêu thích' : 'Đã bỏ khỏi yêu thích');
      if (typeof onWishlistRemoved === 'function' && !data.in_wishlist) onWishlistRemoved(productId);
    })
    .catch(() => showToast('Không thể kết nối máy chủ'));
}

function updateWishlistBadge(count) {
  const badge = document.getElementById('wishlistCount');
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? '' : 'none';
  }
  const navIcon = document.getElementById('navWishlistBtn');
  if (navIcon) navIcon.classList.toggle('has-items', count > 0);
}

function initWishlistState() {
  if (!window.FSW_IS_LOGGED_IN) return;
  fetch(SITE_ROOT + '/api/wishlist.php?action=list')
    .then(r => r.json())
    .then(data => {
      if (!data.ok) return;
      updateWishlistBadge(data.ids.length);
      data.ids.forEach(id => {
        document.querySelectorAll('.wl-heart[data-pid="' + id + '"]').forEach(btn => btn.classList.add('active'));
      });
    })
    .catch(() => {});
}
document.addEventListener('DOMContentLoaded', initWishlistState);

/* ── SO SÁNH SẢN PHẨM (client-side, localStorage, tối đa 3) ── */
const CMP_KEY = 'fsw-compare';
const CMP_MAX = 3;

function getCompareList() {
  try { return JSON.parse(localStorage.getItem(CMP_KEY)) || []; }
  catch (e) { return []; }
}

function saveCompareList(list) {
  localStorage.setItem(CMP_KEY, JSON.stringify(list));
  renderCompareBar();
}

function toggleCompare(id, name, price, img, btnEl) {
  let list = getCompareList();
  const idx = list.findIndex(x => x.id === id);

  if (idx > -1) {
    list.splice(idx, 1);
  } else {
    if (list.length >= CMP_MAX) {
      showToast('Chỉ so sánh được tối đa ' + CMP_MAX + ' sản phẩm cùng lúc');
      return;
    }
    list.push({ id, name, price, img });
  }
  saveCompareList(list);

  document.querySelectorAll('.cmp-btn[data-pid="' + id + '"]').forEach(b => {
    b.classList.toggle('active', idx === -1);
  });
}

function removeFromCompare(id) {
  saveCompareList(getCompareList().filter(x => x.id !== id));
  document.querySelectorAll('.cmp-btn[data-pid="' + id + '"]').forEach(b => b.classList.remove('active'));
}

function renderCompareBar() {
  const list = getCompareList();
  let bar = document.getElementById('compareBar');

  if (!bar) {
    bar = document.createElement('div');
    bar.id = 'compareBar';
    document.body.appendChild(bar);
  }

  if (list.length === 0) {
    bar.classList.remove('show');
    bar.innerHTML = '';
    return;
  }

  const thumbs = list.map(p => '<img src="' + SITE_ROOT + '/' + p.img + '" alt="">').join('');
  bar.innerHTML =
    '<div class="cb-thumbs">' + thumbs + '</div>' +
    '<span class="cb-count">' + list.length + '/' + CMP_MAX + ' đã chọn</span>' +
    '<button type="button" class="cb-go" ' + (list.length < 2 ? 'disabled' : '') + ' onclick="goToCompare()">So sánh ngay</button>' +
    '<button type="button" class="cb-clear" title="Xoá tất cả" onclick="saveCompareList([])">✕</button>';
  bar.classList.add('show');
}

function goToCompare() {
  const ids = getCompareList().map(p => p.id).join(',');
  if (!ids) return;
  window.location.href = SITE_ROOT + '/compare.php?ids=' + ids;
}

function initCompareState() {
  const list = getCompareList();
  list.forEach(p => {
    document.querySelectorAll('.cmp-btn[data-pid="' + p.id + '"]').forEach(b => b.classList.add('active'));
  });
  renderCompareBar();
}
document.addEventListener('DOMContentLoaded', initCompareState);

/* ══ Mục lục trang chính sách (Điều khoản / Bảo mật): bấm cuộn mượt tới
   mục, và tự động tô sáng mục tương ứng khi cuộn qua từng phần. ══ */
function initPolicyToc() {
  const toc = document.querySelector('.policy-toc');
  if (!toc) return;
  const links = Array.from(toc.querySelectorAll('a[href^="#"]'));
  if (!links.length) return;
  const sections = links
    .map(a => document.getElementById(a.getAttribute('href').slice(1)))
    .filter(Boolean);

  // Bấm vào mục lục -> cuộn mượt tới đúng phần nội dung
  links.forEach(a => {
    a.addEventListener('click', function (e) {
      const target = document.getElementById(this.getAttribute('href').slice(1));
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.replaceState(null, '', this.getAttribute('href'));
    });
  });

  // Cuộn trang -> tự nổi bật mục lục tương ứng với phần đang xem
  const setActive = (id) => {
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
  };
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      const visible = entries.filter(en => en.isIntersecting);
      if (visible.length) {
        visible.sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
        setActive(visible[0].target.id);
      }
    }, { rootMargin: '-100px 0px -70% 0px', threshold: 0 });
    sections.forEach(sec => observer.observe(sec));
  }
  if (sections.length) setActive(sections[0].id);
}
document.addEventListener('DOMContentLoaded', initPolicyToc);
