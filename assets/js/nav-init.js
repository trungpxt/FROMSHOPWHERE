document.addEventListener('DOMContentLoaded', function() {
  restoreTheme();
  updateCartBadge();
  syncCartPanel();
  if (window.initFswEffects) window.initFswEffects();
  initUserMenuDelay();
});

/* Dropdown "Tài khoản" trên nav vốn chỉ mở bằng CSS :hover, nên đóng lại
   ngay lập tức khi chuột rời khỏi nút hoặc đi lệch ra khỏi khoảng cách
   nhỏ giữa nút và menu -> rất dễ bấm hụt. Thêm độ trễ ~600ms trước khi
   đóng thật sự, để người dùng có thời gian di chuột xuống bấm mục menu. */
function initUserMenuDelay() {
  document.querySelectorAll('.nav-user-wrap').forEach(function (wrap) {
    var closeTimer = null;
    var open = function () {
      clearTimeout(closeTimer);
      wrap.classList.add('nav-user-open');
    };
    var scheduleClose = function () {
      clearTimeout(closeTimer);
      closeTimer = setTimeout(function () {
        wrap.classList.remove('nav-user-open');
      }, 600);
    };
    wrap.addEventListener('mouseenter', open);
    wrap.addEventListener('mouseleave', scheduleClose);
    wrap.addEventListener('focusin', open);
    wrap.addEventListener('focusout', scheduleClose);
  });
}
