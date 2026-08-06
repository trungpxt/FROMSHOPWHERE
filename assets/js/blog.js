document.addEventListener('DOMContentLoaded', () => {
  restoreTheme(); updateCartBadge(); syncCartPanel();
  initBlogAjaxFilter();
});

/* ══ Lọc bài viết theo danh mục bằng AJAX ══
   Bấm chọn danh mục (pill sidebar hoặc dropdown) chỉ cập nhật lại
   phần danh sách bài viết bên trái, KHÔNG tải lại cả trang. */
function initBlogAjaxFilter() {
  const getWrap = () => document.getElementById('blog-post-list-wrap');
  if (!getWrap()) return; // không phải trang blog.php

  function toAjaxUrl(url) {
    return url + (url.indexOf('?') > -1 ? '&' : '?') + 'ajax=1';
  }

  function syncActiveStates(filterTag) {
    // Pill danh mục ở sidebar
    document.querySelectorAll('.tc-item').forEach(a => {
      const isActive = (a.dataset.tag || '') === filterTag;
      a.style.color = isActive ? a.dataset.color : '';
      a.style.borderColor = isActive ? a.dataset.color + '44' : '';
    });
    // Dropdown lọc nhanh phía trên
    const sel = document.querySelector('.ftag-select');
    if (sel) {
      Array.from(sel.options).forEach(opt => {
        opt.selected = (opt.dataset.tag || '') === filterTag;
      });
    }
  }

  function loadPosts(url, pushHistory) {
    const wrap = getWrap();
    if (!wrap) return;
    wrap.style.transition = 'opacity .15s';
    wrap.style.opacity = '.45';

    fetch(toAjaxUrl(url), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(data => {
        wrap.outerHTML = data.html;
        const newWrap = getWrap();
        if (newWrap) {
          newWrap.style.opacity = '0';
          requestAnimationFrame(() => {
            newWrap.style.transition = 'opacity .2s';
            newWrap.style.opacity = '1';
          });
        }
        syncActiveStates(data.filter || '');
        if (pushHistory) history.pushState({ blogAjax: true }, '', url);
      })
      .catch(() => { window.location.href = url; }); // lỗi mạng -> vẫn hoạt động bình thường
  }

  // Bấm vào pill danh mục ở sidebar, hoặc link "Xem tất cả" khi rỗng kết quả
  document.addEventListener('click', function (e) {
    const a = e.target.closest('.tag-cloud a, .blog-empty a[href^="blog.php"]');
    if (!a) return;
    e.preventDefault();
    loadPosts(a.getAttribute('href'), true);
  });

  // Chọn danh mục trong dropdown lọc nhanh phía trên
  const sel = document.querySelector('.ftag-select');
  if (sel) {
    sel.addEventListener('change', function () {
      if (this.value) loadPosts(this.value, true);
    });
  }

  // Nút Back/Forward của trình duyệt
  window.addEventListener('popstate', function () {
    loadPosts(location.pathname + location.search, false);
  });
}
