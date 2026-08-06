<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'products';
$_user = currentUser();

// 1. Nhận ID sản phẩm từ URL quả trang products.php gửi sang
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

// 2. Truy vấn lấy thông tin chi tiết sản phẩm hiện tại
// Admin được phép xem trước cả sản phẩm đang ẩn (trang_thai='an'); khách thường chỉ xem được sản phẩm đang hiển thị
try {
    $sql = "
        SELECT p.*, c.ten_danh_muc 
        FROM products p 
        JOIN categories c ON p.danh_muc_id = c.id 
        WHERE p.id = :id" . (isAdmin() ? "" : " AND p.trang_thai != 'an'") . "
        LIMIT 1
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: products.php');
        exit;
    }
} catch (PDOException $ex) {
    error_log('product-demo.php DB error: ' . $ex->getMessage());
    header('Location: error.php');
    exit;
}

// 3. Sản phẩm liên quan (cùng danh mục, khác sản phẩm hiện tại) — trước đây
//    trang này nhúng TOÀN BỘ catalog vào HTML rồi lọc bằng JS, rất nặng khi
//    shop có nhiều sản phẩm. Giờ truy vấn thẳng, chỉ lấy đúng số cần hiển thị.
try {
    $stmtRel = db()->prepare("
        SELECT p.*, c.ten_danh_muc
        FROM products p
        JOIN categories c ON p.danh_muc_id = c.id
        WHERE p.trang_thai != 'an' AND p.danh_muc_id = :cat_id AND p.id != :id
        ORDER BY RAND()
        LIMIT 8
    ");
    $stmtRel->execute([':cat_id' => $product['danh_muc_id'], ':id' => $id]);
    $relatedProducts = $stmtRel->fetchAll();
} catch (PDOException $ex) {
    $relatedProducts = [];
}

$jsRelatedProducts = [];
foreach ($relatedProducts as $p) {
    $jsRelatedProducts[] = [
        'id' => (int)$p['id'],
        'name' => $p['ten_san_pham'],
        'cat' => $p['ten_danh_muc'],
        'price' => (float)$p['gia_ban'],
        'oldPrice' => $p['gia_goc'] ? (float)$p['gia_goc'] : null,
        'image' => !empty($p['hinh_anh']) ? "images/" . $p['hinh_anh'] : "images/ui/default.jpg",
        'hinh_anh' => $p['hinh_anh'] ?? '',
        'isNew' => (int)$p['la_moi']
    ];
}

function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

// 4. Điểm đánh giá trung bình + số lượt đánh giá (chỉ tính bình luận gốc có sao)
$avgRating = 0.0;
$ratingCount = 0;
try {
    $rStmt = db()->prepare(
        "SELECT ROUND(AVG(rating),1) avg_r, COUNT(*) c FROM product_reviews
         WHERE product_id = :id AND rating IS NOT NULL"
    );
    $rStmt->execute([':id' => $id]);
    $rRow = $rStmt->fetch();
    $avgRating   = $rRow && $rRow['avg_r'] ? (float)$rRow['avg_r'] : 0.0;
    $ratingCount = $rRow ? (int)$rRow['c'] : 0;
} catch (Exception $e) {
    // Bảng product_reviews có thể chưa được tạo -> bỏ qua, hiển thị 0
}

// 5. Số lượng đã bán THẬT (trước đây hiển thị cứng "1.2k+" giả — giờ tính
//    từ đơn hàng thật đã thanh toán/hoàn thành)
$soldCount = 0;
try {
    $soldStmt = db()->prepare(
        "SELECT COALESCE(SUM(oi.so_luong),0) FROM order_items oi
         JOIN orders o ON o.id = oi.don_hang_id
         WHERE oi.san_pham_id = :id AND o.trang_thai IN ('da_thanh_toan','hoan_thanh')"
    );
    $soldStmt->execute([':id' => $id]);
    $soldCount = (int) $soldStmt->fetchColumn();
} catch (Exception $e) {}

$productImage = !empty($product['hinh_anh']) ? "images/" . $product['hinh_anh'] : "images/default.jpg";

$seoDesc = trim((string)($product['mo_ta'] ?? ''));
$seoDesc = preg_replace('/\s+/', ' ', $seoDesc);
if ($seoDesc === '') {
    $seoDesc = $product['ten_san_pham'] . ' — phần mềm bản quyền chính hãng, giao key tự động, hỗ trợ cài đặt 24/7 tại FROMSHOPWHERE.';
}
if (mb_strlen($seoDesc) > 155) {
    $seoDesc = mb_substr($seoDesc, 0, 152) . '...';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['ten_san_pham']) ?> — Mua Bản Quyền Giá Rẻ | FROMSHOPWHERE</title>
<meta name="description" content="<?= htmlspecialchars($seoDesc) ?>">
<link rel="canonical" href="<?= SITE_URL ?>/product-demo.php?id=<?= (int)$product['id'] ?>">
<meta property="og:type" content="product">
<meta property="og:title" content="<?= htmlspecialchars($product['ten_san_pham']) ?> — FROMSHOPWHERE">
<meta property="og:description" content="<?= htmlspecialchars($seoDesc) ?>">
<meta property="og:image" content="<?= SITE_URL ?>/<?= htmlspecialchars($productImage) ?>">
<meta property="og:url" content="<?= SITE_URL ?>/product-demo.php?id=<?= (int)$product['id'] ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<script type="application/ld+json">
<?php
$ldProduct = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Product',
    'name'        => $product['ten_san_pham'],
    'description' => $seoDesc,
    'image'       => SITE_URL . '/' . $productImage,
    'category'    => $product['ten_danh_muc'],
    'sku'         => 'FSW-' . $product['id'],
    'offers'      => [
        '@type'         => 'Offer',
        'url'           => SITE_URL . '/product-demo.php?id=' . (int)$product['id'],
        'priceCurrency' => 'VND',
        'price'         => (string)(int)$product['gia_ban'],
        'availability'  => $product['trang_thai'] === 'het_hang'
            ? 'https://schema.org/OutOfStock'
            : 'https://schema.org/InStock',
    ],
];
if (!empty($product['thuong_hieu'])) {
    $ldProduct['brand'] = ['@type' => 'Brand', 'name' => $product['thuong_hieu']];
}
if ($ratingCount > 0) {
    $ldProduct['aggregateRating'] = [
        '@type'       => 'AggregateRating',
        'ratingValue' => (string)round($avgRating, 1),
        'reviewCount' => (string)$ratingCount,
    ];
}
echo json_encode($ldProduct, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
</script>
<script type="application/ld+json">
<?php
echo json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'Trang chủ','item'=>SITE_URL.'/index.php'],
        ['@type'=>'ListItem','position'=>2,'name'=>'Sản phẩm','item'=>SITE_URL.'/products.php'],
        ['@type'=>'ListItem','position'=>3,'name'=>$product['ten_danh_muc'],'item'=>SITE_URL.'/products.php?cat='.urlencode($product['ten_danh_muc'])],
        ['@type'=>'ListItem','position'=>4,'name'=>$product['ten_san_pham']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
</script>
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>


<link rel="stylesheet" href="assets/css/product-demo.css">


<div class="breadcrumb">
  <a href="index.php">Trang chủ</a>
  <span>/</span>
  <a href="products.php">Sản phẩm</a>
  <span>/</span>
  <a href="products.php"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></a>
  <span>/</span>
  <span style="color:var(--text)"><?php echo htmlspecialchars($product['ten_san_pham']); ?></span>
</div>

<div class="pd-wrap">
  <div class="pd-grid">
    
    <div class="pd-left">
      <div class="pd-gallery">
        <img class="pd-main-img" src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
      </div>
    </div>

    <div class="pd-right">
      <div class="pd-cat"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></div>
      <?php if (!empty($product['thuong_hieu'])): ?><div class="pd-brand"><?= e($product['thuong_hieu']) ?></div><?php endif; ?>
      <h2 class="pd-title"><?php echo htmlspecialchars($product['ten_san_pham']); ?></h2>
      
      <div class="pd-meta">
        <div class="pd-stars"><?php
            $fullStars = (int)round($avgRating);
            echo str_repeat('★', $fullStars) . str_repeat('☆', 5 - $fullStars);
            echo ' ' . ($ratingCount > 0 ? number_format($avgRating, 1) : 'Chưa có đánh giá');
        ?></div>
        <?php if ($soldCount > 0): ?>
        <div>•</div>
        <div>Đã bán <?= $soldCount >= 1000 ? number_format($soldCount / 1000, 1) . 'k' : $soldCount ?>+</div>
        <?php endif; ?>
        <div>•</div>
        <div>Mã SP: FSW-<?php echo $product['id']; ?></div>
      </div>

      <?php if ($product['trang_thai'] === 'het_hang'): ?>
      <div class="pd-outofstock-badge">⚠️ Sản phẩm tạm hết hàng</div>
      <?php endif; ?>

      <div class="pd-price-box">
        <span class="pd-price"><?php echo formatVND($product['gia_ban']); ?></span>
        <?php if (!empty($product['gia_goc']) && $product['gia_goc'] > $product['gia_ban']): ?>
          <span class="pd-old-price"><?php echo formatVND($product['gia_goc']); ?></span>
        <?php endif; ?>
        <?php if ($product['trang_thai'] !== 'het_hang'): ?><span class="mini-seal">✓ Chính hãng</span><?php endif; ?>
      </div>

      <div class="pd-desc">
        <?php echo nl2br(htmlspecialchars($product['mo_ta'] ?? 'Sản phẩm phần mềm kích hoạt bản quyền chính hãng, hỗ trợ cập nhật và bảo hành trọn đời tại FROMSHOPWHERE.')); ?>
      </div>

      <div class="pd-actions">
        <?php if ($product['trang_thai'] === 'het_hang'): ?>
        <button class="btn-buy-now" disabled style="opacity:.5;cursor:not-allowed">Tạm hết hàng</button>
        <button class="btn-add-cart" disabled style="opacity:.5;cursor:not-allowed">Tạm hết hàng</button>
        <?php else: ?>
        <button class="btn-buy-now" 
    onclick="buyNow(
        <?= (int)$product['id'] ?>, 
        '<?= addslashes($product['ten_san_pham']) ?>', 
        <?= (float)$product['gia_ban'] ?>, 
        '<?= addslashes($product['hinh_anh']) ?>'
    )">
    Mua ngay
</button>
        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>,'<?php echo addslashes($product['ten_san_pham']); ?>',<?php echo (float)$product['gia_ban']; ?>,'<?php echo addslashes($product['hinh_anh'] ?? ''); ?>')">Thêm giỏ hàng</button>
        <?php endif; ?>
      </div>

      <div class="pd-features">
        <div class="pd-feat-item">
          <span class="pd-feat-icon">⚡</span>
          <span>Giao hàng tự động trong 5 giây qua Email</span>
        </div>
        <div class="pd-feat-item">
          <span class="pd-feat-icon">🔑</span>
          <span>Key chính hãng 100% bảo hành trọn đời ổn định</span>
        </div>
        <div class="pd-feat-item">
          <span class="pd-feat-icon">🎧</span>
          <span>Hỗ trợ kỹ thuật 24/7 Ultraview/Teamview miễn phí</span>
        </div>
      </div>
    </div>

  </div>

  <div class="pd-tabs-nav">
    <button class="pd-tab-btn active" onclick="switchTab(this,'desc')">Chi tiết sản phẩm</button>
    <button class="pd-tab-btn" onclick="switchTab(this,'reviews')">Đánh giá khách hàng (<span id="reviewCountLabel"><?= $ratingCount ?></span>)</button>
  </div>

  <div id="tabDesc" class="pd-tab-content">
     <p><?php echo nl2br(htmlspecialchars($product['mo_ta'] ?? 'Không có thông tin mô tả chi tiết thêm cho sản phẩm này.')); ?></p>
  </div>

  <div id="tabReviews" class="pd-tab-content" style="display:none">
     <div class="pd-rating-summary">
       <div class="pd-rating-avg"><?= $ratingCount > 0 ? number_format($avgRating, 1) : '—' ?></div>
       <div>
         <div class="pd-rating-stars-lg"><?php
            $fullStars2 = (int)round($avgRating);
            echo str_repeat('★', $fullStars2) . str_repeat('☆', 5 - $fullStars2);
         ?></div>
         <div class="pd-rating-count"><?= $ratingCount ?> lượt đánh giá</div>
       </div>
     </div>

     <div class="rv-form" id="rvForm">
       <?php if ($_user): ?>
         <div>Đánh giá của bạn:</div>
         <div class="star-picker" id="starPicker">
           <span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span>
         </div>
         <textarea id="rvText" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm này..."></textarea>
         <button type="button" onclick="submitReview()">Gửi đánh giá</button>
       <?php else: ?>
         <p class="rv-login-hint">Vui lòng <a href="login.php?redirect=<?= urlencode('product-demo.php?id=' . $id) ?>">đăng nhập</a> để đánh giá sản phẩm này.</p>
       <?php endif; ?>
     </div>

     <div id="reviewGrid"></div>
  </div>

  <div class="pd-related">
    <h3>Sản phẩm liên quan</h3>
    <div class="products" id="relatedGrid"></div>
  </div>

  <div class="pd-related" id="recentlyViewedWrap" style="display:none">
    <h3>🕐 Đã xem gần đây</h3>
    <div class="products" id="recentlyViewedGrid"></div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const RELATED_PRODUCTS = <?= json_encode($jsRelatedProducts, JSON_UNESCAPED_UNICODE) ?>;
const CURRENT_PRODUCT = {
  id:  <?= (int)$product['id'] ?>,
  name: <?= json_encode($product['ten_san_pham'], JSON_UNESCAPED_UNICODE) ?>,
  cat:  <?= json_encode($product['ten_danh_muc'], JSON_UNESCAPED_UNICODE) ?>,
  price: <?= (float)$product['gia_ban'] ?>,
  img: <?= json_encode($product['hinh_anh'] ?? '', JSON_UNESCAPED_UNICODE) ?>
};
const SITE_URL_JS = "<?= SITE_URL ?>";
const PDP_LOGGED_IN = <?= $_user ? 'true' : 'false' ?>;
const PDP_IS_ADMIN  = <?= ($_user && $_user['vai_tro'] === 'admin') ? 'true' : 'false' ?>;
</script>
<script src="assets/js/product-demo.js"></script>
</body>
</html>