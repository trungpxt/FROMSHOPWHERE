<?php
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
    <img src="<?= SITE_URL ?>/images/logo.png" alt="FSW" style="height:34px">
  </div>
  <nav class="adm-nav">
    <div class="adm-nav-label">Quản lý</div>
    <a href="<?= SITE_URL ?>/admin/" class="<?= $cur==='index.php'?'on':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
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
    <div class="adm-sep"></div>
    <div class="adm-nav-label">Liên kết</div>
    <a href="<?= SITE_URL ?>/blog.php" target="_blank">
      <span class="nav-icon">📰</span> Xem Blog
    </a>
    <a href="<?= SITE_URL ?>/index.php" target="_blank">
      <span class="nav-icon">🌐</span> Xem website
    </a>
    <a href="<?= SITE_URL ?>/logout.php">
      <span class="nav-icon">🚪</span> Đăng xuất
    </a>
  </nav>
  <div class="adm-user">
    <div class="adm-avatar"><?= strtoupper(mb_substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
    <div>
      <div class="adm-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
      <div class="adm-user-role">Administrator</div>
    </div>
  </div>
</aside>
