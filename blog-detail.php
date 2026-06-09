<?php
require_once __DIR__ . '/config.php';
startSession();
$_user   = currentUser();
$_isAdmin = isAdmin();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: blog.php'); exit; }

try {
    $stmt = db()->prepare("
        SELECT p.*, u.ho_ten AS tac_gia
        FROM posts p JOIN users u ON u.id = p.tac_gia_id
        WHERE p.slug = :slug AND p.trang_thai = 'da_dang'
        LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $post = $stmt->fetch();
} catch(Exception $e) { $post = null; }

if (!$post) { header('Location: blog.php'); exit; }

/* Bài liên quan */
try {
    $rel = db()->prepare("
        SELECT id, tieu_de, slug, tag, icon, tag_color, read_time, ngay_dang, hinh_anh, excerpt
        FROM posts
        WHERE trang_thai = 'da_dang' AND slug != :slug
        ORDER BY RAND() LIMIT 3
    ");
    $rel->execute([':slug' => $slug]);
    $related = $rel->fetchAll();
} catch(Exception $e) { $related = []; }

function bgByColor($c) {
    $m=['#185FA5'=>'#E6F1FB','#0F6E56'=>'#E1F5EE','#A32D2D'=>'#FCEBEB',
        '#065E34'=>'#E1F5EE','#534AB7'=>'#EEEDFE','#BA7517'=>'#FAEEDA'];
    return $m[$c] ?? '#F0F2F0';
}

function renderContent(string $raw): string {
    $out = '';
    foreach (explode("\n\n", $raw) as $para) {
        $para = trim($para);
        if (!$para) continue;
        if (preg_match('/^\d+\.\s/', $para))
            $out .= '<h2>'.htmlspecialchars($para).'</h2>';
        elseif (mb_strlen($para) < 120 && mb_strtoupper($para) === $para && mb_strlen($para) > 8)
            $out .= '<h3>'.htmlspecialchars($para).'</h3>';
        else
            $out .= '<p>'.nl2br(htmlspecialchars($para)).'</p>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($post['tieu_de']) ?> — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
<style>
.detail-wrap { max-width: 820px; margin: 0 auto; padding: 40px 24px; }
.back-btn {
    display:inline-flex;align-items:center;gap:6px;
    color:var(--text-muted);font-size:13px;font-weight:600;
    text-decoration:none;margin-bottom:28px;
    padding:7px 14px;border-radius:8px;
    background:var(--bg-alt);border:1px solid var(--border);
    transition:all .15s;
}
.back-btn:hover { color:var(--green-600,#0A8A4C);border-color:var(--green-300,#6EEAAA) }
.detail-tag {
    display:inline-flex;align-items:center;gap:5px;
    padding:5px 14px;border-radius:20px;
    font-size:12px;font-weight:700;
    text-transform:uppercase;letter-spacing:.06em;
    margin-bottom:16px;
}
.detail-title {
    font-family:'Space Grotesk',sans-serif;
    font-size:clamp(22px,4vw,34px);
    font-weight:800;line-height:1.2;
    color:var(--text);letter-spacing:-.02em;
    margin-bottom:18px;
}
.detail-meta {
    display:flex;align-items:center;gap:16px;
    font-size:13px;color:var(--text-muted);
    padding-bottom:24px;
    border-bottom:2px solid var(--border);
    flex-wrap:wrap;margin-bottom:32px;
}
.author-av {
    width:34px;height:34px;border-radius:50%;
    background:linear-gradient(135deg,#065E34,#C8FF00);
    display:flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:800;color:#000;
    font-family:'Space Grotesk',sans-serif;flex-shrink:0;
}
.hero-img {
    margin-bottom:32px;border-radius:16px;
    overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.12);
}
.hero-img img { width:100%;max-height:440px;object-fit:cover;display:block }
.blog-content { font-size:15.5px;line-height:1.85;color:var(--text) }
.blog-content h2 {
    font-family:'Space Grotesk',sans-serif;
    font-size:21px;font-weight:800;
    color:var(--text);margin:40px 0 14px;
    padding-left:14px;
    border-left:4px solid var(--green-500,#12B566);
}
.blog-content h3 {
    font-family:'Space Grotesk',sans-serif;
    font-size:17px;font-weight:700;
    color:var(--text);margin:28px 0 10px;
}
.blog-content p { color:var(--text-sub,#3D5040);margin-bottom:18px }
.blog-cta {
    background:linear-gradient(135deg,#011208,#043D22);
    border-radius:16px;padding:32px;margin:40px 0;
    text-align:center;color:#fff;position:relative;overflow:hidden;
}
.blog-cta::before { content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 80% 50%,rgba(200,255,0,.12),transparent 60%) }
.blog-cta h3 { font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:800;color:#C8FF00;margin-bottom:8px;position:relative;z-index:1 }
.blog-cta p  { font-size:14px;color:rgba(255,255,255,.65);margin-bottom:18px;position:relative;z-index:1 }
.blog-cta a  { position:relative;z-index:1 }
.related-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-top:16px }
.related-card {
    background:var(--card-bg);border:1px solid var(--border);
    border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;
    display:block;transition:transform .2s,box-shadow .2s,border-color .2s;
}
.related-card:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(6,94,52,.12);border-color:var(--green-300,#6EEAAA) }
.related-thumb { height:120px;overflow:hidden;background:var(--bg-alt) }
.related-thumb img { width:100%;height:100%;object-fit:cover;transition:transform .3s }
.related-card:hover .related-thumb img { transform:scale(1.06) }
.related-thumb-icon { width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:38px }
.related-body { padding:14px }
.related-title { font-size:13px;font-weight:700;line-height:1.4;color:var(--text);margin-bottom:6px }

/* Admin edit bar */
.post-admin-bar {
    background:var(--bg-alt);border:1px solid var(--border);
    border-radius:12px;padding:14px 18px;
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:24px;flex-wrap:wrap;gap:10px;
}
.post-admin-bar span { font-size:13px;color:var(--text-muted) }
.post-admin-bar strong { color:var(--green-600,#0A8A4C) }
</style>
</head>
<body>

<div class="toast" id="toast"></div>
<div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBackdrop(event)">
  <div class="cart-panel">
    <div class="cart-header"><h3>Giỏ hàng</h3><button class="close-btn" onclick="toggleCart()">✕</button></div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer">
      <div class="cart-total"><span class="ct-label">Tổng cộng</span><span class="ct-value" id="cartTotal">0đ</span></div>
      <button class="btn-checkout" onclick="window.location.href='checkout.php'">Thanh toán →</button>
    </div>
  </div>
</div>

<!-- ══ NAV ══ -->
<nav>
  <div class="nav-inner">
    <a class="logo" href="index.php">
      <img src="images/logo.png" alt="FROMSHOPWHERE" style="height:44px;width:auto;object-fit:contain">
    </a>
    <ul class="nav-links">
      <li><a href="index.php">Trang chủ</a></li>
      <li><a href="products.php">Sản phẩm</a></li>
      <li><a href="blog.php" class="active">Blog</a></li>
      <li><a href="contact.php">Liên hệ</a></li>
    </ul>
    <div class="nav-right">
      <div class="search-wrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input class="search-box" type="search" placeholder="Tìm phần mềm..."
               onkeydown="if(event.key==='Enter')window.location.href='products.php?q='+encodeURIComponent(this.value)">
      </div>
      <button class="theme-toggle" onclick="toggleTheme()"><div class="theme-knob" id="themeKnob">☀️</div></button>
      <div class="cart-btn" onclick="toggleCart()">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        <span class="cart-badge" id="cartCount">0</span>
      </div>
      <?php if($_user): ?>
        <div style="position:relative">
          <button class="btn-login" onclick="document.getElementById('uDrop').classList.toggle('open')" style="cursor:pointer;display:flex;align-items:center;gap:6px">
            👤 <?= htmlspecialchars($_user['ho_ten']) ?> ▾
          </button>
          <div id="uDrop" class="user-dropdown">
            <?php if($_isAdmin):?><a href="admin/">⚙️ Quản trị</a><?php endif;?>
            <a href="profile.php">👤 Tài khoản</a>
            <a href="logout.php">🚪 Đăng xuất</a>
          </div>
        </div>
      <?php else: ?>
        <a class="btn-login" href="login.php">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ══ ARTICLE ══ -->
<div class="detail-wrap">
  <a class="back-btn" href="blog.php">← Quay lại Blog</a>

  <!-- Admin bar trực tiếp trên bài viết -->
  <?php if($_isAdmin): ?>
  <div class="post-admin-bar">
    <span>⚙️ <strong>Admin</strong> — Bài viết #<?= $post['id'] ?> · Slug: <code><?= htmlspecialchars($post['slug']) ?></code></span>
    <div style="display:flex;gap:8px">
      <a href="admin/posts.php?edit=<?= $post['id'] ?>"
         style="padding:7px 14px;background:#C8FF00;color:#000;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700">
        ✏️ Sửa bài
      </a>
      <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Ẩn bài viết này?')">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" value="<?= $post['id'] ?>">
        <button style="padding:7px 14px;background:#FEF3C7;color:#92400E;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer" type="submit">👁 Ẩn bài</button>
      </form>
      <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Xoá bài viết này vĩnh viễn?')">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $post['id'] ?>">
        <button style="padding:7px 14px;background:#FEE2E2;color:#991B1B;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer" type="submit">🗑 Xoá</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tag + Title + Meta -->
  <?php $bg = bgByColor($post['tag_color'] ?? '#065E34'); ?>
  <div class="detail-tag"
       style="background:<?= $bg ?>;color:<?= htmlspecialchars($post['tag_color'] ?? '#065E34') ?>">
    <?= htmlspecialchars($post['icon'] ?? '') ?> <?= htmlspecialchars($post['tag'] ?? 'Blog') ?>
  </div>
  <h1 class="detail-title"><?= htmlspecialchars($post['tieu_de']) ?></h1>
  <div class="detail-meta">
    <div style="display:flex;align-items:center;gap:8px">
      <div class="author-av"><?= strtoupper(mb_substr($post['tac_gia'], 0, 1)) ?></div>
      <span><b><?= htmlspecialchars($post['tac_gia']) ?></b></span>
    </div>
    <span>📅 <?= date('d/m/Y', strtotime($post['ngay_dang'])) ?></span>
    <span>⏱ <?= $post['read_time'] ?? 5 ?> phút đọc</span>
  </div>

  <!-- Hero image (ảnh thật) -->
  <?php if(!empty($post['hinh_anh'])): ?>
  <div class="hero-img">
    <img src="images/<?= htmlspecialchars($post['hinh_anh']) ?>"
         alt="<?= htmlspecialchars($post['tieu_de']) ?>">
  </div>
  <?php endif; ?>

  <!-- Nội dung -->
  <div class="blog-content">
    <?= renderContent($post['noi_dung']) ?>
  </div>

  <!-- CTA -->
  <div class="blog-cta">
    <h3>🔑 Mua phần mềm bản quyền tại FROMSHOPWHERE</h3>
    <p>Hàng trăm phần mềm chính hãng từ Adobe, Microsoft, Kaspersky... Giá tốt nhất — Giao key qua email trong 2 phút!</p>
    <a class="btn-primary" href="products.php">Xem tất cả sản phẩm →</a>
  </div>

  <!-- Bài liên quan -->
  <?php if(!empty($related)): ?>
  <div style="margin-top:48px">
    <h3 style="font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:800;margin-bottom:4px">Bài viết liên quan</h3>
    <p style="color:var(--text-muted);font-size:13px;margin-bottom:16px">Có thể bạn cũng quan tâm</p>
    <div class="related-grid">
      <?php foreach($related as $r):
        $rbg = bgByColor($r['tag_color'] ?? '#065E34');
      ?>
      <a class="related-card" href="blog-detail.php?slug=<?= urlencode($r['slug']) ?>">
        <div class="related-thumb" style="<?= empty($r['hinh_anh']) ? "background:$rbg" : '' ?>">
          <?php if(!empty($r['hinh_anh'])): ?>
            <img src="images/<?= htmlspecialchars($r['hinh_anh']) ?>" alt="">
          <?php else: ?>
            <div class="related-thumb-icon" style="background:<?= $rbg ?>">
              <?= htmlspecialchars($r['icon'] ?? '📝') ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="related-body">
          <div style="font-size:11px;font-weight:700;color:<?= htmlspecialchars($r['tag_color'] ?? '#065E34') ?>;margin-bottom:5px">
            <?= htmlspecialchars($r['icon'] ?? '') ?> <?= htmlspecialchars($r['tag'] ?? '') ?>
          </div>
          <div class="related-title"><?= htmlspecialchars($r['tieu_de']) ?></div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:6px">
            <?= date('d/m/Y', strtotime($r['ngay_dang'])) ?> · <?= $r['read_time'] ?? 5 ?> phút
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ══ FOOTER ══ -->
<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div style="margin-bottom:12px"><img src="images/logo.png" alt="FROMSHOPWHERE" style="height:50px;width:auto;object-fit:contain"></div>
        <p>Nền tảng mua bán phần mềm bản quyền uy tín hàng đầu Việt Nam.</p>
        <div class="social-links"><a class="social-link" href="#">f</a><a class="social-link" href="#">in</a><a class="social-link" href="#">yt</a><a class="social-link" href="#">tk</a></div>
      </div>
      <div class="footer-col"><h4>Sản phẩm</h4><ul><li><a href="products.php">Thiết kế</a></li><li><a href="products.php">Văn phòng</a></li><li><a href="products.php">Video</a></li><li><a href="products.php">Bảo mật</a></li></ul></div>
      <div class="footer-col"><h4>Hỗ trợ</h4><ul><li><a href="blog.php">Hướng dẫn</a></li><li><a href="contact.php">Liên hệ</a></li></ul></div>
      <div class="footer-col"><h4>Công ty</h4><ul><li><a href="#">Giới thiệu</a></li><li><a href="#">Điều khoản</a></li></ul></div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons"><div class="pay-badge">VISA</div><div class="pay-badge">MC</div><div class="pay-badge">MOMO</div><div class="pay-badge">ZALO</div></div>
    </div>
  </div>
</footer>

<script src="shared.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    restoreTheme(); updateCartBadge(); syncCartPanel();
    document.addEventListener('click', e => {
        const d = document.getElementById('uDrop');
        if (d && !d.parentElement.contains(e.target)) d.classList.remove('open');
    });
});
</script>
</body>
</html>
