/* assets/js/products-filter.js
 * Lọc danh mục + sắp xếp + tìm kiếm trên trang products.php bằng AJAX,
 * không tải lại trang. Đồng bộ URL bằng history.pushState để vẫn
 * bookmark/back-forward được bình thường.
 */
(function () {
  var grid = document.getElementById('productsGrid');
  if (!grid) return; // không phải trang products.php -> bỏ qua

  var catList  = document.getElementById('catFilterList');
  var sortList = document.getElementById('sortFilterList');
  var priceMinInput = document.getElementById('priceMinInput');
  var priceMaxInput = document.getElementById('priceMaxInput');
  var priceApplyBtn = document.getElementById('priceApplyBtn');
  var hint     = document.getElementById('searchResultHint');
  var hintTerm = document.getElementById('searchResultTerm');
  var hintCount = document.getElementById('searchResultCount');
  var countBar = document.getElementById('productsCountBar');
  var countNum = document.getElementById('productsCountNum');
  var countCatSpan = document.getElementById('productsCountCat');
  var searchInput = document.querySelector('.search-box');
  var searchForm  = searchInput ? searchInput.closest('form') : null;
  var pagination  = document.getElementById('productsPagination');

  var state = {
    cat:  grid.dataset.cat  || 'all',
    sort: grid.dataset.sort || 'pop',
    q:    grid.dataset.q    || '',
    pmin: grid.dataset.pmin || '',
    pmax: grid.dataset.pmax || '',
    page: parseInt(grid.dataset.page, 10) || 1,
  };

  var debounceTimer = null;
  var activeRequestId = 0;

  function buildQuery(s) {
    var params = {};
    if (s.cat && s.cat !== 'all') params.cat = s.cat;
    if (s.sort && s.sort !== 'pop') params.sort = s.sort;
    if (s.q) params.q = s.q;
    if (s.pmin !== '' && s.pmin != null) params.pmin = s.pmin;
    if (s.pmax !== '' && s.pmax != null) params.pmax = s.pmax;
    if (s.page && s.page > 1) params.page = s.page;
    var qs = new URLSearchParams(params).toString();
    return qs;
  }

  function buildUrl(s) {
    var qs = buildQuery(s);
    return 'products.php' + (qs ? '?' + qs : '');
  }

  function setActive(container, attr, value) {
    if (!container) return;
    var links = container.querySelectorAll('a[' + attr + ']');
    links.forEach(function (a) {
      a.classList.toggle('active', a.getAttribute(attr) === value);
    });
  }

  function applyFilter(nextState, opts) {
    opts = opts || {};
    // Đổi bộ lọc/sắp xếp/tìm kiếm/giá (không tự chỉ định trang) -> luôn về trang 1.
    // Khi bấm phân trang hoặc khôi phục state (back/forward), nextState.page đã
    // được chỉ định rõ -> giữ nguyên, không ép về 1.
    var hasExplicitPage = Object.prototype.hasOwnProperty.call(nextState, 'page');
    if (!hasExplicitPage) nextState.page = 1;

    state = Object.assign({}, state, nextState);

    var requestId = ++activeRequestId;
    grid.classList.add('is-loading');

    var qs = buildQuery(state);
    fetch('api/products-filter.php' + (qs ? '?' + qs : ''), { credentials: 'same-origin' })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (requestId !== activeRequestId) return; // có request mới hơn đã bắn ra, bỏ kết quả cũ
        if (!data.ok) {
          if (window.showToast) showToast(data.error || 'Không tải được sản phẩm.');
          return;
        }

        grid.innerHTML = data.html || '';
        if (pagination) pagination.innerHTML = data.pagination || '';
        state.page = data.page || 1;

        if (hint) {
          if (state.q) {
            hint.style.display = '';
            if (hintTerm) hintTerm.textContent = state.q;
            if (hintCount) hintCount.textContent = data.total;
          } else {
            hint.style.display = 'none';
          }
        }
        if (countBar) {
          if (state.q) {
            countBar.style.display = 'none';
          } else {
            countBar.style.display = '';
            if (countNum) countNum.textContent = data.total;
            if (countCatSpan) {
              countCatSpan.textContent = (state.cat && state.cat !== 'all') ? ' trong danh mục "' + state.cat + '"' : '';
            }
          }
        }

        setActive(catList, 'data-cat', state.cat);
        setActive(sortList, 'data-sort', state.sort);

        var newUrl = buildUrl(state);
        if (opts.pushHistory !== false) {
          history.pushState({ fsw: state }, '', newUrl);
        } else {
          history.replaceState({ fsw: state }, '', newUrl);
        }

        if (hasExplicitPage) {
          grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      })
      .catch(function () {
        if (window.showToast) showToast('Có lỗi khi lọc sản phẩm, vui lòng thử lại.');
      })
      .finally(function () {
        if (requestId === activeRequestId) grid.classList.remove('is-loading');
      });
  }

  // --- Click vào danh mục (sidebar) ---
  if (catList) {
    catList.addEventListener('click', function (e) {
      var a = e.target.closest('a[data-cat]');
      if (!a) return;
      e.preventDefault();
      applyFilter({ cat: a.getAttribute('data-cat') });
    });
  }

  // --- Click vào tuỳ chọn sắp xếp ---
  if (sortList) {
    sortList.addEventListener('click', function (e) {
      var a = e.target.closest('a[data-sort]');
      if (!a) return;
      e.preventDefault();
      applyFilter({ sort: a.getAttribute('data-sort') });
    });
  }

  // --- Khoảng giá tự nhập (khách tự quyết định từ mức nào tới mức nào) ---
  function applyPriceRange() {
    var minVal = priceMinInput ? priceMinInput.value.trim() : '';
    var maxVal = priceMaxInput ? priceMaxInput.value.trim() : '';
    applyFilter({ pmin: minVal, pmax: maxVal });
  }
  if (priceApplyBtn) {
    priceApplyBtn.addEventListener('click', applyPriceRange);
  }
  [priceMinInput, priceMaxInput].forEach(function (inp) {
    if (!inp) return;
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); applyPriceRange(); }
    });
  });
  var priceClearBtn = document.getElementById('priceClearBtn');
  if (priceClearBtn) {
    priceClearBtn.addEventListener('click', function (e) {
      e.preventDefault();
      if (priceMinInput) priceMinInput.value = '';
      if (priceMaxInput) priceMaxInput.value = '';
      applyFilter({ pmin: '', pmax: '' });
    });
  }

  // --- Thanh tìm kiếm trên nav: gõ tới đâu lọc tới đó (debounce) ---
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      var val = searchInput.value;
      debounceTimer = setTimeout(function () {
        applyFilter({ q: val }, { pushHistory: false });
      }, 350);
    });
  }

  // Enter trong ô tìm kiếm -> lọc ngay, không submit/reload trang
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      clearTimeout(debounceTimer);
      applyFilter({ q: searchInput.value });
    });
  }

  // --- Click vào thanh phân trang (dùng event delegation vì #productsPagination
  //     được thay nội dung mới sau mỗi lần lọc) ---
  if (pagination) {
    pagination.addEventListener('click', function (e) {
      var a = e.target.closest('a[data-page]');
      if (!a) return;
      e.preventDefault();
      applyFilter({ page: parseInt(a.getAttribute('data-page'), 10) || 1 });
    });
  }

  // --- Nút back/forward trình duyệt ---
  window.addEventListener('popstate', function (e) {
    var s = (e.state && e.state.fsw) || {
      cat: new URLSearchParams(location.search).get('cat') || 'all',
      sort: new URLSearchParams(location.search).get('sort') || 'pop',
      q: new URLSearchParams(location.search).get('q') || '',
      pmin: new URLSearchParams(location.search).get('pmin') || '',
      pmax: new URLSearchParams(location.search).get('pmax') || '',
      page: parseInt(new URLSearchParams(location.search).get('page'), 10) || 1,
    };
    if (priceMinInput) priceMinInput.value = s.pmin || '';
    if (priceMaxInput) priceMaxInput.value = s.pmax || '';
    applyFilter(s, { pushHistory: false });
  });
})();
