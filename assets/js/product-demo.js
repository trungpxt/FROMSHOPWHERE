document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});

// RELATED_PRODUCTS, CURRENT_PRODUCT, SITE_URL_JS được định nghĩa
// trong <script> inline ngay trước file này (xem product-demo.php)

function fmtVND(num) {
  return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
}

function switchTab(btn, tab) {
  document.querySelectorAll('.pd-tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  if (tab === 'desc') {
    document.getElementById('tabDesc').style.display = 'block';
    document.getElementById('tabReviews').style.display = 'none';
  } else {
    document.getElementById('tabDesc').style.display = 'none';
    document.getElementById('tabReviews').style.display = 'block';
  }
}

function renderGrid(targetId, list) {
  const grid = document.getElementById(targetId);
  if (!grid) return;
  const siteUrl = typeof SITE_URL_JS !== 'undefined' ? SITE_URL_JS : '';
  grid.innerHTML = list.map(p => {
    const discount = (p.oldPrice && p.oldPrice > p.price) ? Math.round((1 - p.price / p.oldPrice) * 100) : 0;
    const discBadge = discount > 0 ? `<span class="ribbon ribbon-discount">-${discount}%</span>` : '';
    const oldPriceHtml = (p.oldPrice && p.oldPrice > p.price) ? `<span style="text-decoration:line-through;color:#aaa;font-size:12px;margin-left:6px">${fmtVND(p.oldPrice)}</span>` : '';
    const newTag = p.isNew ? `<span class="ribbon ribbon-new">MỚI</span>` : '';
    const nameSafe = (p.name || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    const imgSafe  = (p.hinh_anh || '').replace(/'/g, "\\'");
    const imgUrl   = p.image ? `${siteUrl}/${p.image}` : `${siteUrl}/images/ui/default.jpg`;
    return `
    <div class="hero-card prod-card-wrap" style="display:flex;flex-direction:column;cursor:pointer" onclick="window.location.href='${siteUrl}/product-demo.php?id=${p.id}'">
      <div class="hc-icon" style="background:var(--bg-alt,#E1F5EE)">
        <img src="${imgUrl}" alt="${p.name}" onerror="this.src='${siteUrl}/images/ui/default.jpg'">
        ${newTag}${discBadge}
      </div>
      <div class="hc-name" style="margin-top:10px">${p.name}</div>
      <div class="hc-price" style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;margin-bottom:8px">
        ${fmtVND(p.price)}${oldPriceHtml}
      </div>
      <div class="hc-tag" style="margin-bottom:10px">${p.cat}</div>
      <div style="display:flex;gap:8px;margin-top:auto">
        <button class="btn-atc" onclick="event.stopPropagation();addToCart(${p.id},'${nameSafe}',${p.price},'${imgSafe}')" title="Thêm vào giỏ">🛒 Thêm vào giỏ</button>
        <button class="btn-detail" onclick="event.stopPropagation();window.location.href='${siteUrl}/product-demo.php?id=${p.id}'" title="Chi tiết">Chi tiết</button>
      </div>
    </div>`;
  }).join('');
}

/* ═══════════ Đánh giá sao + bình luận thật (api/reviews.php) ═══════════ */
let selectedStars = 0;

function escapeHtmlRv(s) {
  const div = document.createElement('div');
  div.textContent = s || '';
  return div.innerHTML;
}

function timeAgoRv(dateStr) {
  const d = new Date(dateStr.replace(' ', 'T'));
  const diffSec = Math.floor((Date.now() - d.getTime()) / 1000);
  if (diffSec < 60) return 'Vừa xong';
  if (diffSec < 3600) return Math.floor(diffSec / 60) + ' phút trước';
  if (diffSec < 86400) return Math.floor(diffSec / 3600) + ' giờ trước';
  return Math.floor(diffSec / 86400) + ' ngày trước';
}

function initStarPicker() {
  const picker = document.getElementById('starPicker');
  if (!picker) return;
  picker.querySelectorAll('span').forEach(s => {
    s.addEventListener('click', () => {
      selectedStars = parseInt(s.dataset.v, 10);
      picker.querySelectorAll('span').forEach(x => {
        x.classList.toggle('active', parseInt(x.dataset.v, 10) <= selectedStars);
      });
    });
  });
}

function renderReviewCard(r) {
  const isMine = PDP_LOGGED_IN && window.PDP_USER_ID && r.user_id == window.PDP_USER_ID;
  const canDelete = PDP_IS_ADMIN;
  const stars = r.rating ? `<div class="pd-rv-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div>` : '';
  const roleLabel = r.vai_tro === 'admin' ? 'Quản trị viên FROMSHOPWHERE' : 'Khách hàng';

  const repliesHtml = (r.replies || []).map(rp => `
    <div class="pd-rv-card" style="padding:10px 0">
      <div class="pd-rv-header">
        <div class="pd-rv-user">
          <div class="pd-rv-avatar${rp.vai_tro === 'admin' ? ' pd-rv-avatar-admin' : ''}">${escapeHtmlRv(rp.ho_ten[0] || '?')}</div>
          <div>
            <div class="pd-rv-name">${escapeHtmlRv(rp.ho_ten)}</div>
            <div class="pd-rv-role">${rp.vai_tro === 'admin' ? 'Quản trị viên FROMSHOPWHERE' : 'Khách hàng'}</div>
          </div>
        </div>
        <div style="text-align:right"><div class="pd-rv-date">${timeAgoRv(rp.created_at)}</div></div>
      </div>
      <div class="pd-rv-body">${escapeHtmlRv(rp.noi_dung)}</div>
      ${PDP_IS_ADMIN ? `<div class="pd-rv-actions"><button class="pd-rv-delete-btn" onclick="deleteReview(${rp.id})">Xoá</button></div>` : ''}
    </div>
  `).join('');

  return `
    <div class="pd-rv-card" data-id="${r.id}">
      <div class="pd-rv-header">
        <div class="pd-rv-user">
          <div class="pd-rv-avatar${r.vai_tro === 'admin' ? ' pd-rv-avatar-admin' : ''}">${escapeHtmlRv(r.ho_ten[0] || '?')}</div>
          <div>
            <div class="pd-rv-name">${escapeHtmlRv(r.ho_ten)}</div>
            <div class="pd-rv-role">${roleLabel}</div>
          </div>
        </div>
        <div style="text-align:right">
          ${stars}
          <div class="pd-rv-date">${timeAgoRv(r.created_at)}</div>
        </div>
      </div>
      <div class="pd-rv-body">${escapeHtmlRv(r.noi_dung)}</div>
      <div class="pd-rv-actions">
        ${PDP_LOGGED_IN ? `<button class="pd-rv-reply-btn" onclick="toggleReplyForm(${r.id})">Trả lời</button>` : ''}
        ${canDelete ? `<button class="pd-rv-delete-btn" onclick="deleteReview(${r.id})">Xoá</button>` : ''}
      </div>
      ${PDP_LOGGED_IN ? `
        <div class="rv-reply-form" id="replyForm-${r.id}">
          <input type="text" id="replyInput-${r.id}" placeholder="Viết phản hồi...">
          <button onclick="submitReply(${r.id})">Gửi</button>
        </div>` : ''}
      ${repliesHtml ? `<div class="pd-rv-replies">${repliesHtml}</div>` : ''}
    </div>
  `;
}

async function loadReviews() {
  const grid = document.getElementById('reviewGrid');
  if (!grid || typeof CURRENT_PRODUCT === 'undefined') return;
  try {
    const res = await fetch(`api/reviews.php?product_id=${CURRENT_PRODUCT.id}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!data.ok) { grid.innerHTML = '<p style="color:var(--text-muted)">Không tải được đánh giá.</p>'; return; }

    window.PDP_USER_ID = data.user_id;

    const countLabel = document.getElementById('reviewCountLabel');
    if (countLabel) countLabel.textContent = data.count;

    if (!data.reviews.length) {
      grid.innerHTML = '<p style="color:var(--text-muted)">Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên!</p>';
      return;
    }
    grid.innerHTML = data.reviews.map(renderReviewCard).join('');
  } catch (err) {
    console.error('Lỗi tải đánh giá:', err);
    grid.innerHTML = '<p style="color:var(--text-muted)">Không tải được đánh giá.</p>';
  }
}

function toggleReplyForm(id) {
  const f = document.getElementById(`replyForm-${id}`);
  if (f) f.classList.toggle('open');
}

async function submitReview() {
  const text = (document.getElementById('rvText')?.value || '').trim();
  if (selectedStars < 1) { alert('Vui lòng chọn số sao đánh giá'); return; }
  if (!text) { alert('Vui lòng nhập nội dung đánh giá'); return; }

  try {
    const res = await fetch('api/reviews.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
      body: JSON.stringify({ action: 'add', product_id: CURRENT_PRODUCT.id, rating: selectedStars, noi_dung: text })
    });
    const data = await res.json();
    if (!data.ok) { alert(data.error || 'Không thể gửi đánh giá'); return; }
    document.getElementById('rvText').value = '';
    selectedStars = 0;
    document.querySelectorAll('#starPicker span').forEach(x => x.classList.remove('active'));
    loadReviews();
  } catch (err) {
    alert('Có lỗi xảy ra, vui lòng thử lại.');
  }
}

async function submitReply(parentId) {
  const input = document.getElementById(`replyInput-${parentId}`);
  const text = (input?.value || '').trim();
  if (!text) return;
  try {
    const res = await fetch('api/reviews.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
      body: JSON.stringify({ action: 'add', product_id: CURRENT_PRODUCT.id, parent_id: parentId, noi_dung: text })
    });
    const data = await res.json();
    if (!data.ok) { alert(data.error || 'Không thể gửi phản hồi'); return; }
    input.value = '';
    loadReviews();
  } catch (err) {
    alert('Có lỗi xảy ra, vui lòng thử lại.');
  }
}

async function deleteReview(id) {
  if (!confirm('Xoá bình luận/đánh giá này? (sẽ xoá luôn các phản hồi bên trong)')) return;
  try {
    const res = await fetch('api/reviews.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
      body: JSON.stringify({ action: 'delete', id })
    });
    const data = await res.json();
    if (!data.ok) { alert(data.error || 'Không thể xoá'); return; }
    loadReviews();
  } catch (err) {
    alert('Có lỗi xảy ra, vui lòng thử lại.');
  }
}

function buildReviews() {
  initStarPicker();
  loadReviews();
}

function buildRelated() {
  if (typeof RELATED_PRODUCTS === 'undefined') return;
  if (RELATED_PRODUCTS.length === 0) {
    const relWrap = document.querySelector('.pd-related');
    if (relWrap) relWrap.style.display = 'none';
    return;
  }
  renderGrid('relatedGrid', RELATED_PRODUCTS.slice(0, 4));
}

document.addEventListener('DOMContentLoaded', () => {
  if (typeof restoreTheme === 'function') restoreTheme();
  if (typeof updateCartBadge === 'function') updateCartBadge();
  if (typeof updateLoginBtn === 'function') updateLoginBtn();
  if (typeof syncCartPanel === 'function') syncCartPanel();

  buildReviews();
  buildRelated();

  if (typeof recordRecentlyViewed === 'function' && typeof CURRENT_PRODUCT !== 'undefined') {
    recordRecentlyViewed(CURRENT_PRODUCT.id, CURRENT_PRODUCT.name, CURRENT_PRODUCT.price, CURRENT_PRODUCT.img, CURRENT_PRODUCT.cat);
  }
  if (typeof renderRecentlyViewed === 'function') {
    renderRecentlyViewed('recentlyViewedWrap', 'recentlyViewedGrid', CURRENT_PRODUCT.id);
  }
});
