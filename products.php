<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/product-card.php';
require_once __DIR__ . '/includes/pagination.php';
startSession();
$currentPage = 'products';
$_user = currentUser();

$filterCat = trim($_GET['cat'] ?? 'all');
$sort      = $_GET['sort'] ?? 'pop';
$searchQ   = trim($_GET['q'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
// Khoảng giá do khách tự nhập (không phải mức cố định do shop đặt sẵn)
$priceMin = (isset($_GET['pmin']) && $_GET['pmin'] !== '') ? max(0, (float)$_GET['pmin']) : null;
$priceMax = (isset($_GET['pmax']) && $_GET['pmax'] !== '') ? max(0, (float)$_GET['pmax']) : null;
if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
    // khách nhập ngược (từ > đến) -> tự hoán đổi lại cho hợp lý thay vì trả rỗng
    [$priceMin, $priceMax] = [$priceMax, $priceMin];
}

$cat_icons = ['Thiết kế'=>'🎨','Văn phòng'=>'📄','Video'=>'🎬','Bảo mật'=>'🔒','Lưu trữ'=>'☁️','Developer'=>'💻','Mẹo hay'=>'💡'];

try {
    $where = "WHERE p.trang_thai != 'an'";
    $params = [];

    if ($filterCat !== 'all' && $filterCat !== '') {
        $where .= " AND c.ten_danh_muc = :cat";
        $params[':cat'] = $filterCat;
    }
    if ($searchQ !== '') {
        $where .= " AND (p.ten_san_pham LIKE :q1 OR c.ten_danh_muc LIKE :q2)";
        $params[':q1'] = '%' . $searchQ . '%';
        $params[':q2'] = '%' . $searchQ . '%';
    }
    if ($priceMin !== null) { $where .= " AND p.gia_ban >= :pmin"; $params[':pmin'] = $priceMin; }
    if ($priceMax !== null) { $where .= " AND p.gia_ban <= :pmax"; $params[':pmax'] = $priceMax; }

    $orderMap = [
        'asc'  => 'p.gia_ban ASC',
        'desc' => 'p.gia_ban DESC',
        'new'  => 'p.id DESC',
        'pop'  => 'p.la_moi DESC, p.id DESC',
    ];
    $orderSql = ' ORDER BY ' . ($orderMap[$sort] ?? $orderMap['pop']);

    // Đếm tổng số sản phẩm khớp bộ lọc (không LIMIT) để tính số trang
    $countStmt = db()->prepare(
        "SELECT COUNT(*) FROM products p JOIN categories c ON p.danh_muc_id = c.id $where"
    );
    $countStmt->execute($params);
    $totalCount = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalCount / PRODUCTS_PAGE_SIZE));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * PRODUCTS_PAGE_SIZE;

    $sql = "SELECT p.*, c.ten_danh_muc,
                   COALESCE(r.avg_rating, 0) AS avg_rating,
                   COALESCE(r.rating_count, 0) AS rating_count
            FROM products p
            JOIN categories c ON p.danh_muc_id = c.id
            LEFT JOIN (
                SELECT product_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS rating_count
                FROM product_reviews
                WHERE rating IS NOT NULL
                GROUP BY product_id
            ) r ON r.product_id = p.id
            $where
            $orderSql
            LIMIT :lim OFFSET :off";
    $stmt = db()->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', PRODUCTS_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $dbProducts = $stmt->fetchAll();

    $categories = db()->query(
        "SELECT c.ten_danh_muc, COUNT(*) AS so_luong
         FROM products p JOIN categories c ON c.id=p.danh_muc_id
         WHERE p.trang_thai!='an'
         GROUP BY c.ten_danh_muc, c.thu_tu
         ORDER BY c.thu_tu"
    )->fetchAll();

    // Tổng số sản phẩm KHÔNG lọc — dùng riêng cho mục "Tất cả" trong sidebar,
    // vì $totalCount ở trên đã bị giới hạn theo bộ lọc/tìm kiếm hiện tại
    $grandTotal = (int) db()->query("SELECT COUNT(*) FROM products WHERE trang_thai != 'an'")->fetchColumn();
} catch (PDOException $ex) {
    $dbProducts = [];
    $categories = [];
    $totalCount = 0;
    $totalPages = 1;
    $grandTotal = 0;
}

function productsUrl(array $overrides = []): string {
    $qs = array_filter([
        'cat'  => $_GET['cat'] ?? 'all',
        'sort' => $_GET['sort'] ?? 'pop',
        'q'    => $_GET['q'] ?? '',
        'pmin' => $_GET['pmin'] ?? '',
        'pmax' => $_GET['pmax'] ?? '',
        'page' => $_GET['page'] ?? '1',
    ], fn($v) => $v !== '' && $v !== null);
    $qs = array_merge($qs, $overrides);
    // Đổi bộ lọc/sắp xếp/tìm kiếm/giá thì luôn quay về trang 1 (trừ khi tự chỉ định trang)
    if (!array_key_exists('page', $overrides) && array_intersect_key($overrides, array_flip(['cat','sort','q','pmin','pmax']))) {
        $qs['page'] = '1';
    }
    $qs = array_filter($qs, fn($v) => $v !== '' && $v !== null);
    if (($qs['cat'] ?? 'all') === 'all') unset($qs['cat']);
    if (($qs['sort'] ?? 'pop') === 'pop') unset($qs['sort']);
    if (empty($qs['q'])) unset($qs['q']);
    if (($qs['page'] ?? '1') === '1') unset($qs['page']);
    return 'products.php' . ($qs ? '?' . http_build_query($qs) : '');
}
function productsPageUrl(int $p): string {
    return productsUrl(['page' => $p]);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sản Phẩm Phần Mềm Bản Quyền — FROMSHOPWHERE</title>
<meta name="description" content="Toàn bộ phần mềm bản quyền đang bán tại FROMSHOPWHERE: thiết kế đồ hoạ, văn phòng, chỉnh sửa video, bảo mật... Lọc theo danh mục, giá tốt, giao key ngay.">
<link rel="canonical" href="<?= SITE_URL ?>/products.php">
<meta property="og:type" content="website">
<meta property="og:title" content="Sản Phẩm Phần Mềm Bản Quyền — FROMSHOPWHERE">
<meta property="og:description" content="Toàn bộ phần mềm bản quyền đang bán: thiết kế, văn phòng, video, bảo mật. Giá tốt, giao key ngay.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/products.php">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">✓ Chính hãng 100%</span></div>
    <h1>Tất cả sản phẩm</h1>
    <p><?= (int)$totalCount ?>+ phần mềm bản quyền với giá tốt nhất thị trường</p>
  </div>
</div>

<div class="section">
  <div class="products-page-layout">

    <aside class="sidebar">
      <div class="sidebar-title">Danh mục</div>
      <div class="sidebar-cats" id="catFilterList">
        <a href="<?= e(productsUrl(['cat'=>'all'])) ?>" data-cat="all" class="cat-pill<?= $filterCat === 'all' ? ' active' : '' ?>">
          <span>Tất cả</span><span class="cat-count"><?= (int)$grandTotal ?></span>
        </a>
        <?php foreach ($categories as $cat):
          $cname = $cat['ten_danh_muc'];
          $ico = $cat_icons[$cname] ?? '📦';
        ?>
        <a href="<?= e(productsUrl(['cat'=>$cname])) ?>" data-cat="<?= e($cname) ?>" class="cat-pill<?= $filterCat === $cname ? ' active' : '' ?>">
          <span><?= $ico ?> <?= e($cname) ?></span><span class="cat-count"><?= (int)$cat['so_luong'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="sidebar-sort">
        <div class="sidebar-title">Sắp xếp theo</div>
        <div class="sort-links" id="sortFilterList">
          <?php
          $sortOpts = ['pop'=>'Phổ biến nhất','asc'=>'Giá tăng dần','desc'=>'Giá giảm dần','new'=>'Mới nhất'];
          foreach ($sortOpts as $val => $lbl):
          ?>
          <a href="<?= e(productsUrl(['sort'=>$val])) ?>" data-sort="<?= e($val) ?>" class="sort-link<?= $sort === $val ? ' active' : '' ?>"><?= $lbl ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="sidebar-price">
        <div class="sidebar-title">Khoảng giá</div>
        <div class="price-range-box" id="priceRangeBox">
          <div class="price-range-inputs">
            <input type="number" min="0" step="1000" inputmode="numeric" class="price-input" id="priceMinInput" placeholder="Từ" value="<?= $priceMin !== null ? (int)$priceMin : '' ?>">
            <span class="price-range-sep">—</span>
            <input type="number" min="0" step="1000" inputmode="numeric" class="price-input" id="priceMaxInput" placeholder="Đến" value="<?= $priceMax !== null ? (int)$priceMax : '' ?>">
          </div>
          <button type="button" class="btn-price-apply" id="priceApplyBtn">Áp dụng</button>
          <?php if ($priceMin !== null || $priceMax !== null): ?>
          <a href="<?= e(productsUrl(['pmin'=>null,'pmax'=>null])) ?>" class="price-range-clear" id="priceClearBtn">✕ Xoá lọc giá</a>
          <?php endif; ?>
        </div>
      </div>
    </aside>

    <div class="products-main">
      <div class="products-count-bar" id="productsCountBar" style="<?= $searchQ !== '' ? 'display:none' : '' ?>">
        Hiển thị <strong id="productsCountNum"><?= (int)$totalCount ?></strong> sản phẩm<span id="productsCountCat"><?= $filterCat !== 'all' ? ' trong danh mục "' . e($filterCat) . '"' : '' ?></span>
      </div>
      <p class="search-result-hint" id="searchResultHint" style="<?= $searchQ === '' ? 'display:none' : '' ?>">
        Kết quả tìm kiếm: <strong id="searchResultTerm"><?= e($searchQ) ?></strong> (<span id="searchResultCount"><?= (int)$totalCount ?></span> sản phẩm)
      </p>

      <div class="products" id="productsGrid" data-cat="<?= e($filterCat) ?>" data-sort="<?= e($sort) ?>" data-q="<?= e($searchQ) ?>" data-pmin="<?= e($priceMin ?? '') ?>" data-pmax="<?= e($priceMax ?? '') ?>" data-page="<?= (int)$page ?>">
        <?php if (empty($dbProducts)): ?>
          <p class="empty-grid-msg">
            <?php if ($searchQ !== ''): ?>
              Không tìm thấy sản phẩm phù hợp với "<?= e($searchQ) ?>".
            <?php else: ?>
              Không có sản phẩm nào trong danh mục này.
            <?php endif; ?>
          </p>
        <?php else: ?>
          <?php foreach ($dbProducts as $p) renderProductCard($p, 'grid'); ?>
        <?php endif; ?>
      </div>

      <div id="productsPagination">
        <?php renderPagination($page, $totalPages, 'productsPageUrl'); ?>
      </div>
    </div>

  </div>
</div>

<script src="assets/js/products-filter.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
