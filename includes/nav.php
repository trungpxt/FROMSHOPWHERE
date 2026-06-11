<?php
if (!defined('SITE_URL')) require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_navUser    = currentUser();
$_navIsAdmin = isAdmin();
$_navPage    = $currentPage ?? '';
?>

<div class="toast" id="toast"></div>

<div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBackdrop(event)">
  <div class="cart-panel">
    <div class="cart-header">
      <h3>Giỏ hàng</h3>
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
      <img src="images/logo.png" alt="FROMSHOPWHERE" class="logo-img-nav">
    </a>

    <ul class="nav-links">
      <li><a href="index.php"    <?= $_navPage==='home'     ? 'class="active"' : '' ?>>Trang chủ</a></li>
      <li><a href="products.php" <?= $_navPage==='products' ? 'class="active"' : '' ?>>Sản phẩm</a></li>
      <li><a href="blog.php"     <?= $_navPage==='blog'     ? 'class="active"' : '' ?>>Blog</a></li>
      <li><a href="contact.php"  <?= $_navPage==='contact'  ? 'class="active"' : '' ?>>Liên hệ</a></li>
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

<script src="shared.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  restoreTheme();
  updateCartBadge();
  syncCartPanel();
});
</script>
