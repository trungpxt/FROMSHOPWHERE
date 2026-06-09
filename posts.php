<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$msg = ''; $error = '';

/* ══ ACTIONS ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── THÊM / SỬA ── */
    if ($action === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $tieu_de  = trim($_POST['tieu_de'] ?? '');
        $noi_dung = trim($_POST['noi_dung'] ?? '');
        $tag      = trim($_POST['tag'] ?? '');
        $icon     = trim($_POST['icon'] ?? '📝');
        $color    = trim($_POST['tag_color'] ?? '#065E34');
        $rt       = max(1, (int)($_POST['read_time'] ?? 5));
        $tt       = $_POST['trang_thai'] ?? 'nhap';
        $excerpt  = mb_substr(strip_tags($noi_dung), 0, 250);

        $slug_base = 'bai-viet-' . ($id ?: time());
        $slug = $slug_base;
        if (!$id) {
            $count = (int)db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            $slug = 'bai-viet-' . ($count + 1);
            $i = 1;
            while (db()->query("SELECT COUNT(*) FROM posts WHERE slug='$slug'")->fetchColumn() > 0) {
                $slug = 'bai-viet-' . ($count + 1) . '-' . $i++;
            }
        }

        if (!$tieu_de || !$noi_dung) {
            $error = 'Vui lòng nhập tiêu đề và nội dung bài viết.';
        } else {
            if ($id) {
                db()->prepare("UPDATE posts SET tieu_de=:td, noi_dung=:nd, excerpt=:ex, tag=:tag, icon=:ic, tag_color=:tc, read_time=:rt, trang_thai=:tt WHERE id=:id")
                   ->execute([':td'=>$tieu_de,':nd'=>$noi_dung,':ex'=>$excerpt,':tag'=>$tag,':ic'=>$icon,':tc'=>$color,':rt'=>$rt,':tt'=>$tt,':id'=>$id]);
                $msg = 'Đã cập nhật bài viết thành công.';
            } else {
                db()->prepare("INSERT INTO posts (tac_gia_id,tieu_de,slug,noi_dung,excerpt,tag,icon,tag_color,read_time,trang_thai) VALUES (?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$_SESSION['user_id'],$tieu_de,$slug,$noi_dung,$excerpt,$tag,$icon,$color,$rt,$tt]);
                $msg = 'Đã thêm bài viết mới thành công.';
            }
        }
    }

    if ($action === 'delete') {
        db()->prepare("DELETE FROM posts WHERE id=:id")->execute([':id'=>(int)$_POST['id']]);
        $msg = 'Đã xoá bài viết.';
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $cur = db()->query("SELECT trang_thai FROM posts WHERE id=$id")->fetchColumn();
        $new = $cur === 'da_dang' ? 'nhap' : 'da_dang';
        db()->prepare("UPDATE posts SET trang_thai=:t WHERE id=:id")->execute([':t'=>$new,':id'=>$id]);
        $msg = 'Đã ' . ($new === 'da_dang' ? 'đăng' : 'ẩn') . ' bài viết.';
    }
}

$s = trim($_GET['s'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$where_parts = [];
$params = [];
if ($s) { $where_parts[] = "tieu_de LIKE :s"; $params[':s'] = "%$s%"; }
if ($filter !== 'all') { $where_parts[] = "p.trang_thai = :ft"; $params[':ft'] = $filter; }
$where = $where_parts ? "WHERE " . implode(" AND ", $where_parts) : "";
$stmt = db()->prepare("SELECT p.*, u.ho_ten tac_gia FROM posts p JOIN users u ON u.id=p.tac_gia_id $where ORDER BY p.id DESC");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$counts = db()->query("SELECT trang_thai, COUNT(*) c FROM posts GROUP BY trang_thai")->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);
$published = $counts['da_dang'] ?? 0;
$drafts = $counts['nhap'] ?? 0;

$editPost = null;
if (isset($_GET['edit'])) {
    $ep = db()->prepare("SELECT * FROM posts WHERE id=:id");
    $ep->execute([':id'=>(int)$_GET['edit']]);
    $editPost = $ep->fetch();
}

$tags = [
    ['Văn phòng','📄','#185FA5'],['Thiết kế','🎨','#0F6E56'],
    ['Bảo mật','🛡️','#A32D2D'],['Hướng dẫn','📖','#065E34'],
    ['Doanh nghiệp','💼','#534AB7'],['Developer','💻','#534AB7'],
    ['Mẹo hay','💡','#BA7517'],['Lưu trữ','☁️','#185FA5'],
    ['Đánh giá','⭐','#065E34'],['Tin tức','📰','#A32D2D'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bài đăng — Admin FSW</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/style.css">
<link rel="stylesheet" href="<?= SITE_URL ?>/admin/admin.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ══ BLOG ADMIN — REDESIGNED ══ */
:root {
  --ink: #0A0F0B;
  --ink-2: #3B4D3E;
  --ink-3: #6B7F6E;
  --ink-4: #A0B0A2;
  --bg: #F4F6F3;
  --bg-2: #EAEDE8;
  --white: #FFFFFF;
  --border: #DDE5DE;
  --border-2: #C8D5CA;
  --green: #065E34;
  --green-2: #0A8A4E;
  --lime: #C8FF00;
  --lime-dim: rgba(200,255,0,.12);
  --red: #D92B2B;
  --red-dim: #FEE8E8;
  --yellow: #E07B00;
  --yellow-dim: #FFF3DC;
  --blue: #1554B2;
  --blue-dim: #E6EFFE;
  --radius: 14px;
  --radius-sm: 9px;
  --shadow: 0 2px 12px rgba(0,0,0,.07);
  --shadow-lg: 0 16px 48px rgba(0,0,0,.14);
}

body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--ink); }

/* ── LAYOUT ── */
.adm { display: flex; min-height: 100vh; }
.adm-side {
  width: 240px; flex-shrink: 0;
  background: var(--ink); 
  position: sticky; top: 0; height: 100vh;
  overflow-y: auto; display: flex; flex-direction: column;
  border-right: 1px solid rgba(255,255,255,.06);
}
.adm-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }

/* ── SIDEBAR ── */
.adm-logo { padding: 22px 20px; border-bottom: 1px solid rgba(255,255,255,.07); }
.adm-logo img { height: 34px; }
.adm-nav { padding: 14px 10px; flex: 1; }
.adm-nav a {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px; border-radius: 9px;
  color: rgba(255,255,255,.38); font-size: 13px; font-weight: 500;
  text-decoration: none; margin-bottom: 1px;
  transition: all .15s;
}
.adm-nav a:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.75); }
.adm-nav a.on { background: rgba(200,255,0,.1); color: var(--lime); font-weight: 700; }
.adm-nav .sep { height: 1px; background: rgba(255,255,255,.07); margin: 10px 2px; }

/* ── TOPBAR ── */
.adm-topbar {
  background: var(--white); border-bottom: 1px solid var(--border);
  padding: 0 28px; height: 58px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 80;
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
}
.adm-topbar-left { display: flex; align-items: center; gap: 16px; }
.breadcrumb { font-size: 13px; color: var(--ink-3); display: flex; align-items: center; gap: 6px; }
.breadcrumb strong { color: var(--ink); font-weight: 700; }
.breadcrumb span { opacity: .4; }
.adm-topbar-right { display: flex; gap: 8px; }
.btn-new {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 18px; border-radius: 9px;
  background: var(--green); color: var(--lime);
  font-size: 13px; font-weight: 700; border: none; cursor: pointer;
  font-family: 'Plus Jakarta Sans', sans-serif;
  transition: all .18s; text-decoration: none;
  letter-spacing: .01em;
}
.btn-new:hover { background: #054d2a; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(6,94,52,.25); }
.btn-new svg { width: 15px; height: 15px; }

/* ── PAGE CONTENT ── */
.adm-content { padding: 26px 28px; flex: 1; }

/* ── STATS ROW ── */
.stats-row {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 14px; margin-bottom: 22px;
}
.stat-pill {
  background: var(--white); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 18px 20px;
  display: flex; align-items: center; gap: 14px;
  transition: border-color .2s, box-shadow .2s;
}
.stat-pill:hover { border-color: var(--border-2); box-shadow: var(--shadow); }
.stat-pill-icon {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}
.stat-pill-icon.all { background: #EEF3FF; }
.stat-pill-icon.pub { background: #E8FFF3; }
.stat-pill-icon.drf { background: var(--yellow-dim); }
.stat-pill-num {
  font-size: 26px; font-weight: 800; line-height: 1;
  color: var(--ink); letter-spacing: -.03em;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.stat-pill-lbl { font-size: 12px; color: var(--ink-3); font-weight: 500; margin-top: 2px; }

/* ── TOOLBAR ── */
.toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.toolbar-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.search-wrap {
  position: relative; display: flex; align-items: center;
}
.search-wrap svg {
  position: absolute; left: 11px; color: var(--ink-4);
  width: 14px; height: 14px; pointer-events: none;
}
.search-input {
  padding: 8px 12px 8px 34px;
  border: 1.5px solid var(--border); border-radius: 9px;
  font-size: 13px; font-family: 'Plus Jakarta Sans', sans-serif;
  color: var(--ink); background: var(--white); outline: none;
  min-width: 240px; transition: border-color .18s;
}
.search-input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(6,94,52,.07); }
.btn-search {
  padding: 8px 16px; background: var(--green); color: var(--lime);
  border: none; border-radius: 9px; font-size: 13px; font-weight: 700;
  cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif;
  transition: background .15s;
}
.btn-search:hover { background: #054d2a; }

/* Filter tabs */
.filter-tabs { display: flex; gap: 4px; }
.filter-tab {
  padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
  cursor: pointer; text-decoration: none; border: 1.5px solid transparent;
  color: var(--ink-3); transition: all .15s; background: none;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.filter-tab:hover { background: var(--bg-2); color: var(--ink); }
.filter-tab.active { background: var(--white); border-color: var(--border); color: var(--ink); box-shadow: var(--shadow); }

/* ── TABLE CARD ── */
.table-card {
  background: var(--white); border: 1px solid var(--border);
  border-radius: var(--radius); overflow: hidden;
}
.table-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
.table-card th {
  padding: 10px 16px; background: #FAFBFA;
  color: var(--ink-4); text-align: left;
  font-size: 10.5px; text-transform: uppercase; letter-spacing: .08em;
  font-weight: 700; border-bottom: 1px solid var(--border); white-space: nowrap;
}
.table-card td {
  padding: 13px 16px; border-bottom: 1px solid var(--bg-2);
  vertical-align: middle;
}
.table-card tr:last-child td { border-bottom: none; }
.table-card tbody tr { transition: background .1s; }
.table-card tbody tr:hover td { background: #FAFBFA; }

/* Post title cell */
.post-title-cell .ptitle {
  font-size: 13.5px; font-weight: 700; color: var(--ink);
  max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  display: block;
}
.post-title-cell .pslug {
  font-family: 'JetBrains Mono', monospace;
  font-size: 10.5px; color: var(--ink-4); margin-top: 3px; display: block;
}

/* Tag badge */
.tag-chip {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 4px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 600;
  background: var(--bg-2);
}

/* Status */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 700; white-space: nowrap;
}
.s-pub  { background: #D1FAE5; color: #065F46; }
.s-drf  { background: #F3F4F6; color: #4B5563; }
.s-hid  { background: #FEE2E2; color: #9B1C1C; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.s-pub .status-dot  { background: #10B981; }
.s-drf .status-dot  { background: #9CA3AF; }
.s-hid .status-dot  { background: #EF4444; }

/* Action buttons */
.act-row { display: flex; gap: 4px; align-items: center; }
.act-btn {
  width: 30px; height: 30px; border-radius: 7px; border: none;
  cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
  font-size: 13px; transition: all .13s; text-decoration: none;
}
.act-btn:hover { transform: scale(1.08); }
.ab-edit   { background: #EEF3FF; color: #1554B2; }
.ab-toggle { background: var(--yellow-dim); color: var(--yellow); }
.ab-view   { background: #E8FFF3; color: var(--green); }
.ab-del    { background: var(--red-dim); color: var(--red); }

/* ── ALERT MESSAGES ── */
.adm-alert {
  padding: 12px 16px; border-radius: var(--radius-sm);
  font-size: 13px; font-weight: 500; margin-bottom: 18px;
  display: flex; align-items: center; gap: 8px;
}
.adm-alert-ok  { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
.adm-alert-err { background: var(--red-dim); color: var(--red); border: 1px solid #FECACA; }

/* ── EMPTY STATE ── */
.empty-wrap {
  text-align: center; padding: 64px 24px; color: var(--ink-3);
}
.empty-wrap .eico { font-size: 52px; margin-bottom: 14px; display: block; }
.empty-wrap p { font-size: 14px; margin-bottom: 18px; }

/* ════════════════════════════════════
   EDITOR MODAL — FULL REDESIGN
════════════════════════════════════ */
.editor-overlay {
  position: fixed; inset: 0; background: rgba(8,14,9,.55);
  z-index: 600; display: none; align-items: flex-start; justify-content: center;
  padding: 24px 20px; overflow-y: auto;
  backdrop-filter: blur(6px);
}
.editor-overlay.open { display: flex; }

.editor-panel {
  background: var(--white); border-radius: 20px;
  width: min(900px, 100%); margin: auto;
  box-shadow: 0 32px 80px rgba(0,0,0,.2), 0 0 0 1px rgba(0,0,0,.06);
  animation: panelIn .28s cubic-bezier(.22,1,.36,1);
  display: flex; flex-direction: column;
}
@keyframes panelIn {
  from { opacity: 0; transform: translateY(24px) scale(.98); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Editor header */
.ep-header {
  padding: 22px 28px 18px;
  border-bottom: 1px solid var(--bg-2);
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; background: var(--white);
  border-radius: 20px 20px 0 0; z-index: 2;
}
.ep-header-left { display: flex; align-items: center; gap: 12px; }
.ep-title-badge {
  background: var(--lime-dim); color: var(--green);
  padding: 5px 12px; border-radius: 20px;
  font-size: 12px; font-weight: 700; letter-spacing: .02em;
}
.ep-title-text {
  font-size: 17px; font-weight: 800; color: var(--ink);
  letter-spacing: -.02em; font-family: 'Plus Jakarta Sans', sans-serif;
}
.ep-close {
  width: 33px; height: 33px; border: none;
  background: var(--bg-2); border-radius: 8px; cursor: pointer;
  font-size: 15px; color: var(--ink-3);
  display: flex; align-items: center; justify-content: center;
  transition: all .15s;
}
.ep-close:hover { background: var(--bg); color: var(--ink); transform: rotate(90deg); }

/* Editor body */
.ep-body { padding: 26px 28px; display: flex; flex-direction: column; gap: 20px; }

/* Two-pane layout for metadata */
.ep-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ep-meta-3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; }

/* Form field */
.ef-group { display: flex; flex-direction: column; gap: 6px; }
.ef-label {
  font-size: 11px; font-weight: 700; color: var(--ink-3);
  text-transform: uppercase; letter-spacing: .07em;
}
.ef-input, .ef-select, .ef-textarea {
  padding: 10px 13px; border: 1.5px solid var(--border);
  border-radius: var(--radius-sm); font-size: 13px;
  font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink);
  background: var(--white); outline: none;
  transition: border-color .18s, box-shadow .18s;
  box-sizing: border-box; width: 100%;
}
.ef-input:focus, .ef-select:focus, .ef-textarea:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 3px rgba(6,94,52,.08);
}
.ef-input-title {
  font-size: 16px; font-weight: 700; padding: 13px 15px;
}
.ef-select { cursor: pointer; }

/* Tag picker dropdown */
.tag-picker-wrap { position: relative; }
.tag-input-row {
  display: flex; align-items: center; gap: 0;
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  overflow: hidden; transition: border-color .18s, box-shadow .18s;
  cursor: pointer; background: var(--white);
}
.tag-input-row:focus-within {
  border-color: var(--green); box-shadow: 0 0 0 3px rgba(6,94,52,.08);
}
.tag-preview-icon { padding: 10px 10px 10px 13px; font-size: 16px; }
.tag-input-val {
  flex: 1; border: none; outline: none; padding: 10px 8px;
  font-size: 13px; font-family: 'Plus Jakarta Sans', sans-serif;
  color: var(--ink); background: transparent; cursor: pointer;
}
.tag-arrow { padding: 10px 12px; color: var(--ink-4); font-size: 11px; }

.tag-dropdown {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: var(--radius); box-shadow: var(--shadow-lg);
  z-index: 10; display: none; padding: 10px;
  grid-template-columns: repeat(2, 1fr); gap: 6px;
}
.tag-dropdown.open { display: grid; }
.tag-opt {
  padding: 9px 12px; border-radius: 8px; cursor: pointer;
  font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 7px;
  border: 1.5px solid transparent; transition: all .13s;
  color: var(--ink-2);
}
.tag-opt:hover { background: var(--bg-2); border-color: var(--border); }
.tag-opt.selected { background: #E8FFF3; border-color: #6EE7B7; color: var(--green); }

/* Content textarea with toolbar */
.content-wrap { display: flex; flex-direction: column; gap: 6px; }
.content-toolbar {
  display: flex; gap: 4px; flex-wrap: wrap; align-items: center;
  padding: 8px; background: var(--bg); border-radius: 8px;
}
.tb-btn {
  padding: 5px 11px; border: 1.5px solid var(--border);
  border-radius: 6px; background: var(--white); cursor: pointer;
  font-size: 11.5px; font-weight: 700; color: var(--ink-2);
  font-family: 'Plus Jakarta Sans', sans-serif;
  transition: all .12s; white-space: nowrap;
}
.tb-btn:hover { background: #E8FFF3; border-color: #6EE7B7; color: var(--green); }
.tb-sep { width: 1px; background: var(--border); height: 20px; margin: 0 4px; flex-shrink: 0; }
.tb-count {
  margin-left: auto; font-size: 11px; color: var(--ink-4); font-weight: 500;
  font-family: 'JetBrains Mono', monospace;
}
.ef-textarea {
  min-height: 300px; resize: vertical; line-height: 1.75; font-size: 14px;
}

/* Status info box */
.status-info {
  background: var(--bg); border-radius: 9px; padding: 12px 14px;
  font-size: 12px; color: var(--ink-2); line-height: 1.65;
  display: flex; flex-direction: column; justify-content: center;
}
.status-info b { color: var(--ink); }

/* Preview panel */
.preview-panel {
  background: #FAFCFA; border: 1.5px solid var(--border);
  border-radius: var(--radius-sm); padding: 16px 18px;
  font-size: 13.5px; line-height: 1.75; color: var(--ink-2);
  max-height: 240px; overflow-y: auto; display: none;
}
.preview-panel h2 {
  font-size: 15px; font-weight: 800; color: var(--ink);
  margin: 16px 0 8px; padding-left: 10px;
  border-left: 3px solid var(--green);
}
.preview-panel p { margin-bottom: 10px; }

/* Editor footer */
.ep-footer {
  padding: 16px 28px 22px;
  border-top: 1px solid var(--bg-2);
  display: flex; align-items: center; gap: 10px;
  border-radius: 0 0 20px 20px;
}
.btn-save {
  flex: 1; padding: 13px;
  background: linear-gradient(135deg, #065E34 0%, #0A8A4E 100%);
  color: var(--white); border: none; border-radius: 10px;
  font-size: 14px; font-weight: 800; cursor: pointer;
  font-family: 'Plus Jakarta Sans', sans-serif;
  transition: all .18s; letter-spacing: .01em;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(6,94,52,.28); }
.btn-cancel-ep {
  padding: 13px 20px; border: 1.5px solid var(--border);
  border-radius: 10px; background: none; cursor: pointer;
  font-size: 13px; font-weight: 600; color: var(--ink-3);
  font-family: 'Plus Jakarta Sans', sans-serif; transition: all .15s;
}
.btn-cancel-ep:hover { border-color: var(--border-2); color: var(--ink); }
.btn-view-ep {
  padding: 13px 18px; background: var(--blue-dim);
  color: var(--blue); border-radius: 10px;
  font-size: 13px; font-weight: 700;
  text-decoration: none; display: none; align-items: center; gap: 6px;
}
</style>
</head>
<body>
<div class="adm">

  <!-- SIDEBAR -->
  <aside class="adm-side">
    <div class="adm-logo"><img src="<?= SITE_URL ?>/images/logo.png" alt="FSW"></div>
    <nav class="adm-nav">
      <a href="<?= SITE_URL ?>/admin/">📊 Dashboard</a>
      <a href="<?= SITE_URL ?>/admin/products.php">📦 Sản phẩm</a>
      <a href="<?= SITE_URL ?>/admin/posts.php" class="on">✍️ Bài đăng</a>
      <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Đơn hàng</a>
      <a href="<?= SITE_URL ?>/admin/users.php">👥 Người dùng</a>
      <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Danh mục</a>
      <div class="sep"></div>
      <a href="<?= SITE_URL ?>/blog.php" target="_blank">📰 Xem Blog</a>
      <a href="<?= SITE_URL ?>/index.php" target="_blank">🌐 Xem website</a>
      <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
    </nav>
  </aside>

  <main class="adm-main">
    <!-- TOPBAR -->
    <div class="adm-topbar">
      <div class="adm-topbar-left">
        <div class="breadcrumb">
          Admin <span>/</span> <strong>Bài đăng</strong>
        </div>
      </div>
      <div class="adm-topbar-right">
        <button class="btn-new" onclick="openEditor()">
          <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="1" x2="8" y2="15"/><line x1="1" y1="8" x2="15" y2="8"/></svg>
          Viết bài mới
        </button>
      </div>
    </div>

    <!-- CONTENT -->
    <div class="adm-content">

      <?php if($msg): ?>
      <div class="adm-alert adm-alert-ok">✓ <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <?php if($error): ?>
      <div class="adm-alert adm-alert-err">⚠ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- STATS ROW -->
      <div class="stats-row">
        <div class="stat-pill">
          <div class="stat-pill-icon all">📝</div>
          <div>
            <div class="stat-pill-num"><?= $total ?></div>
            <div class="stat-pill-lbl">Tổng bài viết</div>
          </div>
        </div>
        <div class="stat-pill">
          <div class="stat-pill-icon pub">🚀</div>
          <div>
            <div class="stat-pill-num"><?= $published ?></div>
            <div class="stat-pill-lbl">Đã xuất bản</div>
          </div>
        </div>
        <div class="stat-pill">
          <div class="stat-pill-icon drf">📋</div>
          <div>
            <div class="stat-pill-num"><?= $drafts ?></div>
            <div class="stat-pill-lbl">Bản nháp</div>
          </div>
        </div>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <form method="GET" style="display:contents">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <div class="search-wrap">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6.5" cy="6.5" r="5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
              <input class="search-input" type="text" name="s" value="<?= htmlspecialchars($s) ?>" placeholder="Tìm tiêu đề bài viết...">
            </div>
            <button class="btn-search" type="submit">Tìm</button>
            <?php if($s): ?><a href="?filter=<?= $filter ?>" style="font-size:12px;color:var(--ink-3);padding:8px 4px">✕</a><?php endif; ?>
          </form>
        </div>
        <div class="filter-tabs">
          <a href="?s=<?= urlencode($s) ?>&filter=all" class="filter-tab <?= $filter==='all'?'active':'' ?>">Tất cả (<?= $total ?>)</a>
          <a href="?s=<?= urlencode($s) ?>&filter=da_dang" class="filter-tab <?= $filter==='da_dang'?'active':'' ?>">Đã đăng</a>
          <a href="?s=<?= urlencode($s) ?>&filter=nhap" class="filter-tab <?= $filter==='nhap'?'active':'' ?>">Nháp</a>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Tiêu đề</th>
              <th>Tag</th>
              <th>Tác giả</th>
              <th>Đọc</th>
              <th>Trạng thái</th>
              <th>Ngày đăng</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($posts)): ?>
            <tr><td colspan="8">
              <div class="empty-wrap">
                <span class="eico">✍️</span>
                <p>Chưa có bài viết nào<?= $s ? " khớp với \"$s\"" : '' ?>.</p>
                <button class="btn-new" onclick="openEditor()" style="margin:0 auto">+ Viết bài đầu tiên</button>
              </div>
            </td></tr>
          <?php endif; ?>
          <?php foreach($posts as $p): ?>
          <?php
            $ss = $p['trang_thai'];
            $scls = match($ss){ 'da_dang'=>'s-pub', 'nhap'=>'s-drf', default=>'s-hid' };
            $slbl = match($ss){ 'da_dang'=>'Đã đăng', 'nhap'=>'Nháp', default=>'Ẩn' };
          ?>
          <tr>
            <td style="color:var(--ink-4);font-size:12px;font-family:'JetBrains Mono',monospace"><?= $p['id'] ?></td>
            <td>
              <div class="post-title-cell">
                <span class="ptitle"><?= htmlspecialchars($p['tieu_de']) ?></span>
                <span class="pslug">/<?= htmlspecialchars($p['slug']) ?></span>
              </div>
            </td>
            <td>
              <span class="tag-chip" style="color:<?= htmlspecialchars($p['tag_color']??'#065E34') ?>">
                <?= htmlspecialchars($p['icon']??'') ?> <?= htmlspecialchars($p['tag']??'') ?>
              </span>
            </td>
            <td style="font-size:12px;color:var(--ink-3)"><?= htmlspecialchars($p['tac_gia']) ?></td>
            <td style="font-size:12px;color:var(--ink-4);font-family:'JetBrains Mono',monospace"><?= $p['read_time']??5 ?>m</td>
            <td>
              <span class="status-badge <?= $scls ?>">
                <span class="status-dot"></span><?= $slbl ?>
              </span>
            </td>
            <td style="font-size:12px;color:var(--ink-4);white-space:nowrap">
              <?= date('d/m/Y', strtotime($p['ngay_dang'])) ?>
            </td>
            <td>
              <div class="act-row">
                <button class="act-btn ab-edit" title="Sửa"
                  onclick='openEditor(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                <form method="POST" style="display:contents">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="act-btn ab-toggle" type="submit" title="<?= $ss==='da_dang'?'Ẩn bài':'Đăng bài' ?>">
                    <?= $ss==='da_dang' ? '⏸' : '▶️' ?>
                  </button>
                </form>
                <a class="act-btn ab-view" href="<?= SITE_URL ?>/blog-detail.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" title="Xem bài">👁</a>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xoá bài viết này?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="act-btn ab-del" type="submit" title="Xoá">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /adm-content -->
  </main>
</div>

<!-- ════ EDITOR MODAL ════ -->
<div class="editor-overlay" id="editorOverlay" onclick="if(event.target===this)closeEditor()">
  <div class="editor-panel">

    <div class="ep-header">
      <div class="ep-header-left">
        <span class="ep-title-badge" id="epBadge">✍️ Mới</span>
        <span class="ep-title-text" id="epTitle">Viết bài mới</span>
      </div>
      <button class="ep-close" onclick="closeEditor()">✕</button>
    </div>

    <form method="POST" id="postForm">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="fId" value="0">
      <input type="hidden" name="tag_color" id="fColor" value="#065E34">
      <input type="hidden" name="icon" id="fIconHidden" value="📝">

      <div class="ep-body">

        <!-- Tiêu đề -->
        <div class="ef-group">
          <label class="ef-label">Tiêu đề bài viết *</label>
          <input class="ef-input ef-input-title" type="text" name="tieu_de" id="fTitle"
                 placeholder="Nhập tiêu đề hấp dẫn..." required>
        </div>

        <!-- Tag + Icon + Read time -->
        <div class="ep-meta-3">
          <div class="ef-group">
            <label class="ef-label">Tag / Danh mục</label>
            <div class="tag-picker-wrap">
              <div class="tag-input-row" onclick="toggleTagDropdown()">
                <span class="tag-preview-icon" id="tagPreviewIcon">📝</span>
                <input class="tag-input-val" type="text" name="tag" id="fTag" placeholder="Chọn tag..." readonly>
                <span class="tag-arrow">▾</span>
              </div>
              <div class="tag-dropdown" id="tagDropdown">
                <?php foreach($tags as [$tname,$ticon,$tcolor]): ?>
                <div class="tag-opt" onclick="selectTag('<?= $tname ?>','<?= $ticon ?>','<?= $tcolor ?>')" style="color:<?= $tcolor ?>">
                  <span><?= $ticon ?></span> <?= $tname ?>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="ef-group">
            <label class="ef-label">Icon hiển thị</label>
            <input class="ef-input" type="text" id="fIconDisplay" placeholder="🎨"
                   style="font-size:18px;text-align:center"
                   oninput="document.getElementById('fIconHidden').value=this.value">
          </div>
          <div class="ef-group">
            <label class="ef-label">Thời gian đọc (phút)</label>
            <input class="ef-input" type="number" name="read_time" id="fReadTime" value="5" min="1" max="60">
          </div>
        </div>

        <!-- Content editor -->
        <div class="ef-group content-wrap">
          <label class="ef-label">Nội dung *</label>
          <div class="content-toolbar">
            <button type="button" class="tb-btn" onclick="ins('## Tiêu đề mục\n\n')">H2</button>
            <button type="button" class="tb-btn" onclick="ins('### Tiêu đề phụ\n\n')">H3</button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-btn" onclick="ins('**văn bản đậm**')"><b>B</b></button>
            <button type="button" class="tb-btn" onclick="ins('*in nghiêng*')"><i>I</i></button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-btn" onclick="ins('\n• ')">• List</button>
            <button type="button" class="tb-btn" onclick="ins('\n\n---\n\n')">── Phân cách</button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-btn" onclick="togglePreview()" id="previewBtn">👁 Xem trước</button>
            <span class="tb-count" id="charCount">0 ký tự</span>
          </div>
          <textarea class="ef-input ef-textarea" name="noi_dung" id="fContent"
            placeholder="Viết nội dung ở đây...&#10;&#10;Gợi ý:&#10;## Tiêu đề mục lớn&#10;### Tiêu đề phụ&#10;• Gạch đầu dòng&#10;---  (phân cách đoạn)"
            oninput="updateCount(this)" required></textarea>
          <div id="previewPanel" class="preview-panel"></div>
        </div>

        <!-- Status + hint -->
        <div class="ep-meta">
          <div class="ef-group">
            <label class="ef-label">Trạng thái xuất bản</label>
            <select class="ef-input ef-select" name="trang_thai" id="fStatus">
              <option value="nhap">📝 Nháp — chưa công khai</option>
              <option value="da_dang">🚀 Đăng ngay — hiện trên blog</option>
              <option value="an">🔒 Ẩn — tạm ngừng</option>
            </select>
          </div>
          <div class="status-info">
            <div><b>📝 Nháp</b>: Chỉ admin mới thấy bài này</div>
            <div style="margin-top:6px"><b>🚀 Đăng ngay</b>: Hiện công khai trên blog</div>
            <div style="margin-top:6px"><b>🔒 Ẩn</b>: Tạm ẩn khỏi danh sách</div>
          </div>
        </div>

      </div><!-- /ep-body -->

      <div class="ep-footer">
        <button type="submit" class="btn-save">
          💾 Lưu bài viết
        </button>
        <button type="button" class="btn-cancel-ep" onclick="closeEditor()">Huỷ</button>
        <a id="viewLinkBtn" class="btn-view-ep" href="#" target="_blank">👁 Xem bài</a>
      </div>
    </form>
  </div>
</div>

<script src="<?= SITE_URL ?>/shared.js"></script>
<script>
restoreTheme?.();

/* ─ OPEN / CLOSE EDITOR ─ */
function openEditor(p) {
  const ov = document.getElementById('editorOverlay');
  ov.classList.add('open');
  document.body.style.overflow = 'hidden';

  if (p) {
    document.getElementById('epBadge').textContent = '✏️ Sửa';
    document.getElementById('epTitle').textContent = 'Sửa bài viết';
    document.getElementById('fId').value        = p.id;
    document.getElementById('fTitle').value     = p.tieu_de || '';
    document.getElementById('fContent').value   = p.noi_dung || '';
    document.getElementById('fTag').value       = p.tag || '';
    document.getElementById('fIconDisplay').value = p.icon || '📝';
    document.getElementById('fIconHidden').value  = p.icon || '📝';
    document.getElementById('tagPreviewIcon').textContent = p.icon || '📝';
    document.getElementById('fReadTime').value  = p.read_time || 5;
    document.getElementById('fStatus').value    = p.trang_thai || 'nhap';
    document.getElementById('fColor').value     = p.tag_color || '#065E34';
    updateCount(document.getElementById('fContent'));
    highlightTag(p.tag);
    const vb = document.getElementById('viewLinkBtn');
    if (p.trang_thai === 'da_dang') {
      vb.href = '<?= SITE_URL ?>/blog-detail.php?slug=' + encodeURIComponent(p.slug);
      vb.style.display = 'inline-flex';
    } else { vb.style.display = 'none'; }
  } else {
    document.getElementById('epBadge').textContent = '✍️ Mới';
    document.getElementById('epTitle').textContent = 'Viết bài mới';
    document.getElementById('fId').value = '0';
    document.getElementById('postForm').reset();
    document.getElementById('fId').value = '0';
    document.getElementById('fReadTime').value = '5';
    document.getElementById('fIconHidden').value = '📝';
    document.getElementById('tagPreviewIcon').textContent = '📝';
    document.getElementById('viewLinkBtn').style.display = 'none';
    updateCount(document.getElementById('fContent'));
    document.querySelectorAll('.tag-opt').forEach(e=>e.classList.remove('selected'));
  }
  setTimeout(() => document.getElementById('fTitle').focus(), 120);
}

function closeEditor() {
  document.getElementById('editorOverlay').classList.remove('open');
  document.body.style.overflow = '';
  document.getElementById('tagDropdown').classList.remove('open');
  document.getElementById('previewPanel').style.display = 'none';
  document.getElementById('previewBtn').textContent = '👁 Xem trước';
}

/* ─ TAG ─ */
function toggleTagDropdown() {
  document.getElementById('tagDropdown').classList.toggle('open');
}
function selectTag(name, icon, color) {
  document.getElementById('fTag').value         = name;
  document.getElementById('fIconDisplay').value = icon;
  document.getElementById('fIconHidden').value  = icon;
  document.getElementById('fColor').value       = color;
  document.getElementById('tagPreviewIcon').textContent = icon;
  document.getElementById('tagDropdown').classList.remove('open');
  highlightTag(name);
}
function highlightTag(name) {
  document.querySelectorAll('.tag-opt').forEach(el =>
    el.classList.toggle('selected', el.textContent.trim().includes(name))
  );
}
document.addEventListener('click', e => {
  const dd = document.getElementById('tagDropdown');
  if (dd && !dd.closest('.tag-picker-wrap').contains(e.target))
    dd.classList.remove('open');
});

/* ─ EDITOR HELPERS ─ */
function ins(text) {
  const ta = document.getElementById('fContent');
  const s = ta.selectionStart, e = ta.selectionEnd;
  ta.value = ta.value.slice(0,s) + text + ta.value.slice(e);
  ta.selectionStart = ta.selectionEnd = s + text.length;
  ta.focus(); updateCount(ta);
}
function updateCount(ta) {
  const len = ta.value.length;
  const mins = Math.max(1, Math.ceil(len / 800));
  document.getElementById('charCount').textContent = len.toLocaleString('vi-VN') + ' ký · ~' + mins + ' phút';
  document.getElementById('fReadTime').value = mins;
}
function togglePreview() {
  const panel = document.getElementById('previewPanel');
  const btn   = document.getElementById('previewBtn');
  const show  = panel.style.display !== 'block';
  panel.style.display = show ? 'block' : 'none';
  btn.textContent = show ? '✕ Đóng xem trước' : '👁 Xem trước';
  if (show) {
    const raw = document.getElementById('fContent').value;
    panel.innerHTML = renderMd(raw);
  }
}
function renderMd(raw) {
  return raw.split('\n\n').map(p => {
    p = p.trim(); if (!p) return '';
    if (p.startsWith('## ')) return `<h2>${esc(p.slice(3))}</h2>`;
    if (p.startsWith('### ')) return `<h3>${esc(p.slice(4))}</h3>`;
    if (p.startsWith('---')) return `<hr style="border:none;border-top:2px solid #DDE5DE;margin:12px 0">`;
    return `<p>${esc(p).replace(/\n/g,'<br>').replace(/\*\*(.+?)\*\*/g,'<b>$1</b>').replace(/\*(.+?)\*/g,'<i>$1</i>')}</p>`;
  }).join('');
}
function esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

/* ─ ESC KEY ─ */
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditor(); });

/* ─ AUTO OPEN IF ?edit= ─ */
<?php if($editPost): ?>
openEditor(<?= json_encode($editPost, JSON_UNESCAPED_UNICODE) ?>);
<?php endif; ?>
</script>
</body>
</html>
