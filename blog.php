<?php
require_once __DIR__ . '/config.php';
startSession();
$_user    = currentUser();
$_isAdmin = isAdmin();
$currentPage = 'blog';

$filter = $_GET['tag'] ?? '';
$search = trim($_GET['q'] ?? '');

try {
    $where = "WHERE trang_thai = 'da_dang'";
    $params = [];
    if ($filter) { $where .= " AND tag = :tag"; $params[':tag'] = $filter; }
    if ($search) { $where .= " AND (tieu_de LIKE :q OR excerpt LIKE :q2)"; $params[':q'] = "%$search%"; $params[':q2'] = "%$search%"; }

    $stmt = db()->prepare("SELECT id,tieu_de,slug,excerpt,tag,icon,tag_color,read_time,ngay_dang,hinh_anh FROM posts $where ORDER BY ngay_dang DESC LIMIT 20");
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    $tags = db()->query("SELECT DISTINCT tag,icon,tag_color FROM posts WHERE trang_thai='da_dang' AND tag IS NOT NULL AND tag!='' ORDER BY tag")->fetchAll();
} catch(Exception $e) { $posts = []; $tags = []; }

// ── Trả về HTML mảnh (fragment) cho gọi AJAX khi bấm lọc danh mục / tìm kiếm,
//    để chỉ khu vực danh sách bài viết cập nhật, không tải lại cả trang.
if (isset($_GET['ajax'])) {
    $postCountBeforeShift = count($posts);
    ob_start();
    include __DIR__ . '/includes/blog-post-list.php';
    $html = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'html'   => $html,
        'filter' => $filter,
        'search' => $search,
        'count'  => $postCountBeforeShift,
    ]);
    exit;
}

function bgByColor($c) {
    $m = [
        '#185FA5' => 'linear-gradient(135deg,#1a3a6b,#2563a8)',
        '#0F6E56' => 'linear-gradient(135deg,#0a3d30,#0F6E56)',
        '#A32D2D' => 'linear-gradient(135deg,#5a1a1a,#A32D2D)',
        '#065E34' => 'linear-gradient(135deg,#022b18,#065e34)',
        '#534AB7' => 'linear-gradient(135deg,#2d2a6e,#534ab7)',
        '#BA7517' => 'linear-gradient(135deg,#5a3700,#ba7517)',
        '#1554B2' => 'linear-gradient(135deg,#0e1a4a,#1554b2)',
    ];
    return $m[$c] ?? 'linear-gradient(135deg,#022b18,#065e34)';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Blog & Hướng Dẫn Sử Dụng Phần Mềm — FROMSHOPWHERE</title>
<meta name="description" content="Hướng dẫn cài đặt, kích hoạt và sử dụng phần mềm bản quyền. Mẹo hay, tin tức công nghệ, so sánh phần mềm từ FROMSHOPWHERE.">
<link rel="canonical" href="<?= SITE_URL ?>/blog.php">
<meta property="og:type" content="website">
<meta property="og:title" content="Blog & Hướng Dẫn — FROMSHOPWHERE">
<meta property="og:description" content="Hướng dẫn cài đặt, kích hoạt phần mềm bản quyền, mẹo hay và tin công nghệ.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/blog.php">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/blog.css">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>


<!-- ══ ADMIN BAR ══ -->
<?php if($_isAdmin): ?>
<div class="admin-bar">
  <div class="admin-bar-left">
    <strong>⚙️ Chế độ Admin</strong>
    <span>— Đang xem blog với quyền quản lý</span>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="admin/posts.php" class="ab-btn-green">✍️ Viết bài mới</a>
    <a href="admin/posts.php" class="ab-btn-dark">📋 Quản lý bài viết</a>
    <a href="admin/" class="ab-btn-dark">📊 Dashboard</a>
  </div>
</div>
<?php endif; ?>

<!-- ══ HERO ══ -->
<div class="blog-hero">
  <div class="blog-hero-inner">
    <h1>Blog &amp; <span>Hướng dẫn</span></h1>
    <p>Tin tức, đánh giá và hướng dẫn sử dụng phần mềm bản quyền</p>
    <form method="GET" action="blog.php">
      <?php if($filter): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
      <div class="hero-search">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm bài viết...">
        <button type="submit">🔍 Tìm</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ TAG FILTER STRIP ══ -->
<div class="blog-filters">
  <div class="blog-filters-inner">
    <select class="ftag-select">
      <option value="blog.php<?= $search ? '?q='.urlencode($search) : '' ?>" <?= !$filter?'selected':'' ?>>📰 Tất cả</option>
      <?php foreach($tags as $t): ?>
      <option value="blog.php?tag=<?= urlencode($t['tag']) ?><?= $search?'&q='.urlencode($search):'' ?>" data-tag="<?= htmlspecialchars($t['tag']) ?>" <?= $filter===$t['tag']?'selected':'' ?>><?= htmlspecialchars($t['icon']) ?> <?= htmlspecialchars($t['tag']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- ══ MAIN LAYOUT ══ -->
<div class="blog-layout">

  <!-- LEFT: POSTS -->
  <?php include __DIR__ . '/includes/blog-post-list.php'; ?>

  <!-- RIGHT: SIDEBAR -->
  <aside class="blog-sidebar">

    <!-- Recent posts -->
    <div class="sidebar-widget">
      <div class="sw-header">📌 Bài viết mới nhất</div>
      <div class="sw-body" style="padding:10px 14px">
        <?php
        try {
          $recent = db()->query("SELECT id,tieu_de,slug,icon,tag_color,hinh_anh,ngay_dang FROM posts WHERE trang_thai='da_dang' ORDER BY ngay_dang DESC LIMIT 5")->fetchAll();
        } catch(Exception $e){ $recent=[]; }
        foreach($recent as $r):
          $rb = bgByColor($r['tag_color']??'#065E34');
        ?>
        <a class="recent-item" href="blog-detail.php?slug=<?= urlencode($r['slug']) ?>">
          <div class="recent-thumb" style="background:<?= $rb ?>">
            <?php if(!empty($r['hinh_anh'])): ?>
              <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($r['hinh_anh']) ?>"
                   alt="<?= htmlspecialchars($r['tieu_de'] ?? '') ?>" style="width:100%;height:100%;object-fit:cover"
                   onerror="this.style.display='none'">
            <?php else: ?>
              <?= htmlspecialchars($r['icon']??'📝') ?>
            <?php endif; ?>
          </div>
          <div class="recent-info">
            <div class="recent-title"><?= htmlspecialchars($r['tieu_de']) ?></div>
            <div class="recent-date">📅 <?= date('d/m/Y',strtotime($r['ngay_dang'])) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Tag cloud -->
    <?php if(!empty($tags)): ?>
    <div class="sidebar-widget">
      <div class="sw-header">🏷️ Danh mục</div>
      <div class="sw-body">
        <div class="tag-cloud">
          <?php foreach($tags as $t): ?>
          <a href="blog.php?tag=<?= urlencode($t['tag']) ?>" class="tc-item"
             data-tag="<?= htmlspecialchars($t['tag']) ?>" data-color="<?= htmlspecialchars($t['tag_color']) ?>"
             style="<?= $filter===$t['tag']?'color:'.htmlspecialchars($t['tag_color']).';border-color:'.htmlspecialchars($t['tag_color']).'44':'' ?>">
            <?= htmlspecialchars($t['icon']) ?> <?= htmlspecialchars($t['tag']) ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="sidebar-widget">
      <div class="sw-header">📊 Thống kê</div>
      <div class="sw-body">
        <?php
        try {
          $total_p = db()->query("SELECT COUNT(*) FROM posts WHERE trang_thai='da_dang'")->fetchColumn();
          $total_tags = db()->query("SELECT COUNT(DISTINCT tag) FROM posts WHERE trang_thai='da_dang'")->fetchColumn();
        } catch(Exception $e){ $total_p=0;$total_tags=0; }
        ?>
        <ul class="stat-list">
          <li class="stat-row"><span class="sl">📝 Tổng bài viết</span><span class="sv"><?= $total_p ?></span></li>
          <li class="stat-row"><span class="sl">🏷️ Danh mục</span><span class="sv"><?= $total_tags ?></span></li>
          <li class="stat-row"><span class="sl">🕐 Cập nhật</span><span class="sv"><?= date('d/m/Y') ?></span></li>
        </ul>
      </div>
    </div>

  </aside>

</div><!-- /blog-layout -->

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="assets/js/blog.js"></script>
</body>
</html>
