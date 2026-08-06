<?php
if (!defined('SITE_URL')) { header('HTTP/1.0 403 Forbidden'); exit; }
// sidebar.php — dùng chung cho tất cả trang admin
$cur = basename($_SERVER['PHP_SELF']);
if (!function_exists('navActive')) {
    function navActive(string $page, string $cur): string {
        return strpos($cur, $page) !== false ? 'on' : '';
    }
}
?>
<aside class="adm-side">
  <div class="adm-logo">
    <img src="<?= SITE_URL ?>/images/ui/logo.png" alt="FSW" class="logo-img-light" style="height:34px">
    <img src="<?= SITE_URL ?>/images/ui/logo-dark.png" alt="FSW" class="logo-img-dark" style="height:34px">
  </div>
  <nav class="adm-nav">
    <div class="adm-nav-label">Quản lý</div>
    <a href="<?= SITE_URL ?>/admin/" class="<?= $cur==='index.php'?'on':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>
    <a href="<?= SITE_URL ?>/admin/statistics.php" class="<?= navActive('statistics',$cur) ?>">
      <span class="nav-icon">📈</span> Thống kê
    </a>
    <a href="<?= SITE_URL ?>/admin/products.php" class="<?= navActive('products',$cur) ?>">
      <span class="nav-icon">📦</span> Sản phẩm
    </a>
    <a href="<?= SITE_URL ?>/admin/orders.php" class="<?= navActive('orders',$cur) ?>">
      <span class="nav-icon">🛒</span> Đơn hàng
    </a>
    <a href="<?= SITE_URL ?>/admin/users.php" class="<?= navActive('users',$cur) ?>">
      <span class="nav-icon">👥</span> Người dùng
    </a>
    <a href="<?= SITE_URL ?>/admin/posts.php" class="<?= navActive('posts',$cur) ?>">
      <span class="nav-icon">✍️</span> Bài đăng
    </a>
    <a href="<?= SITE_URL ?>/admin/categories.php" class="<?= navActive('categories',$cur) ?>">
      <span class="nav-icon">🗂️</span> Danh mục
    </a>
    <a href="<?= SITE_URL ?>/admin/contacts.php" class="<?= navActive('contacts',$cur) ?>" style="position:relative">
      <span class="nav-icon">📩</span> Tin nhắn
      <?php
      try {
        $unread_cnt = (int)db()->query("SELECT COUNT(*) FROM contact_messages WHERE trang_thai='chua_doc'")->fetchColumn();
        if($unread_cnt > 0) echo "<span class='nav-badge'>$unread_cnt</span>";
      } catch(Exception $e) {}
      ?>
    </a>
    <a href="<?= SITE_URL ?>/admin/coupons.php" class="<?= navActive('coupons',$cur) ?>">
      <span class="nav-icon">🎁</span> Mã giảm giá
    </a>
    <a href="<?= SITE_URL ?>/admin/chatbot-faq.php" class="<?= navActive('chatbot-faq',$cur) ?>">
      <span class="nav-icon">🤖</span> FAQ Chatbot
    </a>
    <a href="<?= SITE_URL ?>/admin/chatbot-log.php" class="<?= navActive('chatbot-log',$cur) ?>">
      <span class="nav-icon">❓</span> Chất lượng Chatbot
    </a>
    <div class="adm-sep"></div>
    <div class="adm-nav-group" id="admLinkGroup">
      <button type="button" class="adm-nav-toggle" onclick="document.getElementById('admLinkGroup').classList.toggle('open')">
        <span class="nav-icon">🔗</span> Liên kết <span class="adm-nav-caret">▾</span>
      </button>
      <div class="adm-nav-sub">
        <a href="<?= SITE_URL ?>/blog.php"><span class="nav-icon">📰</span> Xem Blog</a>
        <a href="<?= SITE_URL ?>/index.php"><span class="nav-icon">🌐</span> Xem website</a>
        <a href="<?= SITE_URL ?>/logout.php"><span class="nav-icon">🚪</span> Đăng xuất</a>
      </div>
    </div>
  </nav>
  <div class="adm-user">
    <div class="adm-avatar"><?= strtoupper(mb_substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
    <div>
      <div class="adm-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
      <div class="adm-user-role">Administrator</div>
    </div>
  </div>
</aside>
