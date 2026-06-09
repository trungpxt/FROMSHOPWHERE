<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/product-card.php';
startSession();
$currentPage = 'products';
$_user = currentUser();

$filterCat = trim($_GET['cat'] ?? 'all');
$sort      = $_GET['sort'] ?? 'pop';
$searchQ   = trim($_GET['q'] ?? '');

$cat_icons = ['Thiết kế'=>'🎨','Văn phòng'=>'📄','Video'=>'🎬','Bảo mật'=>'🔒','Lưu trữ'=>'☁️','Developer'=>'💻','Mẹo hay'=>'💡'];

try {
    $sql = "SELECT p.*, c.ten_danh_muc FROM products p JOIN categories c ON p.danh_muc_id = c.id WHERE p.trang_thai = 'hien'";
    $params = [];

    if ($filterCat !== 'all' && $filterCat !== '') {
        $sql .= " AND c.ten_danh_muc = :cat";
        $params[':cat'] = $filterCat;
    }
    if ($searchQ !== '') {
        $sql .= " AND (p.ten_san_pham LIKE :q OR c.ten_danh_muc LIKE :q)";
        $params[':q'] = '%' . $searchQ . '%';
    }

    $orderMap = [
        'asc'  => 'p.gia_ban ASC',
        'desc' => 'p.gia_ban DESC',
        'new'  => 'p.id DESC',
        'pop'  => 'p.la_moi DESC, p.id DESC',
    ];
    $sql .= ' ORDER BY ' . ($orderMap[$sort] ?? $orderMap['pop']);

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $dbProducts = $stmt->fetchAll();

    $categories = db()->query("SELECT DISTINCT c.ten_danh_muc FROM products p JOIN categories c ON c.id=p.danh_muc_id WHERE p.trang_thai='hien' ORDER BY c.thu_tu")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $ex) {
    $dbProducts = [];
    $categories = [];
}

function productsUrl(array $overrides = []): string {
    $qs = array_filter([
        'cat'  => $_GET['cat'] ?? 'all',
        'sort' => $_GET['sort'] ?? 'pop',
        'q'    => $_GET['q'] ?? '',
    ], fn($v) => $v !== '' && $v !== null);
    $qs = array_merge($qs, $overrides);
    if (($qs['cat'] ?? 'all') === 'all') unset($qs['cat']);
    if (($qs['sort'] ?? 'pop') === 'pop') unset($qs['sort']);
    if (empty($qs['q'])) unset($qs['q']);
    return 'products.php' . ($qs ? '?' . http_build_query($qs) : '');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sản phẩm — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <h1>Tất cả sản phẩm</h1>
    <p>500+ phần mềm bản quyền với giá tốt nhất thị trường</p>
  </div>
</div>

<div class="section">
  <div class="products-page-layout">

    <aside class="sidebar">
      <div class="sidebar-title">Danh mục</div>
      <div class="sidebar-cats">
        <a href="<?= e(productsUrl(['cat'=>'all'])) ?>" class="cat-pill<?= $filterCat === 'all' ? ' active' : '' ?>">Tất cả</a>
        <?php foreach ($categories as $cname):
          $ico = $cat_icons[$cname] ?? '📦';
        ?>
        <a href="<?= e(productsUrl(['cat'=>$cname])) ?>" class="cat-pill<?= $filterCat === $cname ? ' active' : '' ?>"><?= $ico ?> <?= e($cname) ?></a>
        <?php endforeach; ?>
      </div>

      <div class="sidebar-sort">
        <div class="sidebar-title">Sắp xếp theo</div>
        <div class="sort-links">
          <?php
          $sortOpts = ['pop'=>'Phổ biến nhất','asc'=>'Giá tăng dần','desc'=>'Giá giảm dần','new'=>'Mới nhất'];
          foreach ($sortOpts as $val => $lbl):
          ?>
          <a href="<?= e(productsUrl(['sort'=>$val])) ?>" class="sort-link<?= $sort === $val ? ' active' : '' ?>"><?= $lbl ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>

    <div class="products-main">
      <?php if ($searchQ !== ''): ?>
      <p class="search-result-hint">Kết quả tìm kiếm: <strong><?= e($searchQ) ?></strong> (<?= count($dbProducts) ?> sản phẩm)</p>
      <?php endif; ?>

      <div class="products">
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
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
