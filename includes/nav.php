<?php
if (!defined('SITE_URL')) require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_navUser    = currentUser();
$_navIsAdmin = isAdmin();
$_navPage    = $currentPage ?? '';
?>

<div class="site-preloader" id="sitePreloader">
  <div class="sp-inner">
    <img src="images/ui/logo.png" alt="FROMSHOPWHERE" class="sp-logo">
    <div class="sp-name">FROMSHOPWHERE</div>
    <div class="sp-tagline">Phần mềm bản quyền chính hãng</div>
    <div class="sp-bar"><span></span></div>
  </div>
</div>
<script>
(function(){
  var pre = document.getElementById('sitePreloader');
  if (!pre) return;
  if (sessionStorage.getItem('fsw-visited-v2')) {
    pre.style.display = 'none';
    return;
  }
  sessionStorage.setItem('fsw-visited-v2', '1');
  var hide = function(){
    pre.classList.add('sp-hide');
    setTimeout(function(){ pre.style.display = 'none'; }, 500);
  };
  window.addEventListener('load', function(){ setTimeout(hide, 900); });
  setTimeout(hide, 3000); // an toàn: tự ẩn dù trang tải lâu
})();
</script>

<div class="cb-toggle" id="cbToggleBtn" title="Hỗ trợ khách hàng" aria-label="Chat hỗ trợ">
  <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
    <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/>
  </svg>
  <span class="cb-toggle-badge">1</span>
</div>

<div class="cb-panel" id="cbPanel">
  <div class="cb-header">
    <div class="cb-header-avatar">🤖</div>
    <div class="cb-header-info">
      <div class="cb-header-name">Trợ lý FROMSHOPWHERE</div>
      <div class="cb-header-status"><span class="cb-dot"></span> Đang hoạt động</div>
    </div>
    <button class="cb-close" id="cbCloseBtn" type="button" aria-label="Đóng chat">✕</button>
  </div>
  <div class="cb-messages" id="cbMessages"></div>
  <form class="cb-form" id="cbForm">
    <input class="cb-input" id="cbInput" type="text" placeholder="Nhập câu hỏi của bạn..." autocomplete="off">
    <button class="cb-send" type="submit" aria-label="Gửi">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
    </button>
  </form>
</div>

<div class="toast" id="toast"></div>

<div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBackdrop(event)">
  <div class="cart-panel">
    <div class="cart-header">
      <h3>Giỏ hàng</h3>
      <button class="cart-clear-btn" id="cartClearBtn" onclick="clearCartConfirm()" style="display:none">Xoá tất cả</button>
      <button class="close-btn" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
      <div class="cart-empty-state">
        <div class="cart-empty-icon">🛒</div>
        <p>Giỏ hàng trống</p>
      </div>
    </div>
    <div class="cart-footer">
      <div class="cart-total">
        <span class="ct-label">Tổng cộng</span>
        <span class="ct-value" id="cartTotal">0đ</span>
      </div>
      <button class="btn-checkout" onclick="window.location.href='checkout.php'">Tiến hành thanh toán →</button>
    </div>
  </div>
</div>

<nav>
  <div class="nav-inner">
    <a class="logo" href="index.php">
      <img src="images/ui/logo.png" alt="FROMSHOPWHERE" class="logo-img-nav logo-img-light">
      <img src="images/ui/logo-dark.png" alt="FROMSHOPWHERE" class="logo-img-nav logo-img-dark">
    </a>

    <ul class="nav-links">
      <li><a href="index.php"    <?= $_navPage==='home'     ? 'class="active"' : '' ?>>Trang chủ</a></li>
      <li><a href="products.php" <?= $_navPage==='products' ? 'class="active"' : '' ?>>Sản phẩm</a></li>
      <li><a href="blog.php"     <?= $_navPage==='blog'     ? 'class="active"' : '' ?>>Blog</a></li>
      <li><a href="contact.php"  <?= $_navPage==='contact'  ? 'class="active"' : '' ?>>Liên hệ</a></li>
      <li class="nav-combo" style="display:flex;align-items:center">
        <a href="faq.php"   <?= $_navPage==='faq'   ? 'class="active"' : '' ?> style="padding-right:6px">Hỏi đáp</a><span style="opacity:.4">/</span><a href="terms.php" <?= $_navPage==='terms' ? 'class="active"' : '' ?> style="padding-left:6px">Điều khoản</a>
      </li>
    </ul>

    <div class="nav-right">
      <form method="get" action="products.php" class="search-wrap" role="search">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input class="search-box" type="search" name="q" placeholder="Tìm phần mềm..."
               value="<?= e($_GET['q'] ?? '') ?>">
      </form>

      <button class="theme-toggle" onclick="toggleTheme()" title="Chuyển sáng/tối" aria-label="Theme">
        <div class="theme-knob" id="themeKnob">☀️</div>
      </button>

      <div class="cart-btn" onclick="toggleCart()">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 01-8 0"/>
        </svg>
        <span class="cart-badge" id="cartCount">0</span>
      </div>

      <?php if ($_navUser): ?>
        <a class="cart-btn" id="navWishlistBtn" href="wishlist.php" title="Yêu thích" aria-label="Yêu thích" style="text-decoration:none;color:inherit">
          <svg class="wl-nav-icon" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z"/>
          </svg>
          <span class="cart-badge" id="wishlistCount" style="display:none">0</span>
        </a>
      <?php endif; ?>

      <?php if ($_navUser): ?>
        <div class="notif-wrap" id="notifWrap">
          <div class="cart-btn" id="notifBtn" onclick="toggleNotif(event)" title="Thông báo" aria-label="Thông báo">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
            <span class="cart-badge notif-badge" id="notifCount" style="display:none">0</span>
          </div>
          <div class="user-dropdown notif-dropdown" id="notifDropdown">
            <div class="notif-header">
              <span>Thông báo</span>
              <button type="button" class="notif-markall" onclick="markAllNotifRead()">Đánh dấu đã đọc</button>
            </div>
            <div class="notif-list" id="notifList">
              <div class="notif-empty">Chưa có thông báo nào.</div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($_navUser): ?>
        <div class="nav-user-wrap">
          <a class="btn-login nav-user-btn" href="profile.php">
            <span>👤</span> <?= e($_navUser['ho_ten']) ?>
          </a>
          <div class="user-dropdown nav-user-menu">
            <?php if ($_navIsAdmin): ?>
              <a href="admin/">⚙️ Quản trị Admin</a>
            <?php endif; ?>
            <a href="profile.php">👤 Tài khoản</a>
            <a href="logout.php">🚪 Đăng xuất</a>
          </div>
        </div>
      <?php else: ?>
        <a class="btn-login" href="login.php">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<link rel="stylesheet" href="assets/css/notifications.css">
<link rel="stylesheet" href="assets/css/coupon-popup.css">
<script src="assets/js/shared.js"></script>
<script src="assets/js/nav-init.js"></script>
<script src="assets/js/chatbot.js"></script>
<script src="assets/js/coupon-popup.js"></script>
<?php if ($_navUser): ?>
<script>window.FSW_IS_LOGGED_IN = true;</script>
<script src="assets/js/notifications.js"></script>
<?php endif; ?>
