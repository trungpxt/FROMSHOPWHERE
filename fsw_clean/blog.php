<?php
require_once __DIR__ . '/config.php';
startSession();
$_user = currentUser();
$_isAdmin = isAdmin();

/* ── Lấy bài viết từ DB ── */
try {
    $posts = db()->query("
        SELECT id, tieu_de, slug, excerpt, tag, icon, tag_color, read_time, ngay_dang, hinh_anh
        FROM posts
        WHERE trang_thai = 'da_dang'
        ORDER BY ngay_dang DESC
        LIMIT 12
    ")->fetchAll();
} catch(Exception $e) { $posts = []; }

function bgByColor($c) {
    $m=['#185FA5'=>'#E6F1FB','#0F6E56'=>'#E1F5EE','#A32D2D'=>'#FCEBEB',
        '#065E34'=>'#E1F5EE','#534AB7'=>'#EEEDFE','#BA7517'=>'#FAEEDA'];
    return $m[$c] ?? '#F0F2F0';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog & Hướng dẫn — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
<style>
/* Admin toolbar trên blog */
.admin-bar {
    background: #010D05;
    border-bottom: 2px solid #C8FF00;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.admin-bar-left { display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(255,255,255,.6) }
.admin-bar-left strong { color:#C8FF00;font-size:12px;text-transform:uppercase;letter-spacing:.06em }
.admin-bar a,.admin-bar button {
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 14px;border-radius:8px;font-size:13px;
    font-weight:600;cursor:pointer;text-decoration:none;
    transition:all .15s;border:none;font-family:'Inter',sans-serif;
}
.ab-btn-green  { background:#C8FF00;color:#000 }
.ab-btn-green:hover { background:#d4ff00 }
.ab-btn-dark   { background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.1) }
.ab-btn-dark:hover { background:rgba(255,255,255,.15);color:#fff }

/* Blog card */
.blog-card {
    display:block;text-decoration:none;color:inherit;
    background:var(--card-bg);border:1px solid var(--border);
    border-radius:14px;overflow:hidden;
    transition:transform .25s,box-shadow .25s,border-color .25s;
    position:relative;
}
.blog-card:hover { transform:translateY(-5px);box-shadow:0 16px 48px rgba(6,94,52,.12);border-color:var(--green-300,#6EEAAA) }
.blog-thumb {
    height:200px;overflow:hidden;position:relative;
    background:var(--bg-alt);
}
.blog-thumb img { width:100%;height:100%;object-fit:cover;transition:transform .35s }
.blog-card:hover .blog-thumb img { transform:scale(1.05) }
.blog-thumb-icon {
    width:100%;height:100%;display:flex;
    align-items:center;justify-content:center;font-size:56px;
}
.blog-tag-badge {
    position:absolute;top:12px;left:12px;
    padding:4px 12px;border-radius:20px;
    font-size:11px;font-weight:700;letter-spacing:.05em;
    backdrop-filter:blur(8px);
}
.blog-body { padding:18px 20px 20px }
.blog-title {
    font-family:'Space Grotesk',sans-serif;
    font-size:16px;font-weight:700;
    color:var(--text);margin-bottom:8px;
    line-height:1.4;
    display:-webkit-box;-webkit-line-clamp:2;
    -webkit-box-orient:vertical;overflow:hidden;
}
.blog-excerpt {
    font-size:13px;color:var(--text-muted);
    line-height:1.7;margin-bottom:14px;
    display:-webkit-box;-webkit-line-clamp:3;
    -webkit-box-orient:vertical;overflow:hidden;
}
.blog-meta { display:flex;align-items:center;justify-content:space-between }
.blog-date { font-size:12px;color:var(--text-muted) }
.blog-read { font-size:12px;color:var(--green-600,#0A8A4C);font-weight:700;font-family:'Space Grotesk',sans-serif }

/* Admin badge trên card */
.card-admin-actions {
    position:absolute;top:10px;right:10px;
    display:flex;gap:6px;z-index:10;
    opacity:0;transition:opacity .2s;
}
.blog-card:hover .card-admin-actions { opacity:1 }
.card-admin-btn {
    width:30px;height:30px;border-radius:7px;border:none;
    cursor:pointer;font-size:14px;display:flex;
    align-items:center;justify-content:center;
    backdrop-filter:blur(8px);transition:transform .15s;
}
.card-admin-btn:hover { transform:scale(1.1) }
.cab-edit { background:rgba(200,255,0,.9);color:#000 }
.cab-del  { background:rgba(224,49,49,.85);color:#fff }
.cab-hide { background:rgba(0,0,0,.6);color:#fff }
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

<!-- ══ ADMIN TOOLBAR (chỉ admin thấy) ══ -->
<?php if($_isAdmin): ?>
<div class="admin-bar">
  <div class="admin-bar-left">
    <strong>⚙️ Chế độ Admin</strong>
    <span>— Bạn đang xem trang blog với quyền quản lý</span>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap">
    <a href="admin/posts.php?action=new" class="ab-btn-green ab-btn-green" onclick="openEditorFromBlog(event)">
      ✍️ Viết bài mới
    </a>
    <a href="admin/posts.php" class="ab-btn-dark">
      📋 Quản lý bài viết
    </a>
    <a href="admin/" class="ab-btn-dark">
      📊 Dashboard
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header">
  <div class="page-header-inner">
    <h1>Blog &amp; Hướng dẫn</h1>
    <p>Tin tức, đánh giá và hướng dẫn sử dụng phần mềm bản quyền</p>
  </div>
</div>

<!-- ══ BLOG GRID ══ -->
<div class="section">
  <?php if(empty($posts)): ?>
  <div style="text-align:center;padding:60px 0;color:var(--text-muted)">
    <div style="font-size:48px;margin-bottom:16px">📝</div>
    <p style="font-size:15px">Chưa có bài viết nào.</p>
    <?php if($_isAdmin): ?>
    <a href="admin/posts.php" class="btn-primary" style="display:inline-block;margin-top:16px">+ Viết bài đầu tiên</a>
    <?php endif; ?>
  </div>
  <?php else: ?>

  <div class="blog-grid">
    <?php foreach($posts as $post):
      $bg   = bgByColor($post['tag_color'] ?? '#065E34');
      $date = date('d/m/Y', strtotime($post['ngay_dang']));
      $tc   = htmlspecialchars($post['tag_color'] ?? '#065E34');
      $hasImg = !empty($post['hinh_anh']);
    ?>
    <a class="blog-card" href="blog-detail.php?slug=<?= urlencode($post['slug']) ?>">

      <!-- Ảnh hoặc icon -->
      <div class="blog-thumb" style="<?= !$hasImg ? "background:$bg" : '' ?>">
        <?php if($hasImg): ?>
          <img src="images/<?= htmlspecialchars($post['hinh_anh']) ?>"
               alt="<?= htmlspecialchars($post['tieu_de']) ?>"
               onerror="this.parentElement.classList.add('img-err');this.style.display='none'">
        <?php else: ?>
          <div class="blog-thumb-icon" style="background:<?= $bg ?>">
            <?= htmlspecialchars($post['icon'] ?? '📝') ?>
          </div>
        <?php endif; ?>

        <!-- Tag badge -->
        <span class="blog-tag-badge"
              style="background:<?= $bg ?>;color:<?= $tc ?>;border:1px solid <?= $tc ?>33">
          <?= htmlspecialchars($post['icon'] ?? '') ?> <?= htmlspecialchars($post['tag'] ?? '') ?>
        </span>

        <!-- Admin actions overlay -->
        <?php if($_isAdmin): ?>
        <div class="card-admin-actions" onclick="event.preventDefault()">
          <a href="admin/posts.php?edit=<?= $post['id'] ?>"
             class="card-admin-btn cab-edit" title="Sửa bài">✏️</a>
          <form method="POST" action="admin/posts.php" style="display:inline"
                onsubmit="return confirm('Ẩn bài viết này?')">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= $post['id'] ?>">
            <button class="card-admin-btn cab-hide" type="submit" title="Ẩn bài">👁</button>
          </form>
          <form method="POST" action="admin/posts.php" style="display:inline"
                onsubmit="return confirm('Xoá bài viết này?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $post['id'] ?>">
            <button class="card-admin-btn cab-del" type="submit" title="Xoá bài">🗑</button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <div class="blog-body">
        <h3 class="blog-title"><?= htmlspecialchars($post['tieu_de']) ?></h3>
        <p class="blog-excerpt">
          <?= htmlspecialchars(mb_substr($post['excerpt'] ?? '', 0, 160)) ?>
        </p>
        <div class="blog-meta">
          <span class="blog-date">📅 <?= $date ?> · ⏱ <?= $post['read_time'] ?? 5 ?> phút</span>
          <span class="blog-read">Đọc tiếp →</span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
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
      <div class="footer-col"><h4>Sản phẩm</h4><ul><li><a href="products.php">Thiết kế đồ hoạ</a></li><li><a href="products.php">Văn phòng</a></li><li><a href="products.php">Video</a></li><li><a href="products.php">Bảo mật</a></li></ul></div>
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
