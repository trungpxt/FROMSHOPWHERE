<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/product-card.php';
startSession();
$currentPage = '';

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$ids = array_slice(array_unique($ids), 0, 3);

$products = [];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT p.*, c.ten_danh_muc,
                COALESCE(r.avg_rating, 0) AS avg_rating,
                COALESCE(r.rating_count, 0) AS rating_count
         FROM products p
         JOIN categories c ON p.danh_muc_id = c.id
         LEFT JOIN (
             SELECT product_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS rating_count
             FROM product_reviews WHERE rating IS NOT NULL GROUP BY product_id
         ) r ON r.product_id = p.id
         WHERE p.id IN ($placeholders) AND p.trang_thai != 'an'"
    );
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    /* Giữ đúng thứ tự người dùng đã chọn */
    foreach ($ids as $id) {
        foreach ($rows as $r) {
            if ((int)$r['id'] === $id) { $products[] = $r; break; }
        }
    }
}

$rowsDef = [
    ['label' => 'Giá bán',      'render' => fn($p) => fmtVND((float)$p['gia_ban'])],
    ['label' => 'Giá gốc',      'render' => fn($p) => !empty($p['gia_goc']) ? fmtVND((float)$p['gia_goc']) : '—'],
    ['label' => 'Danh mục',     'render' => fn($p) => e($p['ten_danh_muc'] ?? '—')],
    ['label' => 'Phiên bản',    'render' => fn($p) => e($p['phien_ban'] ?: '—')],
    ['label' => 'Đánh giá',     'render' => fn($p) => (float)$p['avg_rating'] > 0
        ? number_format((float)$p['avg_rating'], 1) . ' ★ (' . (int)$p['rating_count'] . ')'
        : 'Chưa có đánh giá'],
    ['label' => 'Sản phẩm mới', 'render' => fn($p) => !empty($p['la_moi']) ? '✅ Có' : '—'],
    ['label' => 'Tình trạng',   'render' => fn($p) => match($p['trang_thai']) {
        'hien' => '<span class="badge b-tt">Còn hàng</span>',
        'het_hang' => '<span class="badge b-huy">Hết hàng</span>',
        default => '—',
    }],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>So Sánh Sản Phẩm — FROMSHOPWHERE</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/profile.css">
<link rel="stylesheet" href="assets/css/wishlist.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/compare.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">✓ Chính hãng 100%</span></div>
    <h1>⇄ So sánh sản phẩm</h1>
    <p>Đặt cạnh nhau để dễ dàng chọn ra sản phẩm phù hợp nhất với bạn</p>
  </div>
</div>

<div class="section">
  <?php if (count($products) < 2): ?>
    <div class="wl-empty">
      <div class="wl-empty-icon">⇄</div>
      <h3>Chưa đủ sản phẩm để so sánh</h3>
      <p>Chọn ít nhất 2 sản phẩm bằng nút ⇄ trên trang sản phẩm để bắt đầu so sánh.</p>
      <a class="btn-detail" href="products.php">Khám phá sản phẩm →</a>
    </div>
  <?php else: ?>
    <div class="cmp-table-wrap">
      <table class="cmp-table">
        <thead>
          <tr>
            <th class="cmp-label-col"></th>
            <?php foreach ($products as $p): ?>
            <th>
              <div class="cmp-head-card">
                <img src="images/<?= e($p['hinh_anh'] ?: 'ui/default.jpg') ?>" alt="<?= e($p['ten_san_pham']) ?>"
                     onerror="this.src='images/ui/default.jpg'">
                <div class="cmp-head-name"><?= e($p['ten_san_pham']) ?></div>
                <div class="cmp-head-actions">
                  <?php if ($p['trang_thai'] === 'het_hang'): ?>
                  <button class="pc-btn-cart" disabled style="opacity:.5;cursor:not-allowed">Hết hàng</button>
                  <?php else: ?>
                  <button class="pc-btn-cart" onclick="addToCart(<?= (int)$p['id'] ?>,'<?= addslashes(e($p['ten_san_pham'])) ?>',<?= (float)$p['gia_ban'] ?>,'<?= addslashes($p['hinh_anh'] ?? '') ?>')">🛒 Thêm giỏ</button>
                  <?php endif; ?>
                  <a class="pc-btn-detail" href="product-demo.php?id=<?= (int)$p['id'] ?>">Chi tiết</a>
                </div>
              </div>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rowsDef as $row): ?>
          <tr>
            <td class="cmp-label-col"><?= e($row['label']) ?></td>
            <?php foreach ($products as $p): ?>
              <td><?= $row['render']($p) ?></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td class="cmp-label-col">Mô tả</td>
            <?php foreach ($products as $p): ?>
              <td class="cmp-desc"><?= nl2br(e(mb_strimwidth($p['mo_ta'] ?? '', 0, 220, '…'))) ?></td>
            <?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
