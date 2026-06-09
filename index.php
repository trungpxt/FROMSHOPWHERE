<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/product-card.php';
startSession();
$currentPage = 'home';

$cat_icons = ['Thiết kế'=>'🎨','Văn phòng'=>'📄','Video'=>'🎬','Bảo mật'=>'🔒','Lưu trữ'=>'☁️','Developer'=>'💻','Mẹo hay'=>'💡'];
$filterCat = trim($_GET['cat'] ?? 'all');

try {
    $sql = "SELECT p.*, c.ten_danh_muc FROM products p JOIN categories c ON c.id=p.danh_muc_id WHERE p.trang_thai='hien'";
    $params = [];
    if ($filterCat !== 'all' && $filterCat !== '') {
        $sql .= " AND c.ten_danh_muc = :cat";
        $params[':cat'] = $filterCat;
    }
    $sql .= " ORDER BY p.id DESC LIMIT 8";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $cats_db = db()->query("SELECT DISTINCT c.ten_danh_muc FROM products p JOIN categories c ON c.id=p.danh_muc_id WHERE p.trang_thai='hien' ORDER BY c.thu_tu")->fetchAll(PDO::FETCH_COLUMN);
    $total_products = (int)db()->query("SELECT COUNT(*) FROM products WHERE trang_thai='hien'")->fetchColumn();
    $total_users    = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $blog_posts = db()->query("SELECT id,tieu_de,slug,excerpt,tag,icon,tag_color,ngay_dang FROM posts WHERE trang_thai='da_dang' ORDER BY ngay_dang DESC LIMIT 3")->fetchAll();
} catch (PDOException $e) {
    $products = []; $cats_db = []; $total_products = 500; $total_users = 12000; $blog_posts = [];
}
$_user = currentUser();

function bgGrad($c) {
    $m = ['#185FA5'=>'#1a3a6b','#0F6E56'=>'#0a3d30','#A32D2D'=>'#5a1a1a','#065E34'=>'#022b18','#534AB7'=>'#2d2a6e','#BA7517'=>'#5a3700'];
    return $m[$c] ?? '#022b18';
}

function indexCatUrl(string $cat): string {
    return $cat === 'all' ? 'index.php' : 'index.php?cat=' . urlencode($cat);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>FROMSHOPWHERE — Phần Mềm Bản Quyền Giá Tốt</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="hw-hero">
  <div class="hero-container">
    <div class="hero-left">
      <div class="hero-badge">🔑 Phần mềm bản quyền chính hãng</div>
      <h1 class="hero-h1">Phần mềm xịn<br><em>giá không xịn</em></h1>
      <p class="hero-sub">Hàng trăm phần mềm bản quyền chính hãng với giá tốt nhất thị trường. Giao key tự động qua email trong 5 giây.</p>
      <div class="hero-btns">
        <a class="hbtn-primary" href="products.php">🛒 Mua ngay</a>
        <a class="hbtn-ghost" href="products.php">Xem tất cả →</a>
      </div>
      <div class="hero-stats-row">
        <div class="hero-stat"><div class="num"><?= $total_products ?>+</div><div class="lbl">Sản phẩm</div></div>
        <div class="hero-stat"><div class="num"><?= $total_users > 1000 ? round($total_users/1000,1).'K' : $total_users ?>+</div><div class="lbl">Khách hàng</div></div>
        <div class="hero-stat"><div class="num">4.9★</div><div class="lbl">Đánh giá</div></div>
        <div class="hero-stat"><div class="num">5s</div><div class="lbl">Giao key</div></div>
      </div>
    </div>
    <div class="hero-right">
      <?php foreach (array_slice($products, 0, 4) as $hp):
        $img = $hp['hinh_anh'] ? 'images/'.$hp['hinh_anh'] : 'images/default.jpg';
        $disc = ($hp['gia_goc'] > $hp['gia_ban'] && $hp['gia_goc'] > 0) ? round((1-$hp['gia_ban']/$hp['gia_goc'])*100) : 0;
      ?>
      <a class="hero-prod-card" href="product-demo.php?id=<?= (int)$hp['id'] ?>">
        <div class="hpc-img"><img src="<?= e($img) ?>" alt="<?= e($hp['ten_san_pham']) ?>" loading="lazy" onerror="this.src='images/default.jpg'"></div>
        <div class="hpc-name"><?= e($hp['ten_san_pham']) ?></div>
        <div>
          <span class="hpc-price"><?= fmtVND((float)$hp['gia_ban']) ?></span>
          <?php if ($disc > 0): ?><span class="hpc-old">-<?= $disc ?>%</span><?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="trust-strip">
  <div class="trust-inner">
    <div class="trust-item"><span>✅</span> Key chính hãng 100%</div>
    <div class="trust-divider"></div>
    <div class="trust-item"><span>⚡</span> Giao ngay trong 5 giây</div>
    <div class="trust-divider"></div>
    <div class="trust-item"><span>🔒</span> Thanh toán bảo mật</div>
    <div class="trust-divider"></div>
    <div class="trust-item"><span>🎧</span> Hỗ trợ 24/7</div>
    <div class="trust-divider"></div>
    <div class="trust-item"><span>♻️</span> Bảo hành trọn đời</div>
  </div>
</div>

<div class="prod-grid-wrap">
  <div class="hw-section section-flush">
    <div class="flex-between" style="margin-bottom:24px">
      <div>
        <div class="sec-eyebrow">Được mua nhiều nhất</div>
        <h2 class="sec-title">Sản phẩm <span class="accent">nổi bật</span></h2>
      </div>
      <a href="products.php" class="link-teal">Xem tất cả →</a>
    </div>

    <div class="cats-wrap">
      <a href="<?= e(indexCatUrl('all')) ?>" class="cat-pill<?= $filterCat === 'all' ? ' active' : '' ?>">Tất cả</a>
      <?php foreach ($cats_db as $cname):
        $cico = $cat_icons[$cname] ?? '📦';
        $active = ($filterCat === $cname) ? ' active' : '';
      ?>
      <a href="<?= e(indexCatUrl($cname)) ?>" class="cat-pill<?= $active ?>"><?= $cico ?> <?= e($cname) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="prod-grid">
    <?php if (empty($products)): ?>
      <p class="empty-grid-msg">Chưa có sản phẩm<?= $filterCat !== 'all' ? ' trong danh mục này' : '' ?>.</p>
    <?php else: ?>
      <?php foreach ($products as $p) renderProductCard($p, 'home'); ?>
    <?php endif; ?>
    </div>
  </div>
</div>

<div class="steps-wrap">
  <div class="hw-section section-flush">
    <div class="text-center-block">
      <div class="sec-eyebrow">Đơn giản & nhanh chóng</div>
      <h2 class="sec-title">Chỉ 3 bước để <span class="accent">sở hữu</span></h2>
    </div>
    <div class="steps-grid">
      <div class="step-item"><div class="step-num">1</div><div class="step-title">Chọn sản phẩm</div><div class="step-desc">Duyệt qua hàng trăm phần mềm bản quyền, lọc theo danh mục và thêm vào giỏ hàng</div></div>
      <div class="step-item"><div class="step-num">2</div><div class="step-title">Thanh toán</div><div class="step-desc">Chuyển khoản ngân hàng, MoMo, ZaloPay hoặc thẻ Visa/Mastercard — an toàn và nhanh chóng</div></div>
      <div class="step-item"><div class="step-num">3</div><div class="step-title">Nhận key ngay</div><div class="step-desc">License key được gửi tự động qua email trong vòng 5 giây sau khi xác nhận thanh toán</div></div>
    </div>
  </div>
</div>

<div class="why-bg">
  <div class="hw-section section-flush">
    <div class="text-center-block" style="margin-bottom:40px">
      <div class="sec-eyebrow" style="color:#E1FCF6">Cam kết của chúng tôi</div>
      <h2 class="sec-title" style="color:#fff">Tại sao chọn <span style="color:#5DCAA5">FROMSHOPWHERE?</span></h2>
    </div>
    <div class="why-grid">
      <?php
      $whys = [
        ['🔑','Bản quyền chính hãng','Tất cả license key đều chính hãng, đảm bảo hoạt động ổn định, nhận update đầy đủ suốt vòng đời sản phẩm.'],
        ['⚡','Giao hàng tức thì','Hệ thống tự động gửi key qua email trong 5 giây sau thanh toán — không cần chờ nhân viên xử lý.'],
        ['💰','Giá tốt nhất thị trường','Cam kết giá thấp nhất. Nếu tìm được giá rẻ hơn ở nơi khác, chúng tôi hoàn tiền chênh lệch 100%.'],
        ['🎧','Hỗ trợ kỹ thuật 24/7','Đội ngũ hỗ trợ qua Ultraview/Teamview miễn phí, giải quyết mọi sự cố cài đặt trong 15 phút.'],
      ];
      foreach ($whys as [$ico,$title,$desc]):
      ?>
      <div class="why-card">
        <div class="why-icon why-icon-bg"><?= $ico ?></div>
        <h3><?= $title ?></h3>
        <p><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="testi-section">
  <div class="hw-section section-flush">
    <div class="text-center-block" style="margin-bottom:0">
      <div class="sec-eyebrow">Phản hồi thực từ khách hàng</div>
      <h2 class="sec-title">Khách hàng <span class="accent">nói gì?</span></h2>
    </div>
    <div class="testi-grid-new">
      <?php
      $testis = [
        ['NA','Nguyễn Anh','Graphic Designer · TP.HCM','#0F6E56',5,'Mua Photoshop 2025 với giá chỉ bằng 1/5 so với mua trực tiếp từ Adobe. Key hoạt động mượt mà, giao ngay sau khi chuyển khoản! Quá uy tín.'],
        ['TL','Trần Linh','Kế toán · Hà Nội','#185FA5',5,'Đã mua Office 365 lần thứ 3 tại đây. Luôn nhận được key trong vài phút. Shop cực kỳ uy tín, nhân viên hỗ trợ nhiệt tình và chuyên nghiệp.'],
        ['MK','Minh Khoa','Content Creator · Đà Nẵng','#534AB7',4,'Giá rẻ, nhiều lựa chọn, giao diện đẹp dễ dùng. Mình mua Filmora 13 và Kaspersky đều ổn định. Chắc chắn sẽ tiếp tục ủng hộ!'],
      ];
      foreach ($testis as [$av,$name,$role,$col,$stars,$text]):
      ?>
      <div class="testi-card-new">
        <div class="testi-stars-new"><?= str_repeat('★',$stars).str_repeat('☆',5-$stars) ?></div>
        <p class="testi-text-new">"<?= $text ?>"</p>
        <div class="testi-author-new">
          <div class="testi-av" style="background:<?= $col ?>"><?= $av ?></div>
          <div>
            <div class="testi-name-new"><?= $name ?></div>
            <div class="testi-role-new"><?= $role ?></div>
          </div>
        </div>
        <div class="testi-verified">✓ Đã mua và xác nhận sản phẩm</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if (!empty($blog_posts)): ?>
<div class="blog-preview">
  <div class="hw-section section-flush">
    <div class="flex-between" style="margin-bottom:8px">
      <div>
        <div class="sec-eyebrow">Kiến thức & Hướng dẫn</div>
        <h2 class="sec-title">Bài viết <span class="accent">mới nhất</span></h2>
      </div>
      <a href="blog.php" class="link-teal">Xem tất cả bài →</a>
    </div>
    <div class="blog-cards-new">
      <?php foreach ($blog_posts as $bp):
        $bgc = bgGrad($bp['tag_color'] ?? '#0F6E56');
      ?>
      <a class="blog-card-new" href="blog-detail.php?slug=<?= urlencode($bp['slug']) ?>">
        <div class="bcn-thumb" style="background:linear-gradient(135deg,<?= $bgc ?>,<?= e($bp['tag_color'] ?? '#0F6E56') ?>)"><?= htmlspecialchars($bp['icon'] ?? '📝') ?></div>
        <div class="bcn-body">
          <div class="bcn-tag" style="color:<?= e($bp['tag_color'] ?? '#0F6E56') ?>"><?= e($bp['tag'] ?? '') ?></div>
          <div class="bcn-title"><?= e($bp['tieu_de']) ?></div>
          <div class="bcn-excerpt"><?= e(mb_substr($bp['excerpt'] ?? '', 0, 100)) ?></div>
          <div class="bcn-date">📅 <?= date('d/m/Y', strtotime($bp['ngay_dang'])) ?></div>
          <span class="bcn-read">Đọc bài →</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="cta-banner">
  <div class="cta-inner">
    <div class="cta-badge">🎁 Ưu đãi đặc biệt</div>
    <h2 class="cta-h2">Giảm ngay 15% cho<br>đơn hàng đầu tiên</h2>
    <p class="cta-sub">Đăng ký tài khoản và nhập mã ưu đãi khi thanh toán để nhận giảm giá ngay hôm nay</p>
    <div class="cta-code">FIRST15</div>
    <div class="cta-btns">
      <a class="hbtn-primary" href="products.php">🛒 Mua ngay</a>
      <a class="hbtn-ghost" href="login.php?mode=register">Đăng ký miễn phí →</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
