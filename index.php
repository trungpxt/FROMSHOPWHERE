<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/product-card.php';
startSession();
$currentPage = 'home';

$cat_icons = ['Thiết kế'=>'🎨','Văn phòng'=>'📄','Video'=>'🎬','Bảo mật'=>'🔒','Lưu trữ'=>'☁️','Developer'=>'💻','Mẹo hay'=>'💡'];

try {
    $sql = "SELECT p.*, c.ten_danh_muc,
                   COALESCE(r.avg_rating, 0) AS avg_rating,
                   COALESCE(r.rating_count, 0) AS rating_count
            FROM products p
            JOIN categories c ON c.id = p.danh_muc_id
            LEFT JOIN (
                SELECT product_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS rating_count
                FROM product_reviews
                WHERE rating IS NOT NULL
                GROUP BY product_id
            ) r ON r.product_id = p.id
            WHERE p.trang_thai!='an'";
    $params = [];
    $sql .= " ORDER BY p.id DESC LIMIT 8";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $cats_db = db()->query("SELECT DISTINCT c.ten_danh_muc FROM products p JOIN categories c ON c.id=p.danh_muc_id WHERE p.trang_thai!='an' ORDER BY c.thu_tu")->fetchAll(PDO::FETCH_COLUMN);
    $total_products = (int)db()->query("SELECT COUNT(*) FROM products WHERE trang_thai!='an'")->fetchColumn();
    $total_users    = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $blog_posts = db()->query("SELECT id,tieu_de,slug,excerpt,tag,icon,tag_color,ngay_dang FROM posts WHERE trang_thai='da_dang' ORDER BY ngay_dang DESC LIMIT 3")->fetchAll();

    // Đánh giá THẬT của khách hàng — trước đây là dữ liệu bịa gắn nhãn "đã xác
    // thực", mâu thuẫn với chính bản sắc "chính hãng/xác thực" của cả site.
    // Giờ lấy đúng review thật (4-5 sao, có nội dung, không phải reply) từ DB.
    $real_testis = db()->query(
        "SELECT r.noi_dung, r.rating, u.ho_ten, p.ten_san_pham
         FROM product_reviews r
         JOIN users u ON u.id = r.user_id
         JOIN products p ON p.id = r.product_id
         WHERE r.parent_id IS NULL AND r.rating >= 4
           AND r.noi_dung IS NOT NULL AND CHAR_LENGTH(TRIM(r.noi_dung)) >= 20
         ORDER BY r.rating DESC, r.created_at DESC
         LIMIT 3"
    )->fetchAll();
} catch (PDOException $e) {
    $products = []; $cats_db = []; $total_products = 0; $total_users = 0; $blog_posts = []; $real_testis = [];
}
$_user = currentUser();

function bgGrad($c) {
    $m = ['#185FA5'=>'#1a3a6b','#f04923'=>'#1a0800','#A32D2D'=>'#5a1a1a','#065E34'=>'#022b18','#534AB7'=>'#2d2a6e','#BA7517'=>'#5a3700'];
    return $m[$c] ?? '#022b18';
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>FROMSHOPWHERE — Phần Mềm Bản Quyền Giá Rẻ, Giao Key Tự Động</title>
<meta name="description" content="Mua phần mềm bản quyền chính hãng giá tốt: Photoshop, Office 365, AutoCAD, Windows, Kaspersky... Giao license key tự động sau thanh toán, hỗ trợ cài đặt 24/7.">
<link rel="canonical" href="<?= SITE_URL ?>/index.php">
<meta property="og:type" content="website">
<meta property="og:title" content="FROMSHOPWHERE — Phần Mềm Bản Quyền Giá Rẻ">
<meta property="og:description" content="Mua phần mềm bản quyền chính hãng giá tốt, giao key tự động sau thanh toán, hỗ trợ cài đặt 24/7.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/index.php">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            'name'  => 'FROMSHOPWHERE',
            'url'   => SITE_URL . '/',
            'logo'  => SITE_URL . '/images/ui/logo.png',
        ],
        [
            '@type'           => 'WebSite',
            'name'            => 'FROMSHOPWHERE',
            'url'             => SITE_URL . '/',
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => SITE_URL . '/products.php?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="hw-hero">
  <div class="hero-container">
    <div class="hero-left">
      <div class="hero-badge">🔑 Phần mềm bản quyền chính hãng</div>
      <h1 class="hero-h1">Phần mềm xịn<br><em>giá không xịn</em></h1>
      <p class="hero-sub">Kho phần mềm bản quyền chính hãng đa dạng với giá tốt nhất thị trường. Giao key tự động qua email trong 5 giây.</p>
      <div class="hero-btns">
        <a class="hbtn-primary" href="products.php">🛒 Mua ngay</a>
        <a class="hbtn-ghost" href="products.php">Xem tất cả →</a>
      </div>
      <div class="hero-cert-strip">
        <div class="hero-cert-stat"><div class="num"><?= $total_products > 0 ? $total_products . '+' : '—' ?></div><div class="lbl">Sản phẩm</div></div>
        <div class="hero-cert-punch"></div>
        <div class="hero-cert-stat"><div class="num"><?= $total_users > 1000 ? round($total_users/1000,1).'K+' : ($total_users > 0 ? $total_users.'+' : '—') ?></div><div class="lbl">Khách hàng</div></div>
        <div class="hero-cert-punch"></div>
        <div class="hero-cert-stat"><div class="num">4.9★</div><div class="lbl">Đánh giá</div></div>
        <div class="hero-cert-punch"></div>
        <div class="hero-cert-stat"><div class="num">5s</div><div class="lbl">Giao key</div></div>
      </div>
    </div>
    <div class="hero-right">
      <div class="hero-seal" aria-hidden="true">
        <div class="hero-seal-ring"></div>
        <div class="hero-seal-core">
          <span class="hero-seal-check">✓</span>
          <span class="hero-seal-txt">CHÍNH HÃNG</span>
        </div>
      </div>
      <div class="hero-fan">
        <?php foreach (array_slice($products, 0, 3) as $i => $hp):
          $img = $hp['hinh_anh'] ? 'images/'.$hp['hinh_anh'] : 'images/default.jpg';
          $disc = ($hp['gia_goc'] > $hp['gia_ban'] && $hp['gia_goc'] > 0) ? round((1-$hp['gia_ban']/$hp['gia_goc'])*100) : 0;
        ?>
        <a class="hero-prod-card fan-<?= $i ?>" href="product-demo.php?id=<?= (int)$hp['id'] ?>">
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

    <div class="cats-wrap" id="catPills">
      <button class="cat-pill active" data-cat="all">Tất cả</button>
      <?php foreach ($cats_db as $cname):
        $cico = $cat_icons[$cname] ?? '📦';
      ?>
      <button class="cat-pill" data-cat="<?= e($cname) ?>"><?= $cico ?> <?= e($cname) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="prod-grid" id="homeProductGrid">
      <?php foreach ($products as $p) renderProductCard($p, 'home'); ?>
    </div>
  </div>
</div>

<div class="hw-section section-flush" id="recentlyViewedHomeWrap" style="display:none">
  <div class="flex-between" style="margin-bottom:8px">
    <div>
      <div class="sec-eyebrow">Tiếp tục xem</div>
      <h2 class="sec-title">🕐 Đã xem <span class="accent">gần đây</span></h2>
    </div>
  </div>
  <div class="prod-grid" id="recentlyViewedHomeGrid"></div>
</div>

<script>
const CAT_ICONS = <?= json_encode(array_combine($cats_db, array_map(fn($c) => ($cat_icons[$c] ?? '📦') . ' ' . $c, $cats_db)), JSON_UNESCAPED_UNICODE) ?>;
document.addEventListener('DOMContentLoaded', () => {
  if (typeof renderRecentlyViewed === 'function') {
    renderRecentlyViewed('recentlyViewedHomeWrap', 'recentlyViewedHomeGrid', 0);
  }
});
</script>
<script src="assets/js/index.js" defer></script>

<div class="steps-wrap">
  <div class="hw-section section-flush">
    <div class="text-center-block">
      <div class="sec-eyebrow">Đơn giản & nhanh chóng</div>
      <h2 class="sec-title">Chỉ 3 bước để <span class="accent">sở hữu</span></h2>
    </div>
    <div class="steps-grid">
      <div class="step-item"><div class="step-num" data-n="1"></div><div class="step-title">Chọn sản phẩm</div><div class="step-desc">Duyệt qua kho phần mềm bản quyền đa dạng, lọc theo danh mục và thêm vào giỏ hàng</div></div>
      <div class="step-item"><div class="step-num" data-n="2"></div><div class="step-title">Thanh toán</div><div class="step-desc">Chuyển khoản ngân hàng, MoMo, ZaloPay hoặc thẻ Visa/Mastercard — an toàn và nhanh chóng</div></div>
      <div class="step-item"><div class="step-num" data-n="3"></div><div class="step-title">Nhận key ngay</div><div class="step-desc">License key được gửi tự động qua email trong vòng 5 giây sau khi xác nhận thanh toán</div></div>
    </div>
  </div>
</div>

<div class="why-bg">
  <div class="hw-section section-flush">
    <div class="text-center-block" style="margin-bottom:40px">
      <div class="sec-eyebrow" style="color:var(--teal-300)">Cam kết của chúng tôi</div>
      <h2 class="sec-title">Tại sao chọn <span class="accent">FROMSHOPWHERE?</span></h2>
    </div>
    <div class="why-cert-list">
      <?php
      $whys = [
        ['🔑','Bản quyền chính hãng','Tất cả license key đều chính hãng, đảm bảo hoạt động ổn định, nhận update đầy đủ suốt vòng đời sản phẩm.'],
        ['⚡','Nhận key tức thì','Hệ thống tự động gửi key qua email trong 5 giây sau thanh toán — không cần chờ nhân viên xử lý.'],
        ['💰','Giá tốt nhất thị trường','Cam kết giá thấp nhất. Nếu tìm được giá rẻ hơn ở nơi khác, chúng tôi hoàn tiền chênh lệch 100%.'],
        ['🎧','Hỗ trợ kỹ thuật 24/7','Đội ngũ hỗ trợ qua Ultraview/Teamview miễn phí, giải quyết mọi sự cố cài đặt trong 15 phút.'],
      ];
      foreach ($whys as $wi => [$ico,$title,$desc]):
      ?>
      <div class="why-clause">
        <div class="why-clause-num">0<?= $wi + 1 ?></div>
        <div class="why-clause-seal"><?= $ico ?></div>
        <div class="why-clause-body">
          <h3><?= $title ?></h3>
          <p><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if (!empty($real_testis)): ?>
<div class="testi-section">
  <div class="hw-section section-flush">
    <div class="text-center-block" style="margin-bottom:0">
      <div class="sec-eyebrow">Phản hồi thực từ khách hàng</div>
      <h2 class="sec-title">Khách hàng <span class="accent">nói gì?</span></h2>
    </div>
    <div class="testi-grid-new">
      <?php
      $avatarColors = ['#8B7CF0', '#1D63B0', '#D6489A'];
      foreach ($real_testis as $ti => $t):
        $stars = (int)$t['rating'];
        $av = mb_strtoupper(mb_substr($t['ho_ten'], 0, 1));
        $col = $avatarColors[$ti % count($avatarColors)];
      ?>
      <div class="testi-card-new">
        <div class="testi-stars-new"><?= str_repeat('★',$stars).str_repeat('☆',5-$stars) ?></div>
        <p class="testi-text-new">"<?= e($t['noi_dung']) ?>"</p>
        <div class="testi-author-new">
          <div class="testi-av" style="background:<?= e($col) ?>"><?= e($av) ?></div>
          <div>
            <div class="testi-name-new"><?= e($t['ho_ten']) ?></div>
            <div class="testi-role-new"><?= e($t['ten_san_pham']) ?></div>
          </div>
        </div>
        <div class="testi-verified"><span class="testi-verified-dot"></span>Đã mua và xác nhận sản phẩm</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

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
        $bgc = bgGrad($bp['tag_color'] ?? '#3B2FA0');
      ?>
      <a class="blog-card-new" href="blog-detail.php?slug=<?= urlencode($bp['slug']) ?>">
        <div class="bcn-thumb" style="background:linear-gradient(135deg,<?= $bgc ?>,<?= e($bp['tag_color'] ?? '#3B2FA0') ?>)"><?= htmlspecialchars($bp['icon'] ?? '📝') ?></div>
        <div class="bcn-body">
          <div class="bcn-tag" style="color:<?= e($bp['tag_color'] ?? '#3B2FA0') ?>"><?= e($bp['tag'] ?? '') ?></div>
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
    <h2 class="cta-h2" id="ctaH2">Giảm ngay 15% cho<br>đơn hàng đầu tiên</h2>
    <p class="cta-sub">Đăng ký tài khoản và nhập mã ưu đãi khi thanh toán để nhận giảm giá ngay hôm nay</p>
    <div class="cta-code" id="ctaCode" title="Bấm để sao chép mã">FIRST15</div>
    <div class="cta-btns">
      <a class="hbtn-primary" href="products.php">🛒 Mua ngay</a>
      <a class="hbtn-ghost" href="login.php?mode=register">Đăng ký miễn phí →</a>
    </div>
  </div>
</div>

<div class="newsletter-section">
  <div class="newsletter-wrap">
    <div class="newsletter-icon">✉️</div>
    <h2>Đừng bỏ lỡ ưu đãi mới</h2>
    <p>Để lại email — nhận ngay mã giảm giá và thông báo sớm nhất khi có khuyến mãi lớn.</p>
    <form class="newsletter-form" id="newsletterForm" onsubmit="return submitNewsletter(event)">
      <input type="email" class="newsletter-input" id="newsletterEmail" placeholder="you@example.com" required>
      <button type="submit" class="newsletter-btn" id="newsletterBtn">Đăng ký</button>
    </form>
    <div class="newsletter-msg" id="newsletterMsg"></div>
    <p class="newsletter-note">Không spam. Có thể huỷ đăng ký bất cứ lúc nào.</p>
  </div>
</div>

<script>
async function submitNewsletter(e) {
  e.preventDefault();
  var emailEl = document.getElementById('newsletterEmail');
  var btn = document.getElementById('newsletterBtn');
  var msg = document.getElementById('newsletterMsg');
  var email = emailEl.value.trim();

  msg.className = 'newsletter-msg';
  btn.disabled = true;
  btn.textContent = 'Đang gửi...';

  try {
    var res = await fetch('api/newsletter-subscribe.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email })
    });
    var data = await res.json();
    if (data.ok) {
      msg.textContent = data.already ? 'Email này đã đăng ký trước đó rồi 👍' : '🎉 Đăng ký thành công! Cảm ơn bạn đã đồng hành.';
      msg.className = 'newsletter-msg show ok';
      emailEl.value = '';
    } else {
      msg.textContent = data.error || 'Có lỗi xảy ra, vui lòng thử lại.';
      msg.className = 'newsletter-msg show err';
    }
  } catch (err) {
    msg.textContent = 'Không kết nối được máy chủ, vui lòng thử lại.';
    msg.className = 'newsletter-msg show err';
  } finally {
    btn.disabled = false;
    btn.textContent = 'Đăng ký';
  }
  return false;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
