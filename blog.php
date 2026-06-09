<?php
require_once __DIR__ . '/config.php';
startSession();
$_user    = currentUser();
$_isAdmin = isAdmin();

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

function bgByColor($c) {
    $m = [
        '#185FA5' => 'linear-gradient(135deg,#1a3a6b,#2563a8)',
        '#0F6E56' => 'linear-gradient(135deg,#0a3d30,#0f6e56)',
        '#A32D2D' => 'linear-gradient(135deg,#5a1a1a,#a32d2d)',
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
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Blog & Hướng dẫn — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════
   BLOG PAGE — FULL REDESIGN
══════════════════════════════════════ */
:root {
  --b-green: #065E34;
  --b-lime:  #C8FF00;
  --b-lime-d: rgba(200,255,0,.1);
  --b-border: rgba(255,255,255,.08);
  --b-card: rgba(255,255,255,.03);
  --b-card-h: rgba(255,255,255,.055);
  --b-text: rgba(255,255,255,.88);
  --b-muted: rgba(255,255,255,.45);
  --b-faint: rgba(255,255,255,.15);
}
body { font-family: 'Plus Jakarta Sans', sans-serif; }

/* ── ADMIN BAR ── */
.admin-bar {
  background: #010D05;
  border-bottom: 2px solid var(--b-lime);
  padding: 10px 24px;
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; flex-wrap: wrap;
}
.admin-bar-left { display:flex;align-items:center;gap:10px;font-size:13px;color:var(--b-muted) }
.admin-bar-left strong { color:var(--b-lime);font-size:12px;text-transform:uppercase;letter-spacing:.06em }
.ab-btn-green { background:var(--b-lime);color:#000;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .15s }
.ab-btn-green:hover { background:#d4ff00 }
.ab-btn-dark  { background:rgba(255,255,255,.08);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.12);padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .15s }
.ab-btn-dark:hover { background:rgba(255,255,255,.15);color:#fff }

/* ── PAGE HERO ── */
.blog-hero {
  background: linear-gradient(135deg, #011208 0%, #043D22 60%, #065E34 100%);
  padding: 56px 24px 44px;
  text-align: center;
  position: relative; overflow: hidden;
}
.blog-hero::before {
  content: '';
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(200,255,0,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(200,255,0,.04) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
}
.blog-hero-inner { max-width: 640px; margin: 0 auto; position: relative; z-index: 1; }
.blog-hero h1 {
  font-size: clamp(24px, 4vw, 38px);
  font-weight: 800; color: #fff;
  margin: 0 0 10px; letter-spacing: -.02em;
  line-height: 1.2;
}
.blog-hero h1 span { color: var(--b-lime); }
.blog-hero p { font-size: 15px; color: rgba(255,255,255,.5); margin: 0 0 24px; }

/* Search bar in hero */
.hero-search {
  display: flex; max-width: 480px; margin: 0 auto;
  background: rgba(255,255,255,.07);
  border: 1.5px solid rgba(255,255,255,.14);
  border-radius: 12px; overflow: hidden;
  transition: border-color .2s, box-shadow .2s;
}
.hero-search:focus-within {
  border-color: var(--b-lime);
  box-shadow: 0 0 0 3px rgba(200,255,0,.12);
}
.hero-search input {
  flex: 1; padding: 12px 16px;
  background: transparent; border: none; outline: none;
  font-size: 14px; color: #fff;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.hero-search input::placeholder { color: rgba(255,255,255,.35); }
.hero-search button {
  padding: 12px 20px; background: var(--b-green);
  border: none; cursor: pointer;
  color: var(--b-lime); font-size: 13px; font-weight: 700;
  font-family: 'Plus Jakarta Sans', sans-serif;
  border-left: 1px solid rgba(255,255,255,.1);
  transition: background .15s;
}
.hero-search button:hover { background: #054d2a; }

/* Tag filter strip */
.blog-filters {
  background: #0A1A0F;
  border-bottom: 1px solid var(--b-border);
  padding: 0 24px;
  overflow-x: auto;
  scrollbar-width: none;
}
.blog-filters::-webkit-scrollbar { display: none; }
.blog-filters-inner {
  max-width: 1180px; margin: 0 auto;
  display: flex; gap: 4px;
  padding: 12px 0;
  align-items: center;
}
.ftag {
  white-space: nowrap;
  padding: 6px 14px; border-radius: 20px;
  font-size: 12px; font-weight: 600;
  text-decoration: none;
  color: var(--b-muted);
  border: 1.5px solid transparent;
  transition: all .15s; display: inline-flex; align-items: center; gap: 5px;
}
.ftag:hover { color: var(--b-text); background: rgba(255,255,255,.06); }
.ftag.active {
  background: var(--b-lime-d);
  color: var(--b-lime);
  border-color: rgba(200,255,0,.25);
}
.ftag-all { color: var(--b-text); }
.ftag-count { font-size: 10px; opacity: .6; }

/* ── MAIN LAYOUT ── */
.blog-layout {
  max-width: 1180px; margin: 0 auto;
  padding: 40px 24px;
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 40px;
  align-items: start;
}
@media(max-width:900px){ .blog-layout{ grid-template-columns:1fr; } .blog-sidebar{ display:none; } }

/* ── FEATURED POST (first card big) ── */
.post-featured {
  display: flex;
  flex-direction: row;
  background: var(--b-card);
  border: 1px solid var(--b-border);
  border-radius: 18px; overflow: hidden;
  margin-bottom: 28px;
  text-decoration: none; color: inherit;
  transition: border-color .25s, box-shadow .25s, transform .25s;
  min-height: 260px;
}
.post-featured:hover {
  border-color: rgba(200,255,0,.3);
  box-shadow: 0 20px 60px rgba(0,0,0,.35);
  transform: translateY(-3px);
}
.post-featured .pf-thumb {
  position: relative; overflow: hidden;
  width: 45%; flex-shrink: 0;
  min-height: 260px;
  display: flex; align-items: center; justify-content: center;
}
.post-featured .pf-thumb img {
  position: absolute; inset: 0;
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .45s;
  z-index: 2;
}
.post-featured:hover .pf-thumb img { transform: scale(1.06); }
.post-featured .pf-thumb .pf-icon-wrap {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 72px;
  z-index: 1;
}
.post-featured .pf-body {
  flex: 1;
  padding: 28px 26px;
  display: flex; flex-direction: column; justify-content: center;
  gap: 12px;
  background: var(--b-card);
}
.post-featured .pf-label {
  font-size: 10px; font-weight: 800;
  text-transform: uppercase; letter-spacing: .12em;
  color: var(--b-lime); opacity: .8;
}
.post-featured .pf-title {
  font-size: clamp(17px, 2vw, 22px);
  font-weight: 800; color: #fff;
  line-height: 1.3; letter-spacing: -.015em;
}
.post-featured .pf-excerpt {
  font-size: 13.5px; color: var(--b-muted);
  line-height: 1.7;
  display: -webkit-box; -webkit-line-clamp: 3;
  -webkit-box-orient: vertical; overflow: hidden;
}
.post-featured .pf-meta {
  display: flex; align-items: center; justify-content: space-between;
  margin-top: 6px;
}
.post-featured .pf-date { font-size: 12px; color: var(--b-faint); }
.post-featured .pf-read {
  font-size: 12px; font-weight: 700; color: var(--b-lime);
  display: flex; align-items: center; gap: 4px;
}

/* ── POST LIST ITEM ── */
.post-list { display: flex; flex-direction: column; gap: 0; }
.post-item {
  display: flex;
  flex-direction: row;
  background: var(--b-card);
  border: 1px solid var(--b-border);
  border-radius: 14px; overflow: hidden;
  text-decoration: none; color: inherit;
  transition: border-color .2s, background .2s, transform .2s;
  margin-bottom: 12px;
}
.post-item:last-child { margin-bottom: 0; }
.post-item:hover {
  border-color: rgba(200,255,0,.25);
  background: var(--b-card-h);
  transform: translateX(3px);
}
.pi-thumb {
  width: 140px; min-width: 140px; overflow: hidden;
  position: relative; flex-shrink: 0;
  min-height: 100px;
  display: flex; align-items: center; justify-content: center;
}
.pi-thumb img {
  position: absolute; inset: 0;
  width:100%;height:100%;object-fit:cover;transition:transform .35s; display:block;
  z-index: 2;
}
.post-item:hover .pi-thumb img { transform: scale(1.07); }
.pi-thumb-icon {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px;
  z-index: 1;
}
.pi-body { flex:1; padding: 14px 18px; display: flex; flex-direction: column; gap: 6px; justify-content: center; background: var(--b-card); }
.pi-tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 800;
  text-transform: uppercase; letter-spacing: .09em;
}
.pi-title {
  font-size: 14.5px; font-weight: 700; color: #fff;
  line-height: 1.4;
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
}
.pi-excerpt {
  font-size: 12.5px; color: var(--b-muted);
  line-height: 1.65;
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
}
.pi-meta {
  display: flex; align-items: center; gap: 10px; margin-top: 2px;
  font-size: 11.5px; color: var(--b-faint);
}
.pi-meta .read-link { color: var(--b-lime); font-weight: 700; margin-left: auto; }

/* Admin overlay */
.pi-admin {
  position: absolute; top: 6px; left: 6px;
  display: flex; gap: 4px; opacity: 0; transition: opacity .2s; z-index: 5;
}
.pi-thumb { position: relative; }
.post-item:hover .pi-admin { opacity: 1; }
.adm-mini {
  width: 26px; height: 26px; border-radius: 6px;
  border: none; cursor: pointer; font-size: 12px;
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(6px); transition: transform .15s;
}
.adm-mini:hover { transform: scale(1.1); }
.adm-mini-e { background: rgba(200,255,0,.9); color: #000; }
.adm-mini-d { background: rgba(220,50,50,.85); color: #fff; }
.adm-mini-h { background: rgba(0,0,0,.65); color: #fff; }

/* ── SIDEBAR ── */
.blog-sidebar { display: flex; flex-direction: column; gap: 20px; }
.sidebar-widget {
  background: var(--b-card);
  border: 1px solid var(--b-border);
  border-radius: 16px; overflow: hidden;
}
.sw-header {
  padding: 14px 18px;
  border-bottom: 1px solid var(--b-border);
  font-size: 12px; font-weight: 800;
  text-transform: uppercase; letter-spacing: .1em;
  color: var(--b-muted);
}
.sw-body { padding: 14px 18px; }

/* Recent posts mini */
.recent-item {
  display: flex; gap: 12px; align-items: flex-start;
  padding: 10px 0; border-bottom: 1px solid var(--b-border);
  text-decoration: none; color: inherit; transition: opacity .15s;
}
.recent-item:last-child { border-bottom: none; padding-bottom: 0; }
.recent-item:hover { opacity: .75; }
.recent-thumb {
  width: 52px; height: 52px; border-radius: 9px;
  overflow: hidden; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
}
.recent-thumb img { width:100%;height:100%;object-fit:cover; }
.recent-info { flex: 1; min-width: 0; }
.recent-title {
  font-size: 12.5px; font-weight: 700; color: var(--b-text);
  line-height: 1.4;
  display: -webkit-box; -webkit-line-clamp: 2;
  -webkit-box-orient: vertical; overflow: hidden;
  margin-bottom: 4px;
}
.recent-date { font-size: 11px; color: var(--b-muted); }

/* Tag cloud */
.tag-cloud { display: flex; flex-wrap: wrap; gap: 6px; }
.tc-item {
  padding: 5px 12px; border-radius: 20px;
  font-size: 11.5px; font-weight: 600;
  background: rgba(255,255,255,.06);
  border: 1px solid var(--b-border);
  color: var(--b-muted); text-decoration: none;
  transition: all .15s; display: inline-flex; align-items: center; gap: 4px;
}
.tc-item:hover { color: var(--b-lime); border-color: rgba(200,255,0,.25); background: var(--b-lime-d); }

/* Stats widget */
.stat-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
.stat-row { display: flex; align-items: center; justify-content: space-between; }
.stat-row .sl { font-size: 13px; color: var(--b-muted); }
.stat-row .sv { font-size: 14px; font-weight: 800; color: #fff; }

/* ── EMPTY STATE ── */
.blog-empty { text-align: center; padding: 80px 24px; color: var(--b-muted); }
.blog-empty .ei { font-size: 56px; margin-bottom: 14px; }
.blog-empty p { font-size: 15px; margin-bottom: 20px; }

/* ── RESULT HEADER ── */
.result-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--b-border);
}
.result-title { font-size: 14px; font-weight: 700; color: var(--b-text); }
.result-count { font-size: 12px; color: var(--b-muted); }

/* Light mode overrides */
body:not(.dark) {
  --b-card: rgba(0,0,0,.025);
  --b-card-h: rgba(0,0,0,.04);
  --b-border: rgba(0,0,0,.1);
  --b-text: rgba(0,0,0,.88);
  --b-muted: rgba(0,0,0,.5);
  --b-faint: rgba(0,0,0,.35);
  --b-lime-d: rgba(6,94,52,.08);
}
body:not(.dark) .pi-title { color: var(--b-text); }
body:not(.dark) .post-featured .pf-title { color: var(--b-text); }
body:not(.dark) .blog-hero { /* keep dark */ }
body:not(.dark) .recent-title { color: var(--b-text); }
body:not(.dark) .stat-row .sv { color: var(--b-text); }
body:not(.dark) .tc-item { background: rgba(6,94,52,.07); color: rgba(0,0,0,.55); border-color: rgba(6,94,52,.18); }
body:not(.dark) .tc-item:hover { color: var(--b-green); border-color: rgba(6,94,52,.4); background: rgba(6,94,52,.1); }
body:not(.dark) .sidebar-widget,
body:not(.dark) .post-item,
body:not(.dark) .post-featured {
  background: #fff; border-color: #E5E7E5;
}
body:not(.dark) .post-item:hover { background: #F8FBF8; }
body:not(.dark) .ftag { color: rgba(0,0,0,.55); }
body:not(.dark) .ftag.active { color: var(--b-green); background: rgba(6,94,52,.07); border-color: rgba(6,94,52,.25); }
body:not(.dark) .blog-filters { background: #F5F8F5; border-bottom-color: #E0E5E0; }
body:not(.dark) .result-header { border-bottom-color: #E5E7E5; }
body:not(.dark) .pi-meta { color: rgba(0,0,0,.4); }
body:not(.dark) .sw-header { border-bottom-color: #E5E7E5; }
body:not(.dark) .recent-item { border-bottom-color: #E5E7E5; }
body:not(.dark) .post-featured .pf-date { color: rgba(0,0,0,.4); }
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
    <a href="blog.php<?= $search ? '?q='.urlencode($search) : '' ?>" class="ftag ftag-all <?= !$filter?'active':'' ?>">
      📰 Tất cả
    </a>
    <?php foreach($tags as $t): ?>
    <a href="blog.php?tag=<?= urlencode($t['tag']) ?><?= $search?'&q='.urlencode($search):'' ?>"
       class="ftag <?= $filter===$t['tag']?'active':'' ?>"
       style="<?= $filter===$t['tag'] ? 'color:'.htmlspecialchars($t['tag_color']).';border-color:'.htmlspecialchars($t['tag_color']).'44;background:'.htmlspecialchars($t['tag_color']).'18' : '' ?>">
      <?= htmlspecialchars($t['icon']) ?> <?= htmlspecialchars($t['tag']) ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ══ MAIN LAYOUT ══ -->
<div class="blog-layout">

  <!-- LEFT: POSTS -->
  <div>
    <?php if(empty($posts)): ?>
    <div class="blog-empty">
      <div class="ei">🔍</div>
      <p><?= $search ? "Không tìm thấy bài nào cho \"".htmlspecialchars($search)."\"" : "Chưa có bài viết nào." ?></p>
      <?php if($filter || $search): ?><a href="blog.php" style="color:var(--b-lime);font-size:14px;font-weight:700">← Xem tất cả</a><?php endif; ?>
    </div>
    <?php else: ?>

    <!-- Result header -->
    <div class="result-header">
      <span class="result-title">
        <?php if($filter): ?>
          Tag: <strong><?= htmlspecialchars($filter) ?></strong>
        <?php elseif($search): ?>
          Kết quả cho: <strong>"<?= htmlspecialchars($search) ?>"</strong>
        <?php else: ?>
          Bài viết mới nhất
        <?php endif; ?>
      </span>
      <span class="result-count"><?= count($posts) ?> bài viết</span>
    </div>

    <?php
    $featured = array_shift($posts); // First post = featured
    $bg_f = bgByColor($featured['tag_color'] ?? '#065E34');
    $hasImg_f = !empty($featured['hinh_anh']);
    ?>

    <!-- FEATURED POST -->
    <a class="post-featured" href="blog-detail.php?slug=<?= urlencode($featured['slug']) ?>">
      <div class="pf-thumb" style="background:<?= $bg_f ?>">
        <?php if($hasImg_f): ?>
          <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($featured['hinh_anh']) ?>"
               alt="<?= htmlspecialchars($featured['tieu_de']) ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="pf-icon-wrap" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;font-size:72px;background:<?= $bg_f ?>"><?= htmlspecialchars($featured['icon'] ?? '📝') ?></div>
        <?php else: ?>
          <div class="pf-icon-wrap"><?= htmlspecialchars($featured['icon'] ?? '📝') ?></div>
        <?php endif; ?>

        <!-- Tag badge -->
        <span style="position:absolute;top:14px;left:14px;padding:5px 13px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(0,0,0,.55);color:#fff;backdrop-filter:blur(6px);letter-spacing:.05em">
          <?= htmlspecialchars($featured['icon']??'') ?> <?= htmlspecialchars($featured['tag']??'') ?>
        </span>

        <?php if($_isAdmin): ?>
        <div class="pi-admin">
<button type="button"
        class="adm-mini adm-mini-e"
        onclick="event.stopPropagation();location.href='admin/posts.php?edit=<?= $featured['id'] ?>'">
    ✏️
</button>
          <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Ẩn bài?')">
            <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $featured['id'] ?>">
            <button class="adm-mini adm-mini-h" onclick="event.stopPropagation()">👁</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <div class="pf-body">
        <div class="pf-label">✦ Bài nổi bật</div>
        <h2 class="pf-title"><?= htmlspecialchars($featured['tieu_de']) ?></h2>
        <p class="pf-excerpt"><?= htmlspecialchars(mb_substr($featured['excerpt']??'',0,200)) ?></p>
        <div class="pf-meta">
          <span class="pf-date">📅 <?= date('d/m/Y',strtotime($featured['ngay_dang'])) ?> · ⏱ <?= $featured['read_time']??5 ?> phút</span>
          <span class="pf-read">Đọc ngay →</span>
        </div>
      </div>
    </a>

    <!-- POST LIST -->
    <?php if(!empty($posts)): ?>
    <div class="post-list">
      <?php foreach($posts as $p):
        $bg_p = bgByColor($p['tag_color'] ?? '#065E34');
        $hasImg_p = !empty($p['hinh_anh']);
        $tc = htmlspecialchars($p['tag_color'] ?? '#C8FF00');
      ?>
      <a class="post-item" href="blog-detail.php?slug=<?= urlencode($p['slug']) ?>">
        <div class="pi-thumb" style="background:<?= $bg_p ?>">
          <?php if($hasImg_p): ?>
            <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($p['hinh_anh']) ?>"
                 alt="<?= htmlspecialchars($p['tieu_de']) ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="pi-thumb-icon" style="display:none;position:absolute;inset:0;background:<?= $bg_p ?>"><?= htmlspecialchars($p['icon']??'📝') ?></div>
          <?php else: ?>
            <div class="pi-thumb-icon" style="background:<?= $bg_p ?>"><?= htmlspecialchars($p['icon']??'📝') ?></div>
          <?php endif; ?>

          <?php if($_isAdmin): ?>
          <div class="pi-admin">
<button type="button"
        class="adm-mini adm-mini-e"
        onclick="location.href='admin/posts.php?edit=<?= $p['id'] ?>'">
    ✏️
</button>
            <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Ẩn bài?')">
              <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="adm-mini adm-mini-h" onclick="event.stopPropagation()">👁</button>
            </form>
            <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Xoá bài?')">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="adm-mini adm-mini-d" onclick="event.stopPropagation()">🗑</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <div class="pi-body">
          <div class="pi-tag" style="color:<?= $tc ?>">
            <?= htmlspecialchars($p['icon']??'') ?> <?= htmlspecialchars($p['tag']??'') ?>
          </div>
          <div class="pi-title"><?= htmlspecialchars($p['tieu_de']) ?></div>
          <div class="pi-excerpt"><?= htmlspecialchars(mb_substr($p['excerpt']??'',0,120)) ?></div>
          <div class="pi-meta">
            <span>📅 <?= date('d/m/Y',strtotime($p['ngay_dang'])) ?></span>
            <span>⏱ <?= $p['read_time']??5 ?> phút</span>
            <span class="read-link">Đọc tiếp →</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; // end if posts ?>
  </div>

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
                   alt="" style="width:100%;height:100%;object-fit:cover"
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

<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div style="margin-bottom:12px">
          <img src="<?= SITE_URL ?>/images/logo.png" alt="FROMSHOPWHERE" style="height:50px;width:auto;object-fit:contain">
        </div>
        <p>Nền tảng mua bán phần mềm bản quyền uy tín hàng đầu Việt Nam.</p>
        <div class="social-links">
          <a class="social-link" href="#">f</a>
          <a class="social-link" href="#">in</a>
          <a class="social-link" href="#">yt</a>
          <a class="social-link" href="#">tk</a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Sản phẩm</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/products.php">Thiết kế đồ hoạ</a></li>
          <li><a href="<?= SITE_URL ?>/products.php">Văn phòng</a></li>
          <li><a href="<?= SITE_URL ?>/products.php">Bảo mật</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Hỗ trợ</h4>
        <ul>
          <li><a href="<?= SITE_URL ?>/blog.php">Hướng dẫn cài đặt</a></li>
          <li><a href="<?= SITE_URL ?>/contact.php">Liên hệ hỗ trợ</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Công ty</h4>
        <ul>
          <li><a href="#">Giới thiệu</a></li>
          <li><a href="<?= SITE_URL ?>/blog.php">Blog</a></li>
          <li><a href="#">Điều khoản</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div>
        <div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div>
        <div class="pay-badge">ZALO</div>
        <div class="pay-badge">ATM</div>
      </div>
    </div>
  </div>
</footer>

<script src="shared.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  restoreTheme(); updateCartBadge(); syncCartPanel();
});
</script>
</body>
</html>
