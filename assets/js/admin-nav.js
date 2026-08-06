/* ═══════════════════════════════════════════
   FROMSHOPWHERE — admin-nav.js
   Điều hướng mượt giữa các trang/mục trong khu vực
   Admin bằng AJAX (không tải lại toàn bộ trang).
   Chỉ chạy trong /admin — tự bỏ qua các link ra ngoài,
   link mở tab mới, đăng xuất, hoặc khi fetch lỗi (sẽ
   tự động rơi về điều hướng thường như bình thường).
═══════════════════════════════════════════ */
(function () {
  if (typeof ADMIN_NAV_BASE === 'undefined') return;

  var ADM_BASE = ADMIN_NAV_BASE.replace(/\/+$/, '');
  var ADM_PATH = (function () {
    try { return new URL(ADM_BASE, location.href).pathname.replace(/\/+$/, ''); }
    catch (e) { return '/admin'; }
  })();
  var currentController = null;
  var bar = null;

  /* ── các script (src) đã từng được nạp/chạy, tránh chạy lại (gây
     lỗi khai báo trùng const/let và chồng lặp event listener khi
     quay lại đúng trang nhiều lần) ── */
  var loadedScriptSrcs = {};
  (function seedLoadedScripts() {
    Array.prototype.forEach.call(document.querySelectorAll('script[src]'), function (s) {
      try { loadedScriptSrcs[new URL(s.src, location.href).href] = true; } catch (e) {}
    });
  })();

  /* ── thanh tiến trình mỏng ở đầu trang ── */
  function ensureBar() {
    if (bar) return bar;
    bar = document.createElement('div');
    bar.id = 'admNavBar';
    Object.assign(bar.style, {
      position: 'fixed', top: '0', left: '0', height: '3px', width: '0%',
      background: 'var(--green,#16a34a)', zIndex: '99999',
      transition: 'width .25s ease, opacity .25s ease', opacity: '0'
    });
    document.body.appendChild(bar);
    return bar;
  }
  function barStart() {
    var b = ensureBar();
    b.style.transition = 'none';
    b.style.opacity = '1';
    b.style.width = '0%';
    void b.offsetWidth;
    b.style.transition = 'width .35s ease, opacity .25s ease';
    b.style.width = '70%';
  }
  function barDone(ok) {
    if (!bar) return;
    bar.style.width = '100%';
    bar.style.background = ok ? 'var(--green,#16a34a)' : '#c0392b';
    setTimeout(function () { if (bar) bar.style.opacity = '0'; }, 180);
    setTimeout(function () { if (bar) { bar.style.width = '0%'; bar.style.background = 'var(--green,#16a34a)'; } }, 450);
  }

  function isInAdmin(pathname) {
    return pathname === ADM_PATH || pathname.indexOf(ADM_PATH + '/') === 0;
  }

  function qualifyingLink(a) {
    if (!a || !a.getAttribute) return null;
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#') return null;
    if (/^(javascript:|mailto:|tel:)/i.test(href)) return null;
    if (a.target && a.target !== '' && a.target !== '_self') return null;
    if (a.hasAttribute('download')) return null;
    if (a.dataset && a.dataset.noAjax !== undefined) return null;
    var url;
    try { url = new URL(href, location.href); } catch (e) { return null; }
    if (url.origin !== location.origin) return null;
    if (!isInAdmin(url.pathname)) return null;
    if (/logout\.php$/.test(url.pathname)) return null;
    return url;
  }

  /* Chỉ AJAX-hoá form GET (tìm kiếm/lọc). Form POST (lưu/xoá/ẩn-hiện...) submit
     bình thường (tải lại trang) — an toàn hơn, tránh ảnh hưởng dây chuyền tới
     toàn bộ khu Admin (upload ảnh, các trang khác, v.v.) */
  function qualifyingForm(form) {
    if (!form || !form.getAttribute) return null;
    if (form.dataset && form.dataset.noAjax !== undefined) return null;
    var method = (form.getAttribute('method') || 'get').toLowerCase();
    if (method !== 'get') return null;
    var action = form.getAttribute('action') || location.href;
    var url;
    try { url = new URL(action, location.href); } catch (e) { return null; }
    if (url.origin !== location.origin) return null;
    if (!isInAdmin(url.pathname)) return null;

    var fd = new FormData(form);
    var params = new URLSearchParams();
    fd.forEach(function (v, k) { if (v !== '') params.append(k, v); });
    url.search = params.toString();
    return { url: url, method: 'get', body: null };
  }

  function runScripts(container) {
    var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
    scripts.forEach(function (old) {
      if (old.src) {
        var abs;
        try { abs = new URL(old.src, location.href).href; } catch (e) { abs = old.src; }
        if (loadedScriptSrcs[abs]) return; // đã chạy rồi, bỏ qua để không đăng ký lại listener/khai báo trùng
        loadedScriptSrcs[abs] = true;
      }
      var s = document.createElement('script');
      for (var i = 0; i < old.attributes.length; i++) {
        s.setAttribute(old.attributes[i].name, old.attributes[i].value);
      }
      s.text = old.textContent;
      old.parentNode.replaceChild(s, old);
    });
  }

  function closeMobileSidebar() {
    var side = document.querySelector('.adm-side');
    var backdrop = document.querySelector('.adm-side-backdrop');
    if (side) side.classList.remove('open');
    if (backdrop) backdrop.classList.remove('open');
  }

  function syncHeadStylesheets(doc) {
    var have = {};
    Array.prototype.forEach.call(document.querySelectorAll('link[rel="stylesheet"][href]'), function (l) {
      try { have[new URL(l.getAttribute('href'), location.href).href] = true; } catch (e) {}
    });
    Array.prototype.forEach.call(doc.querySelectorAll('head link[rel="stylesheet"][href]'), function (l) {
      var href = l.getAttribute('href');
      var abs;
      try { abs = new URL(href, location.href).href; } catch (e) { return; }
      if (have[abs]) return;
      have[abs] = true;
      var newLink = document.createElement('link');
      newLink.rel = 'stylesheet';
      newLink.href = href;
      document.head.appendChild(newLink);
    });
  }

  function loadPage(url, opts) {
    opts = opts || {};
    var push = opts.push !== false;
    var scrollTop = opts.scrollTop !== false;
    var urlStr = url.toString();

    if (currentController) currentController.abort();
    var controller = new AbortController();
    currentController = controller;

    barStart();
    var main = document.querySelector('.adm-main');
    if (main) main.style.opacity = '.5';

    fetch(urlStr, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      signal: controller.signal
    }).then(function (res) {
      if (!res.ok) throw new Error('bad-status');
      return res.text();
    }).then(function (html) {
      var doc = new DOMParser().parseFromString(html, 'text/html');
      var newAdm = doc.querySelector('.adm');
      if (!newAdm) throw new Error('not-admin-page'); // vd: bị đá về trang login

      document.title = doc.title || document.title;
      syncHeadStylesheets(doc);
      document.body.innerHTML = doc.body.innerHTML;
      runScripts(document.body);

      if (push) history.pushState({ admNav: true }, '', urlStr);
      else history.replaceState({ admNav: true }, '', urlStr);
      if (scrollTop) window.scrollTo(0, 0);
      closeMobileSidebar();
      barDone(true);
    }).catch(function (err) {
      if (err.name === 'AbortError') return;
      // fallback: điều hướng bình thường nếu AJAX thất bại (vd hết phiên đăng nhập)
      barDone(false);
      window.location.href = urlStr;
    });
  }

  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var a = e.target.closest('a');
    var url = qualifyingLink(a);
    if (!url) return;
    e.preventDefault();
    if (url.toString() === location.href) return;
    loadPage(url, { push: true });
  });

  document.addEventListener('submit', function (e) {
    // QUAN TRỌNG: nếu một handler khác (vd onsubmit="return confirm(...)"
    // khi xoá bài viết) đã huỷ sự kiện — ví dụ người dùng bấm Cancel —
    // thì tuyệt đối không được tự ý submit thay. Đây là lỗi trước đây khiến
    // bấm "Huỷ" ở hộp thoại xác nhận xoá vẫn xoá bài viết như thường.
    if (e.defaultPrevented) return;
    var form = e.target;
    var q = qualifyingForm(form);
    if (!q) return;
    e.preventDefault();
    loadPage(q.url, { push: true, scrollTop: true, method: 'get' });
  });

  window.addEventListener('popstate', function () {
    loadPage(new URL(location.href), { push: false, scrollTop: false });
  });

  if (!history.state) history.replaceState({ admNav: true }, '', location.href);
})();
