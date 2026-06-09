<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $tieu_de  = trim($_POST['tieu_de'] ?? '');
        $noi_dung = trim($_POST['noi_dung'] ?? '');
        $tag      = trim($_POST['tag'] ?? '');
        $icon     = trim($_POST['icon'] ?? '📝');
        $color    = trim($_POST['tag_color'] ?? '#065E34');
        $rt       = max(1,(int)($_POST['read_time'] ?? 5));
        $tt       = $_POST['trang_thai'] ?? 'nhap';
        $excerpt  = mb_substr(strip_tags($noi_dung), 0, 250);
        if (!$tieu_de || !$noi_dung) {
            $error = 'Vui lòng nhập tiêu đề và nội dung.';
        } else {
            if ($id) {
                db()->prepare("UPDATE posts SET tieu_de=:td,noi_dung=:nd,excerpt=:ex,tag=:tag,icon=:ic,tag_color=:tc,read_time=:rt,trang_thai=:tt WHERE id=:id")
                   ->execute([':td'=>$tieu_de,':nd'=>$noi_dung,':ex'=>$excerpt,':tag'=>$tag,':ic'=>$icon,':tc'=>$color,':rt'=>$rt,':tt'=>$tt,':id'=>$id]);
                $msg = 'Đã cập nhật bài viết.';
            } else {
                $count = (int)db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
                $slug  = 'bai-viet-'.($count+1);
                $i=1; while(db()->query("SELECT COUNT(*) FROM posts WHERE slug='$slug'")->fetchColumn()>0) $slug='bai-viet-'.($count+1).'-'.$i++;
                db()->prepare("INSERT INTO posts (tac_gia_id,tieu_de,slug,noi_dung,excerpt,tag,icon,tag_color,read_time,trang_thai) VALUES (?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$_SESSION['user_id'],$tieu_de,$slug,$noi_dung,$excerpt,$tag,$icon,$color,$rt,$tt]);
                $msg = 'Đã thêm bài viết mới.';
            }
        }
    }
    if ($action === 'delete') {
        db()->prepare("DELETE FROM posts WHERE id=:id")->execute([':id'=>(int)$_POST['id']]);
        $msg = 'Đã xoá bài viết.';
    }
    if ($action === 'toggle') {
        $id  = (int)$_POST['id'];
        $cur = db()->query("SELECT trang_thai FROM posts WHERE id=$id")->fetchColumn();
        $new = $cur==='da_dang' ? 'nhap' : 'da_dang';
        db()->prepare("UPDATE posts SET trang_thai=:t WHERE id=:id")->execute([':t'=>$new,':id'=>$id]);
        $msg = 'Đã '.($new==='da_dang'?'đăng':'ẩn').' bài viết.';
    }
}

$s      = trim($_GET['s'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$wp=[]; $pp=[];
if ($s)              { $wp[]="p.tieu_de LIKE :s"; $pp[':s']="%$s%"; }
if ($filter!=='all') { $wp[]="p.trang_thai=:ft";  $pp[':ft']=$filter; }
$where = $wp ? "WHERE ".implode(" AND ",$wp) : "";
$stmt  = db()->prepare("SELECT p.*,u.ho_ten tac_gia FROM posts p JOIN users u ON u.id=p.tac_gia_id $where ORDER BY p.id DESC");
$stmt->execute($pp);
$posts = $stmt->fetchAll();

$counts    = db()->query("SELECT trang_thai,COUNT(*) c FROM posts GROUP BY trang_thai")->fetchAll(PDO::FETCH_KEY_PAIR);
$total     = array_sum($counts);
$published = $counts['da_dang'] ?? 0;
$drafts    = $counts['nhap']    ?? 0;

// For public-style sidebar
$tags = db()->query("SELECT DISTINCT tag,icon,tag_color FROM posts WHERE trang_thai='da_dang' AND tag IS NOT NULL AND tag!='' ORDER BY tag")->fetchAll();

// Only show published posts in "public view", but admin sees ALL
$postsView = $posts; // admin sees all

$editPost = null;
if (isset($_GET['edit'])) {
    $ep = db()->prepare("SELECT * FROM posts WHERE id=:id");
    $ep->execute([':id'=>(int)$_GET['edit']]);
    $editPost = $ep->fetch();
}

$tag_list = [
    ['Văn phòng','📄','#185FA5'],['Thiết kế','🎨','#0F6E56'],
    ['Bảo mật','🛡️','#A32D2D'],['Hướng dẫn','📖','#065E34'],
    ['Doanh nghiệp','💼','#534AB7'],['Developer','💻','#534AB7'],
    ['Mẹo hay','💡','#BA7517'],['Lưu trữ','☁️','#185FA5'],
    ['Đánh giá','⭐','#065E34'],['Tin tức','📰','#A32D2D'],
];

function bgByColor($c) {
    $m = [
        '#185FA5'=>'linear-gradient(135deg,#1a3a6b,#2563a8)',
        '#0F6E56'=>'linear-gradient(135deg,#0a3d30,#0f6e56)',
        '#A32D2D'=>'linear-gradient(135deg,#5a1a1a,#a32d2d)',
        '#065E34'=>'linear-gradient(135deg,#022b18,#065e34)',
        '#534AB7'=>'linear-gradient(135deg,#2d2a6e,#534ab7)',
        '#BA7517'=>'linear-gradient(135deg,#5a3700,#ba7517)',
        '#1554B2'=>'linear-gradient(135deg,#0e1a4a,#1554b2)',
    ];
    return $m[$c] ?? 'linear-gradient(135deg,#022b18,#065e34)';
}
$admPageTitle = 'Bài đăng — Admin FSW';
ob_start();
?>
<style>
/* ── Copy y chang style block của blog.php ── */
/* ── Biến màu thích ứng light/dark ── */
body.adm-page.adm-light {
  --b-green:  #0F6E56;
  --b-lime:   #0F6E56;
  --b-lime-d: rgba(15,110,86,.08);
  --b-border: rgba(0,0,0,.1);
  --b-card:   #ffffff;
  --b-card-h: #F8FBF8;
  --b-text:   #1A1A18;
  --b-muted:  #5C5B56;
  --b-faint:  #888780;
}
body.adm-page.adm-dark {
  --b-green:  #5DCAA5;
  --b-lime:   #E1FCF6;
  --b-lime-d: rgba(90,220,170,.12);
  --b-border: rgba(255,255,255,.08);
  --b-card:   rgba(255,255,255,.04);
  --b-card-h: rgba(255,255,255,.07);
  --b-text:   rgba(255,255,255,.88);
  --b-muted:  rgba(255,255,255,.45);
  --b-faint:  rgba(255,255,255,.2);
}
/* ── PAGE HERO ── */
.blog-hero {
  background: linear-gradient(135deg,#011208 0%,#043D22 60%,#065E34 100%);
  padding: 40px 24px 32px; text-align: center;
  position: relative; overflow: hidden;
}
.blog-hero::before {
  content:''; position:absolute; inset:0;
  background-image: linear-gradient(rgba(200,255,0,.04) 1px,transparent 1px), linear-gradient(90deg,rgba(200,255,0,.04) 1px,transparent 1px);
  background-size: 40px 40px; pointer-events:none;
}
.blog-hero-inner { max-width:640px; margin:0 auto; position:relative; z-index:1; }
.blog-hero h1 { font-size:clamp(20px,3vw,30px); font-weight:800; color:#fff; margin:0 0 8px; letter-spacing:-.02em; line-height:1.2; }
.blog-hero h1 span { color:var(--b-lime); }
.blog-hero p { font-size:14px; color:rgba(255,255,255,.5); margin:0 0 18px; }
.hero-search {
  display:flex; max-width:480px; margin:0 auto;
  background:rgba(255,255,255,.07); border:1.5px solid rgba(255,255,255,.14);
  border-radius:12px; overflow:hidden; transition:border-color .2s,box-shadow .2s;
}
.hero-search:focus-within { border-color:var(--b-lime); box-shadow:0 0 0 3px rgba(200,255,0,.12); }
.hero-search input { flex:1; padding:11px 16px; background:transparent; border:none; outline:none; font-size:14px; color:#fff; font-family:'Plus Jakarta Sans',sans-serif; }
.hero-search input::placeholder { color:rgba(255,255,255,.35); }
.hero-search button { padding:11px 20px; background:var(--b-green); border:none; cursor:pointer; color:var(--b-lime); font-size:13px; font-weight:700; font-family:'Plus Jakarta Sans',sans-serif; border-left:1px solid rgba(255,255,255,.1); }
/* Tag filter strip */
.blog-filters { background:var(--bg,#111d16); border-bottom:1px solid var(--b-border); padding:0 24px; overflow-x:auto; scrollbar-width:none; }
.blog-filters::-webkit-scrollbar { display:none; }
.blog-filters-inner { max-width:1180px; margin:0 auto; display:flex; gap:4px; padding:10px 0; align-items:center; }
.ftag { white-space:nowrap; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; text-decoration:none; color:var(--b-muted); border:1.5px solid transparent; transition:all .15s; display:inline-flex; align-items:center; gap:5px; }
.ftag:hover { color:var(--b-text); background:rgba(255,255,255,.06); }
.ftag.active { background:var(--b-lime-d); color:var(--b-lime); border-color:rgba(200,255,0,.25); }
.ftag-all { color:var(--b-text); }
/* Main layout */
.blog-layout { max-width:1180px; margin:0 auto; padding:32px 24px; display:grid; grid-template-columns:1fr 300px; gap:36px; align-items:start; }
@media(max-width:900px){ .blog-layout{ grid-template-columns:1fr; } .blog-sidebar{ display:none; } }
/* Featured post */
.post-featured { display:flex; flex-direction:row; background:var(--b-card); border:1px solid var(--b-border); border-radius:18px; overflow:hidden; margin-bottom:24px; text-decoration:none; color:inherit; transition:border-color .25s,box-shadow .25s,transform .25s; min-height:260px; }
.post-featured:hover { border-color:rgba(200,255,0,.3); box-shadow:0 20px 60px rgba(0,0,0,.35); transform:translateY(-3px); }
.post-featured .pf-thumb { position:relative; overflow:hidden; width:45%; flex-shrink:0; min-height:260px; display:flex; align-items:center; justify-content:center; }
.post-featured .pf-thumb img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:transform .45s; z-index:2; }
.post-featured:hover .pf-thumb img { transform:scale(1.06); }
.post-featured .pf-thumb .pf-icon-wrap { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:72px; z-index:1; }
.post-featured .pf-body { flex:1; padding:28px 26px; display:flex; flex-direction:column; justify-content:center; gap:12px; background:var(--b-card); }
.post-featured .pf-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.12em; color:var(--b-lime); opacity:.8; }
.post-featured .pf-title { font-size:clamp(17px,2vw,22px); font-weight:800; color:var(--b-text); line-height:1.3; letter-spacing:-.015em; }
.post-featured .pf-excerpt { font-size:13.5px; color:var(--b-muted); line-height:1.7; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
.post-featured .pf-meta { display:flex; align-items:center; justify-content:space-between; margin-top:6px; }
.post-featured .pf-date { font-size:12px; color:var(--b-faint); }
.post-featured .pf-read { font-size:12px; font-weight:700; color:var(--b-lime); }
/* Post list */
.post-list { display:flex; flex-direction:column; gap:0; }
.post-item { display:flex; flex-direction:row; background:var(--b-card); border:1px solid var(--b-border); border-radius:14px; overflow:hidden; text-decoration:none; color:inherit; transition:border-color .2s,background .2s,transform .2s; margin-bottom:12px; }
.post-item:last-child { margin-bottom:0; }
.post-item:hover { border-color:rgba(200,255,0,.25); background:var(--b-card-h); transform:translateX(3px); }
.pi-thumb { width:140px; min-width:140px; overflow:hidden; position:relative; flex-shrink:0; min-height:100px; display:flex; align-items:center; justify-content:center; }
.pi-thumb img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:transform .35s; display:block; z-index:2; }
.post-item:hover .pi-thumb img { transform:scale(1.07); }
.pi-thumb-icon { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:32px; z-index:1; }
.pi-body { flex:1; padding:14px 18px; display:flex; flex-direction:column; gap:6px; justify-content:center; background:var(--b-card); }
.pi-tag { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.09em; }
.pi-title { font-size:14.5px; font-weight:700; color:var(--b-text); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.pi-excerpt { font-size:12.5px; color:var(--b-muted); line-height:1.65; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.pi-meta { display:flex; align-items:center; gap:10px; margin-top:2px; font-size:11.5px; color:var(--b-faint); }
.pi-meta .read-link { color:var(--b-lime); font-weight:700; margin-left:auto; }
/* Admin action overlays */
.adm-overlay { position:absolute; top:6px; right:6px; display:flex; gap:4px; opacity:0; transition:opacity .2s; z-index:10; }
.post-featured:hover .adm-overlay,
.post-item:hover .adm-overlay { opacity:1; }
.adm-mini { width:28px; height:28px; border-radius:7px; border:none; cursor:pointer; font-size:13px; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(6px); transition:transform .15s; }
.adm-mini:hover { transform:scale(1.1); }
.adm-mini-e { background:rgba(200,255,0,.9); color:#000; }
.adm-mini-d { background:rgba(220,50,50,.85); color:#fff; }
.adm-mini-t { background:rgba(0,0,0,.65); color:#fff; }
/* Draft dim */
.post-draft { opacity:.6; }
.post-draft:hover { opacity:1; }
/* Draft badge on card */
.draft-badge { position:absolute; top:10px; left:10px; background:rgba(0,0,0,.7); color:rgba(255,255,255,.7); padding:3px 8px; border-radius:20px; font-size:10px; font-weight:700; z-index:10; text-transform:uppercase; letter-spacing:.05em; }
/* Result header */
.result-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid var(--b-border); }
.result-title { font-size:14px; font-weight:700; color:var(--b-text); }
.result-count { font-size:12px; color:var(--b-muted); }
/* Sidebar */
.blog-sidebar { display:flex; flex-direction:column; gap:20px; }
.sidebar-widget { background:var(--b-card); border:1px solid var(--b-border); border-radius:16px; overflow:hidden; }
.sw-header { padding:14px 18px; border-bottom:1px solid var(--b-border); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--b-muted); }
.sw-body { padding:14px 18px; }
.recent-item { display:flex; gap:12px; align-items:flex-start; padding:10px 0; border-bottom:1px solid var(--b-border); text-decoration:none; color:inherit; transition:opacity .15s; }
.recent-item:last-child { border-bottom:none; padding-bottom:0; }
.recent-item:hover { opacity:.75; }
.recent-thumb { width:52px; height:52px; border-radius:9px; overflow:hidden; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:22px; }
.recent-thumb img { width:100%; height:100%; object-fit:cover; }
.recent-info { flex:1; min-width:0; }
.recent-title { font-size:12.5px; font-weight:700; color:var(--b-text); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-bottom:4px; }
.recent-date { font-size:11px; color:var(--b-muted); }
.tag-cloud { display:flex; flex-wrap:wrap; gap:6px; }
.tc-item { padding:5px 12px; border-radius:20px; font-size:11.5px; font-weight:600; background:rgba(255,255,255,.06); border:1px solid var(--b-border); color:var(--b-muted); text-decoration:none; transition:all .15s; display:inline-flex; align-items:center; gap:4px; }
.tc-item:hover { color:var(--b-lime); border-color:rgba(200,255,0,.25); background:var(--b-lime-d); }
.stat-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; }
.stat-row { display:flex; align-items:center; justify-content:space-between; }
.stat-row .sl { font-size:13px; color:var(--b-muted); }
.stat-row .sv { font-size:14px; font-weight:800; color:var(--b-text); }
/* Blog empty */
.blog-empty { text-align:center; padding:64px 24px; color:var(--b-muted); }
.blog-empty .ei { font-size:48px; margin-bottom:12px; }


.adm-light .blog-filters, body:not(.dark) .blog-filters {
  background: #F1EFE8 !important;
}
.adm-light .ftag, body:not(.dark) .ftag {
  color: #5C5B56 !important;
}
.adm-light .ftag.active, body:not(.dark) .ftag.active {
  background: rgba(15,110,86,.1) !important;
  color: #0F6E56 !important;
  border-color: rgba(15,110,86,.25) !important;
}
/* post-featured in light mode */
.adm-light .post-featured,
.adm-light .post-item,
.adm-light .sidebar-widget {
  border-color: #D0CFC7 !important;
}
.adm-light .post-featured:hover {
  border-color: rgba(15,110,86,.4) !important;
  box-shadow: 0 8px 32px rgba(15,110,86,.12) !important;
}
.adm-light .post-item:hover {
  border-color: rgba(15,110,86,.3) !important;
  background: #F8FBF8 !important;
  transform: translateX(3px);
}
.adm-light .result-header {
  border-bottom-color: #D0CFC7 !important;
}
.adm-light .sw-header {
  border-bottom-color: #D0CFC7 !important;
}
.adm-light .recent-item {
  border-bottom-color: #E8ECE4 !important;
}
.adm-light .tc-item {
  background: rgba(15,110,86,.07) !important;
  border-color: rgba(15,110,86,.15) !important;
  color: #0F6E56 !important;
}
.adm-light .tc-item:hover {
  background: rgba(15,110,86,.14) !important;
  border-color: rgba(15,110,86,.3) !important;
}

/* ── EDITOR MODAL ── */
.editor-overlay { position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:600; display:none; align-items:flex-start; justify-content:center; padding:24px 20px; overflow-y:auto; backdrop-filter:blur(6px); }
.editor-overlay.open { display:flex; }
.editor-panel { background:#fff; border-radius:20px; width:min(860px,100%); margin:auto; box-shadow:0 32px 80px rgba(0,0,0,.3); animation:panelIn .28s cubic-bezier(.22,1,.36,1); }
@keyframes panelIn { from{opacity:0;transform:translateY(20px) scale(.98)} to{opacity:1;transform:translateY(0) scale(1)} }
.ep-hd { padding:20px 26px 16px; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between; border-radius:20px 20px 0 0; background:#fff; }
.ep-badge { background:rgba(200,255,0,.2); color:#065E34; padding:4px 11px; border-radius:20px; font-size:11.5px; font-weight:800; }
.ep-ttl { font-size:16px; font-weight:800; color:#0A0F0B; letter-spacing:-.02em; }
.ep-x { width:32px; height:32px; border:none; background:#f0f0f0; border-radius:8px; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; transition:all .15s; }
.ep-x:hover { transform:rotate(90deg); background:#e0e0e0; }
.ep-body { padding:22px 26px; display:flex; flex-direction:column; gap:16px; background:#fff; }
.ep-foot { padding:14px 26px 22px; border-top:1px solid #eee; display:flex; gap:10px; background:#fff; border-radius:0 0 20px 20px; }
.fg { display:flex; flex-direction:column; gap:5px; }
.fg label { font-size:10.5px; font-weight:700; color:#6B7F6E; text-transform:uppercase; letter-spacing:.07em; }
.fg input,.fg select,.fg textarea { padding:10px 13px; border:1.5px solid #DDE5DE; border-radius:9px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#0A0F0B; background:#fff; outline:none; width:100%; transition:border-color .18s,box-shadow .18s; }
.fg input:focus,.fg select:focus,.fg textarea:focus { border-color:#065E34; box-shadow:0 0 0 3px rgba(6,94,52,.08); }
.fg textarea { min-height:240px; resize:vertical; line-height:1.75; font-size:13.5px; }
.fg .f-big { font-size:16px; font-weight:700; padding:12px 14px; }
.fr2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.fr3 { display:grid; grid-template-columns:2fr 1fr 1fr; gap:12px; }
.btn-save { flex:1; padding:12px; background:linear-gradient(135deg,#065E34,#0A8A4E); color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:800; cursor:pointer; font-family:'Plus Jakarta Sans',sans-serif; transition:all .18s; display:flex; align-items:center; justify-content:center; gap:8px; }
.btn-save:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(6,94,52,.28); }
.btn-cancel-e { padding:12px 18px; border:1.5px solid #DDE5DE; border-radius:10px; background:none; cursor:pointer; font-size:13px; font-weight:600; color:#6B7F6E; font-family:'Plus Jakarta Sans',sans-serif; }
.btn-cancel-e:hover { border-color:#C8D5CA; color:#0A0F0B; }
.tb-wrap { display:flex; gap:4px; flex-wrap:wrap; align-items:center; padding:8px; background:#F2F5F2; border-radius:8px; }
.tb-b { padding:5px 11px; border:1.5px solid #DDE5DE; border-radius:6px; background:#fff; cursor:pointer; font-size:11.5px; font-weight:700; color:#3B4D3E; transition:all .12s; }
.tb-b:hover { background:#E8FFF3; border-color:#6EE7B7; color:#065E34; }
.tb-sep { width:1px; background:#DDE5DE; height:18px; margin:0 3px; }
.char-c { margin-left:auto; font-size:11px; color:#A0B0A2; font-family:'JetBrains Mono',monospace; }
/* Tag picker */
.tag-pw { position:relative; }
.tag-ir { display:flex; align-items:center; border:1.5px solid #DDE5DE; border-radius:9px; overflow:hidden; cursor:pointer; background:#fff; }
.tag-ir:hover { border-color:#C8D5CA; }
.tag-pi { padding:10px 10px 10px 13px; font-size:16px; }
.tag-iv { flex:1; border:none; outline:none; padding:10px 8px; font-size:13px; font-family:'Plus Jakarta Sans',sans-serif; color:#0A0F0B; background:transparent; cursor:pointer; }
.tag-dd { position:absolute; top:calc(100% + 6px); left:0; right:0; background:#fff; border:1.5px solid #DDE5DE; border-radius:14px; box-shadow:0 16px 48px rgba(0,0,0,.14); z-index:20; display:none; padding:10px; grid-template-columns:repeat(2,1fr); gap:6px; }
.tag-dd.open { display:grid; }
.tag-o { padding:8px 12px; border-radius:8px; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:6px; border:1.5px solid transparent; transition:all .12s; color:#3B4D3E; }
.tag-o:hover { background:#F2F5F2; border-color:#DDE5DE; }
.tag-o.sel { background:#E8FFF3; border-color:#6EE7B7; color:#065E34; }
.si-info { background:#F2F5F2; border-radius:9px; padding:12px 14px; font-size:12px; color:#3B4D3E; line-height:1.7; }
</style>
<?php
$admExtraHead = ob_get_clean();
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main" style="overflow:hidden">

    <!-- TOPBAR -->
    <div class="adm-topbar">
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <strong>Bài đăng</strong></div>
      <div class="adm-topbar-right">
        <a href="<?= admThemeToggleUrl() ?>" class="adm-theme-btn" title="Đổi giao diện sáng/tối"><?= admThemeIcon() ?></a>
        <a href="<?= SITE_URL ?>/blog.php" target="_blank" class="btn btn-secondary" style="padding:6px 13px;font-size:12px">📰 Xem blog</a>
        <button class="btn btn-primary" onclick="openEditor()">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="1" x2="8" y2="15"/><line x1="1" y1="8" x2="15" y2="8"/></svg>
          Viết bài mới
        </button>
      </div>
    </div>

    <!-- HERO (giống blog.php) -->
    <div class="blog-hero">
      <div class="blog-hero-inner">
        <h1>Quản lý <span>Bài đăng</span></h1>
        <p><?= $total ?> bài viết · <?= $published ?> đã đăng · <?= $drafts ?> nháp</p>
        <form method="GET" style="display:contents">
          <div class="hero-search">
            <input type="text" name="s" value="<?= htmlspecialchars($s) ?>" placeholder="Tìm tiêu đề bài viết...">
            <button type="submit">🔍 Tìm</button>
          </div>
        </form>
      </div>
    </div>

    <!-- FILTER STRIP (giống blog.php) -->
    <div class="blog-filters">
      <div class="blog-filters-inner">
        <a href="?s=<?= urlencode($s) ?>&filter=all" class="ftag ftag-all <?= $filter==='all'?'active':'' ?>">📰 Tất cả (<?= $total ?>)</a>
        <a href="?s=<?= urlencode($s) ?>&filter=da_dang" class="ftag <?= $filter==='da_dang'?'active':'' ?>">🚀 Đã đăng (<?= $published ?>)</a>
        <a href="?s=<?= urlencode($s) ?>&filter=nhap" class="ftag <?= $filter==='nhap'?'active':'' ?>">📋 Nháp (<?= $drafts ?>)</a>
        <?php if($s): ?>
        <a href="?filter=<?= $filter ?>" class="ftag" style="color:rgba(255,255,255,.4)">✕ Xoá tìm</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- MAIN CONTENT (giống hệt blog.php) -->
    <?php if($msg): ?>
    <div style="margin:16px 24px;padding:11px 15px;border-radius:9px;background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;font-size:13px;font-weight:500">✓ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div style="margin:16px 24px;padding:11px 15px;border-radius:9px;background:#FEE8E8;color:#D92B2B;border:1px solid #FECACA;font-size:13px;font-weight:500">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="blog-layout">

      <!-- LEFT: POSTS (y chang blog.php, thêm admin controls) -->
      <div>
        <?php if(empty($postsView)): ?>
        <div class="blog-empty">
          <div class="ei">✍️</div>
          <p>Chưa có bài viết nào<?= $s ? " khớp với \"$s\"" : '' ?>.</p>
          <button onclick="openEditor()" style="background:var(--b-lime);color:#000;border:none;padding:10px 20px;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer">+ Viết bài đầu tiên</button>
        </div>
        <?php else:
          $featured = array_shift($postsView);
          $bg_f     = bgByColor($featured['tag_color'] ?? '#065E34');
          $hasImg_f = !empty($featured['hinh_anh']);
          $isDraft_f = $featured['trang_thai'] !== 'da_dang';
        ?>

        <div class="result-header">
          <span class="result-title"><?= $s ? "Kết quả: \"".htmlspecialchars($s)."\"" : ($filter==='da_dang'?'Đã xuất bản':($filter==='nhap'?'Bản nháp':'Tất cả bài viết')) ?></span>
          <span class="result-count"><?= count($postsView)+1 ?> bài</span>
        </div>

        <!-- FEATURED POST -->
        <div class="post-featured <?= $isDraft_f?'post-draft':'' ?>" style="position:relative">
          <div class="pf-thumb" style="background:<?= $bg_f ?>">
            <?php if($hasImg_f): ?>
              <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($featured['hinh_anh']) ?>"
                   alt="" onerror="this.style.display='none'">
            <?php endif; ?>
            <div class="pf-icon-wrap"><?= htmlspecialchars($featured['icon']??'📝') ?></div>
            <!-- Tag badge -->
            <span style="position:absolute;top:14px;left:14px;padding:5px 13px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(0,0,0,.55);color:#fff;backdrop-filter:blur(6px);z-index:5">
              <?= htmlspecialchars($featured['icon']??'') ?> <?= htmlspecialchars($featured['tag']??'') ?>
            </span>
            <?php if($isDraft_f): ?>
            <span class="draft-badge">Nháp</span>
            <?php endif; ?>
            <!-- Admin controls -->
            <div class="adm-overlay">
              <button class="adm-mini adm-mini-e" title="Sửa"
                onclick='openEditor(<?= json_encode($featured,JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
              <form method="POST" style="display:contents">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $featured['id'] ?>">
                <button class="adm-mini adm-mini-t" title="<?= $isDraft_f?'Đăng':'Ẩn' ?>"><?= $isDraft_f?'🚀':'⏸' ?></button>
              </form>
              <form method="POST" style="display:contents" onsubmit="return confirm('Xoá bài này?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $featured['id'] ?>">
                <button class="adm-mini adm-mini-d" title="Xoá">🗑</button>
              </form>
            </div>
          </div>
          <div class="pf-body">
            <div class="pf-label">✦ Bài mới nhất</div>
            <div class="pf-title"><?= htmlspecialchars($featured['tieu_de']) ?></div>
            <p class="pf-excerpt"><?= htmlspecialchars(mb_substr($featured['excerpt']??'',0,200)) ?></p>
            <div class="pf-meta">
              <span class="pf-date">📅 <?= date('d/m/Y',strtotime($featured['ngay_dang'])) ?> · ⏱ <?= $featured['read_time']??5 ?> phút · ✍️ <?= htmlspecialchars($featured['tac_gia']) ?></span>
              <span class="pf-read" style="cursor:pointer" onclick='openEditor(<?= json_encode($featured,JSON_UNESCAPED_UNICODE) ?>)'>✏️ Sửa bài →</span>
            </div>
          </div>
        </div>

        <!-- POST LIST -->
        <?php if(!empty($postsView)): ?>
        <div class="post-list">
          <?php foreach($postsView as $p):
            $bg_p    = bgByColor($p['tag_color'] ?? '#065E34');
            $hasImg_p = !empty($p['hinh_anh']);
            $isDraft_p = $p['trang_thai'] !== 'da_dang';
            $tc = htmlspecialchars($p['tag_color'] ?? '#C8FF00');
          ?>
          <div class="post-item <?= $isDraft_p?'post-draft':'' ?>" style="position:relative">
            <div class="pi-thumb" style="background:<?= $bg_p ?>">
              <?php if($hasImg_p): ?>
                <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($p['hinh_anh']) ?>"
                     alt="" onerror="this.style.display='none'">
              <?php endif; ?>
              <div class="pi-thumb-icon"><?= htmlspecialchars($p['icon']??'📝') ?></div>
              <?php if($isDraft_p): ?>
              <span class="draft-badge">Nháp</span>
              <?php endif; ?>
              <!-- Admin controls -->
              <div class="adm-overlay">
                <button class="adm-mini adm-mini-e" title="Sửa"
                  onclick='openEditor(<?= json_encode($p,JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                <form method="POST" style="display:contents">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="adm-mini adm-mini-t"><?= $isDraft_p?'🚀':'⏸' ?></button>
                </form>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xoá bài «<?= htmlspecialchars($p['tieu_de'],ENT_QUOTES) ?>»?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="adm-mini adm-mini-d">🗑</button>
                </form>
              </div>
            </div>
            <div class="pi-body">
              <div class="pi-tag" style="color:<?= $tc ?>"><?= htmlspecialchars($p['icon']??'') ?> <?= htmlspecialchars($p['tag']??'') ?></div>
              <div class="pi-title"><?= htmlspecialchars($p['tieu_de']) ?></div>
              <div class="pi-excerpt"><?= htmlspecialchars(mb_substr($p['excerpt']??'',0,120)) ?></div>
              <div class="pi-meta">
                <span>📅 <?= date('d/m/Y',strtotime($p['ngay_dang'])) ?></span>
                <span>⏱ <?= $p['read_time']??5 ?> phút</span>
                <span class="read-link" style="cursor:pointer" onclick='openEditor(<?= json_encode($p,JSON_UNESCAPED_UNICODE) ?>)'>✏️ Sửa →</span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- RIGHT: SIDEBAR (y chang blog.php) -->
      <aside class="blog-sidebar">
        <!-- Quick actions -->
        <div class="sidebar-widget">
          <div class="sw-header">⚡ Thao tác nhanh</div>
          <div class="sw-body" style="display:flex;flex-direction:column;gap:8px">
            <button onclick="openEditor()" style="width:100%;padding:10px;background:linear-gradient(135deg,#065E34,#0A8A4E);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif">✍️ Viết bài mới</button>
            <a href="<?= SITE_URL ?>/blog.php" target="_blank" style="display:block;text-align:center;padding:9px;background:rgba(255,255,255,.06);border:1px solid var(--b-border);border-radius:9px;font-size:13px;font-weight:600;color:var(--b-muted);text-decoration:none">📰 Xem blog công khai</a>
          </div>
        </div>

        <!-- Recent posts -->
        <div class="sidebar-widget">
          <div class="sw-header">📌 Bài viết gần đây</div>
          <div class="sw-body" style="padding:10px 14px">
            <?php
            $recent = db()->query("SELECT id,tieu_de,slug,icon,tag_color,hinh_anh,ngay_dang,trang_thai FROM posts ORDER BY ngay_dang DESC LIMIT 6")->fetchAll();
            foreach($recent as $r):
              $rb = bgByColor($r['tag_color']??'#065E34');
            ?>
            <a class="recent-item" href="?edit=<?= $r['id'] ?>" onclick="event.preventDefault();openEditor(<?= json_encode($r,JSON_UNESCAPED_UNICODE) ?>)">
              <div class="recent-thumb" style="background:<?= $rb ?>">
                <?php if(!empty($r['hinh_anh'])): ?>
                  <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($r['hinh_anh']) ?>" alt="" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none'">
                <?php else: ?>
                  <?= htmlspecialchars($r['icon']??'📝') ?>
                <?php endif; ?>
              </div>
              <div class="recent-info">
                <div class="recent-title"><?= htmlspecialchars($r['tieu_de']) ?></div>
                <div class="recent-date"><?= $r['trang_thai']==='da_dang'?'🟢':'📋' ?> <?= date('d/m/Y',strtotime($r['ngay_dang'])) ?></div>
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
              <a href="?filter=all&s=<?= urlencode($t['tag']) ?>" class="tc-item">
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
            <ul class="stat-list">
              <li class="stat-row"><span class="sl">📝 Tổng bài</span><span class="sv"><?= $total ?></span></li>
              <li class="stat-row"><span class="sl">🚀 Đã đăng</span><span class="sv"><?= $published ?></span></li>
              <li class="stat-row"><span class="sl">📋 Nháp</span><span class="sv"><?= $drafts ?></span></li>
            </ul>
          </div>
        </div>
      </aside>

    </div><!-- /blog-layout -->
  </main>
</div>

<!-- EDITOR MODAL -->
<div class="editor-overlay" id="editorOverlay" onclick="if(event.target===this)closeEditor()">
  <div class="editor-panel">
    <div class="ep-hd">
      <div style="display:flex;align-items:center;gap:10px">
        <span class="ep-badge" id="epBadge">✍️ Mới</span>
        <span class="ep-ttl" id="epTitle">Viết bài mới</span>
      </div>
      <button class="ep-x" onclick="closeEditor()">✕</button>
    </div>
    <form method="POST" id="postForm">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="fId" value="0">
      <input type="hidden" name="tag_color" id="fColor" value="#065E34">
      <input type="hidden" name="icon" id="fIconH" value="📝">
      <div class="ep-body">
        <div class="fg">
          <label>Tiêu đề *</label>
          <input class="f-big" type="text" name="tieu_de" id="fTitle" placeholder="Nhập tiêu đề bài viết..." required>
        </div>
        <div class="fr3">
          <div class="fg">
            <label>Tag / Danh mục</label>
            <div class="tag-pw">
              <div class="tag-ir" onclick="toggleTagDd()">
                <span class="tag-pi" id="tagIcon">📝</span>
                <input class="tag-iv" type="text" name="tag" id="fTag" placeholder="Chọn tag..." readonly>
                <span style="padding:10px 12px;color:#A0B0A2;font-size:11px">▾</span>
              </div>
              <div class="tag-dd" id="tagDd">
                <?php foreach($tag_list as [$tn,$ti,$tc2]): ?>
                <div class="tag-o" onclick="selTag('<?= $tn ?>','<?= $ti ?>','<?= $tc2 ?>')" style="color:<?= $tc2 ?>">
                  <span><?= $ti ?></span><?= $tn ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="fg">
            <label>Icon</label>
            <input type="text" id="fIconD" placeholder="📝" style="font-size:18px;text-align:center"
                   oninput="document.getElementById('fIconH').value=this.value">
          </div>
          <div class="fg">
            <label>Phút đọc</label>
            <input type="number" name="read_time" id="fRt" value="5" min="1" max="60">
          </div>
        </div>
        <div class="fg">
          <label>Nội dung *</label>
          <div class="tb-wrap">
            <button type="button" class="tb-b" onclick="ins('## Tiêu đề\n\n')">H2</button>
            <button type="button" class="tb-b" onclick="ins('### Phụ đề\n\n')">H3</button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-b" onclick="ins('**đậm**')"><b>B</b></button>
            <button type="button" class="tb-b" onclick="ins('*nghiêng*')"><i>I</i></button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-b" onclick="ins('\n• ')">• List</button>
            <button type="button" class="tb-b" onclick="ins('\n\n---\n\n')">── Line</button>
            <span class="char-c" id="charC">0 ký tự</span>
          </div>
          <textarea name="noi_dung" id="fContent" placeholder="Viết nội dung ở đây..." required oninput="updC(this)"></textarea>
        </div>
        <div class="fr2">
          <div class="fg">
            <label>Trạng thái</label>
            <select name="trang_thai" id="fSt">
              <option value="nhap">📋 Nháp</option>
              <option value="da_dang">🚀 Đăng ngay</option>
              <option value="an">🔒 Ẩn</option>
            </select>
          </div>
          <div class="si-info">
            <div><b>📋 Nháp</b> — chỉ admin thấy</div>
            <div style="margin-top:5px"><b>🚀 Đăng</b> — hiện trên blog</div>
            <div style="margin-top:5px"><b>🔒 Ẩn</b> — tạm ẩn</div>
          </div>
        </div>
      </div>
      <div class="ep-foot">
        <button type="submit" class="btn-save">💾 Lưu bài viết</button>
        <button type="button" class="btn-cancel-e" onclick="closeEditor()">Huỷ</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditor(p) {
  document.getElementById('editorOverlay').classList.add('open');
  document.body.style.overflow='hidden';
  if(p){
    document.getElementById('epBadge').textContent='✏️ Sửa';
    document.getElementById('epTitle').textContent='Sửa bài viết';
    document.getElementById('fId').value=p.id;
    document.getElementById('fTitle').value=p.tieu_de||'';
    document.getElementById('fContent').value=p.noi_dung||'';
    document.getElementById('fTag').value=p.tag||'';
    document.getElementById('fIconD').value=p.icon||'📝';
    document.getElementById('fIconH').value=p.icon||'📝';
    document.getElementById('tagIcon').textContent=p.icon||'📝';
    document.getElementById('fRt').value=p.read_time||5;
    document.getElementById('fSt').value=p.trang_thai||'nhap';
    document.getElementById('fColor').value=p.tag_color||'#065E34';
    hiTag(p.tag);
    updC(document.getElementById('fContent'));
  } else {
    document.getElementById('epBadge').textContent='✍️ Mới';
    document.getElementById('epTitle').textContent='Viết bài mới';
    document.getElementById('fId').value='0';
    document.getElementById('postForm').reset();
    document.getElementById('fId').value='0';
    document.getElementById('fIconH').value='📝';
    document.getElementById('tagIcon').textContent='📝';
    document.querySelectorAll('.tag-o').forEach(e=>e.classList.remove('sel'));
    updC(document.getElementById('fContent'));
  }
  setTimeout(()=>document.getElementById('fTitle').focus(),100);
}
function closeEditor(){
  document.getElementById('editorOverlay').classList.remove('open');
  document.body.style.overflow='';
  document.getElementById('tagDd').classList.remove('open');
}
function toggleTagDd(){document.getElementById('tagDd').classList.toggle('open');}
function selTag(n,i,c){
  document.getElementById('fTag').value=n;
  document.getElementById('fIconD').value=i;
  document.getElementById('fIconH').value=i;
  document.getElementById('fColor').value=c;
  document.getElementById('tagIcon').textContent=i;
  document.getElementById('tagDd').classList.remove('open');
  hiTag(n);
}
function hiTag(n){document.querySelectorAll('.tag-o').forEach(e=>e.classList.toggle('sel',e.textContent.trim().includes(n)));}
document.addEventListener('click',e=>{
  const dd=document.getElementById('tagDd');
  if(dd&&!dd.closest('.tag-pw').contains(e.target))dd.classList.remove('open');
});
function ins(t){
  const ta=document.getElementById('fContent');
  const s=ta.selectionStart,e=ta.selectionEnd;
  ta.value=ta.value.slice(0,s)+t+ta.value.slice(e);
  ta.selectionStart=ta.selectionEnd=s+t.length;
  ta.focus();updC(ta);
}
function updC(ta){
  const l=ta.value.length,m=Math.max(1,Math.ceil(l/800));
  document.getElementById('charC').textContent=l.toLocaleString('vi-VN')+' ký · ~'+m+' phút';
  document.getElementById('fRt').value=m;
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeEditor();});
<?php if($editPost):?>openEditor(<?=json_encode($editPost,JSON_UNESCAPED_UNICODE)?>);<?php endif;?>
</script>
</body>
</html>