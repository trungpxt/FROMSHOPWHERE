(function() {
  const grid  = document.getElementById('homeProductGrid');
  const pills = document.getElementById('catPills');

  function setLoading(on) {
    grid.style.opacity  = on ? '0.45' : '1';
    grid.style.pointerEvents = on ? 'none' : '';
    if (on) grid.style.transition = 'opacity .15s';
  }

  function buildCard(p) {
    const giaBan  = parseFloat(p.gia_ban)  || 0;
    const giaGoc  = parseFloat(p.gia_goc)  || 0;
    const disc    = (giaGoc > giaBan && giaGoc > 0) ? Math.round((1 - giaBan / giaGoc) * 100) : 0;
    const img     = p.hinh_anh || '';
    const imgSrc  = img ? `images/${img}` : `images/default.jpg`;
    const name    = p.ten_san_pham || '';
    const nameSafe = name.replace(/'/g, "\\'");
    const imgSafe  = img.replace(/'/g, "\\'");
    const cat     = p.ten_danh_muc || '';
    const detailUrl = `product-demo.php?id=${p.id}`;

    const discBadge = disc > 0 ? `<span class="pc-discount">-${disc}%</span>` : '';
    const newBadge  = parseInt(p.la_moi) ? `<span class="pc-badge-new">MỚI</span>` : '';
    const oldPrice  = (giaGoc > giaBan) ? `<span class="pc-price-old">${fmt(giaGoc)}</span>` : '';
    const outOfStock = p.trang_thai === 'het_hang';
    const oosBadge  = outOfStock ? `<span class="pc-badge-oos">HẾT HÀNG</span>` : '';
    const cartBtn   = outOfStock
      ? `<button class="pc-btn-cart" disabled style="opacity:.5;cursor:not-allowed">Hết hàng</button>`
      : `<button class="pc-btn-cart" onclick="buyNow(${p.id},'${nameSafe}',${giaBan},'${imgSafe}')">🛒 Mua ngay</button>`;

    return `<article class="prod-card${outOfStock ? ' prod-card-oos' : ''}">
      <a class="pc-thumb" href="${detailUrl}">
        <img src="${imgSrc}" alt="${name.replace(/"/g,'&quot;')}" loading="lazy" onerror="this.src='images/default.jpg'">
      </a>
      <div class="pc-badges">
        <span class="pc-badge-cat">${cat}</span>${oosBadge}${!outOfStock ? newBadge + discBadge : ''}
      </div>
      <a class="pc-name" href="${detailUrl}">${name}</a>
      <div class="pc-price-row">
        <span class="pc-price">${fmt(giaBan)}</span>${oldPrice}
      </div>
      <div class="pc-btns">
        ${cartBtn}
        <a class="pc-btn-detail" href="${detailUrl}">Chi tiết</a>
      </div>
    </article>`;
  }

  function loadCat(cat) {
    setLoading(true);
    const url = cat === 'all'
      ? 'api/products.php?limit=8'
      : `api/products.php?ten=${encodeURIComponent(cat)}&limit=8`;

    fetch(url)
      .then(r => r.json())
      .then(res => {
        const items = res.data || [];
        if (items.length === 0) {
          grid.innerHTML = `<p class="empty-grid-msg">Chưa có sản phẩm${cat !== 'all' ? ' trong danh mục này' : ''}.</p>`;
        } else {
          grid.innerHTML = items.map(buildCard).join('');
        }
        setLoading(false);
        grid.style.transition = 'opacity .2s';
        if (window.initFswEffects) window.initFswEffects();
      })
      .catch(() => { setLoading(false); });
  }

  pills.addEventListener('click', function(e) {
    const btn = e.target.closest('.cat-pill');
    if (!btn) return;
    pills.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadCat(btn.dataset.cat);
  });
})();

/* ── Banner "Ưu đãi đặc biệt": tự tạo mã giảm giá mới mỗi khi mở trang ──
   Dùng chung api/popup-coupon.php với popup mã giảm giá (assets/js/coupon-popup.js):
   mã được khoá theo phiên PHP (session), nên nếu popup đã tạo mã trong phiên
   này thì banner sẽ hiển thị đúng mã đó — không tạo trùng nhiều mã cho 1 khách. */
(function () {
  const codeEl = document.getElementById('ctaCode');
  const h2El   = document.getElementById('ctaH2');
  if (!codeEl) return;

  fetch('api/popup-coupon.php', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (!data.ok) return;
      codeEl.textContent = data.code;
      if (h2El) h2El.innerHTML = `Giảm ngay ${data.percent}% cho<br>đơn hàng đầu tiên`;
      codeEl.dataset.code = data.code;
    })
    .catch(() => { /* giữ nguyên mã mặc định nếu lỗi mạng */ });

  codeEl.addEventListener('click', function () {
    const code = codeEl.dataset.code || codeEl.textContent;
    navigator.clipboard.writeText(code).then(() => {
      const old = codeEl.textContent;
      codeEl.textContent = '✓ Đã sao chép!';
      setTimeout(() => { codeEl.textContent = old; }, 1500);
    });
  });
})();
