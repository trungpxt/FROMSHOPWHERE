/* ── Popup mã giảm giá khi khách vừa vào web (1 lần / phiên truy cập) ── */
(function () {
  if (sessionStorage.getItem('fsw-coupon-popup-shown')) return;

  // Không hiện ở trang thanh toán (khách đang bận mua, không cần làm phiền)
  var skipPages = ['checkout.php', 'checkout-buynow.php', 'login.php'];
  var path = location.pathname.split('/').pop() || 'index.php';
  if (skipPages.includes(path)) return;

  sessionStorage.setItem('fsw-coupon-popup-shown', '1');

  fetch('api/popup-coupon.php', { method: 'POST' })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.ok) return;
      showCouponPopup(data.code, data.percent, data.is_returning);
    })
    .catch(function () { /* im lặng bỏ qua nếu lỗi mạng */ });

  function showCouponPopup(code, percent, isReturning) {
    var overlay = document.createElement('div');
    overlay.className = 'cp-overlay';
    overlay.id = 'couponPopupOverlay';
    var title = isReturning ? 'Cảm ơn bạn đã quay lại! 💜' : 'Chào mừng bạn đến FROMSHOPWHERE!';
    var sub = isReturning
      ? 'Đây là mã giảm giá dành riêng cho lần mua tiếp theo của bạn hôm nay:'
      : 'Nhận ngay mã giảm giá dưới đây cho đơn hàng đầu tiên hôm nay:';
    overlay.innerHTML =
      '<div class="cp-box">' +
        '<button class="cp-close" onclick="document.getElementById(\'couponPopupOverlay\').remove()">✕</button>' +
        '<div class="cp-emoji">🎁</div>' +
        '<h3 class="cp-title">' + title + '</h3>' +
        '<p class="cp-sub">' + sub + '</p>' +
        '<div class="cp-code-box">' +
          '<div class="cp-code" id="cpCodeText">' + code + '</div>' +
          '<div class="cp-percent">Giảm ' + percent + '% · Có hiệu lực 7 ngày</div>' +
        '</div>' +
        '<div class="cp-actions">' +
          '<button class="cp-btn cp-btn-copy" id="cpCopyBtn">📋 Sao chép mã</button>' +
          '<button class="cp-btn cp-btn-shop" onclick="location.href=\'products.php\'">🛍️ Mua ngay</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(overlay);
    requestAnimationFrame(function () { overlay.classList.add('show'); });

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.remove();
    });

    document.getElementById('cpCopyBtn').addEventListener('click', function () {
      navigator.clipboard.writeText(code).then(function () {
        var btn = document.getElementById('cpCopyBtn');
        btn.textContent = '✓ Đã sao chép!';
        setTimeout(function () { btn.textContent = '📋 Sao chép mã'; }, 1800);
      });
    });
  }
})();
