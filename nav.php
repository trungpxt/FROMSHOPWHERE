<?php
/* includes/nav.php — Nav dùng chung, tự nhận biết trạng thái đăng nhập */
if (!defined('SITE_URL')) require_once __DIR__ . '/config.php';
startSession();
$_user     = currentUser();
$_cartCount = 0; // Cart vẫn dùng localStorage phía JS
$_currentPage = $currentPage ?? '';
?>
<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<!-- ── CART OVERLAY ── -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBackdrop(event)">
  <div class="cart-panel">
    <div class="cart-header">
      <h3>Giỏ hàng</h3>
      <button class="close-btn" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
      <div style="text-align:center;padding:48px 0">
        <div style="font-size:40px;margin-bottom:12px">🛒</div>
        <p style="color:var(--text-muted);font-size:14px">Giỏ hàng trống</p>
      </div>
    </div>
    <div class="cart-footer">
      <div class="cart-total">
        <span class="ct-label">Tổng cộng</span>
        <span class="ct-value" id="cartTotal">0đ</span>
      </div>
      <button class="btn-checkout" onclick="window.location.href='<?= SITE_URL ?>/checkout.php'">Tiến hành thanh toán →</button>
    </div>
  </div>
</div>

<!-- ══ NAV ══ -->
<nav>
  <div class="nav-inner">
    <a class="logo" href="<?= SITE_URL ?>/index.php">
      <img src="<?= SITE_URL ?>/images/logo.png" alt="FROMSHOPWHERE"
           style="height:44px;width:auto;object-fit:contain;filter:drop-shadow(0 0 6px rgba(0,0,0,.3))">
    </a>

    <ul class="nav-links">
      <li><a href="<?= SITE_URL ?>/index.php"    <?= $_currentPage==='home'     ?'class="active"':'' ?>>Trang chủ</a></li>
      <li><a href="<?= SITE_URL ?>/products.php" <?= $_currentPage==='products' ?'class="active"':'' ?>>Sản phẩm</a></li>
      <li><a href="<?= SITE_URL ?>/blog.php"     <?= $_currentPage==='blog'     ?'class="active"':'' ?>>Blog</a></li>
      <li><a href="<?= SITE_URL ?>/contact.php"  <?= $_currentPage==='contact'  ?'class="active"':'' ?>>Liên hệ</a></li>
    </ul>

    <div class="nav-right">
      <div class="search-wrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input class="search-box" type="search" placeholder="Tìm phần mềm..."
               onkeydown="if(event.key==='Enter')window.location.href='<?= SITE_URL ?>/products.php?q='+encodeURIComponent(this.value)">
      </div>

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

      <?php if ($_user): ?>
        <!-- Đã đăng nhập → hiện tên + dropdown -->
        <div style="position:relative">
          <button class="btn-login"
                  onclick="document.getElementById('userMenu').classList.toggle('open')"
                  style="cursor:pointer;display:flex;align-items:center;gap:6px">
            <span style="font-size:16px">👤</span>
            <?= e($_user['ho_ten']) ?> <span style="font-size:10px;opacity:.7">▾</span>
          </button>
          <div id="userMenu" class="user-dropdown">
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/">⚙️ Quản trị Admin</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/profile.php">👤 Tài khoản</a>
            <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
          </div>
        </div>
      <?php else: ?>
        <!-- Chưa đăng nhập -->
        <a class="btn-login" href="<?= SITE_URL ?>/login.php">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<style>
.user-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:6px;min-width:170px;box-shadow:0 8px 32px rgba(0,0,0,.2);z-index:300;display:none;flex-direction:column;gap:2px}
.user-dropdown.open{display:flex}
.user-dropdown a{padding:9px 13px;border-radius:8px;text-decoration:none;color:var(--text);font-size:13px;font-weight:500;transition:background .12s}
.user-dropdown a:hover{background:var(--bg-alt);color:var(--green-600,#0A8A4C)}
</style>
<script>
document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});
</script>
