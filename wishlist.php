<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/product-card.php';
startSession();
$currentPage = 'wishlist';
$_user = currentUser();

if (!$_user) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode('/wishlist.php'));
}

try {
    db()->exec("CREATE TABLE IF NOT EXISTS wishlist (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        product_id  INT NOT NULL,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_product (user_id, product_id),
        INDEX idx_user (user_id),
        CONSTRAINT fk_wl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_wl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

$stmt = db()->prepare(
    "SELECT p.*, c.ten_danh_muc,
            COALESCE(r.avg_rating, 0) AS avg_rating,
            COALESCE(r.rating_count, 0) AS rating_count
     FROM wishlist w
     JOIN products p ON p.id = w.product_id
     JOIN categories c ON p.danh_muc_id = c.id
     LEFT JOIN (
         SELECT product_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS rating_count
         FROM product_reviews WHERE rating IS NOT NULL GROUP BY product_id
     ) r ON r.product_id = p.id
     WHERE w.user_id = ?
     ORDER BY w.created_at DESC"
);
$stmt->execute([$_user['id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sản phẩm yêu thích — FROMSHOPWHERE</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/wishlist.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">✓ Chính hãng 100%</span></div>
    <h1>♥ Sản phẩm yêu thích</h1>
    <p><?= count($items) ?> sản phẩm bạn đã lưu để mua sau</p>
  </div>
</div>

<div class="section">
  <?php if (empty($items)): ?>
    <div class="wl-empty">
      <div class="wl-empty-icon">♡</div>
      <h3>Chưa có sản phẩm yêu thích</h3>
      <p>Bấm biểu tượng ♥ trên bất kỳ sản phẩm nào để lưu lại đây.</p>
      <a class="btn-detail" href="products.php">Khám phá sản phẩm →</a>
    </div>
  <?php else: ?>
    <div class="products" id="wishlistGrid">
      <?php foreach ($items as $p) renderProductCard($p, 'grid'); ?>
    </div>
  <?php endif; ?>
</div>

<script>
/* Khi bỏ thích ngay trên trang này, xoá thẻ sản phẩm khỏi giao diện */
function onWishlistRemoved(productId) {
  const btn = document.querySelector('.wl-heart[data-pid="' + productId + '"]');
  const card = btn ? btn.closest('article') : null;
  if (!card) return;
  card.style.transition = 'opacity .25s, transform .25s';
  card.style.opacity = '0';
  card.style.transform = 'scale(.92)';
  setTimeout(() => {
    card.remove();
    if (!document.querySelector('#wishlistGrid article')) location.reload();
  }, 250);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
