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

        // Tạo slug unique
        $slug_base = 'bai-viet-' . ($id ?: time());
        $slug = $slug_base;
        if (!$id) {
            // slug mới: đếm số bài hiện có
            $count = (int)db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            $slug = 'bai-viet-' . ($count + 1);
            // đảm bảo unique
            $i = 1;
            while (db()->prepare("SELECT id FROM posts WHERE slug=:s")->execute([':s'=>$slug]) &&
                   db()->prepare("SELECT id FROM posts WHERE slug=:s")->execute([':s'=>$slug]) &&
                   db()->query("SELECT COUNT(*) FROM posts WHERE slug='$slug'")->fetchColumn() > 0) {
                $slug = 'bai-viet-' . ($count + 1) . '-' . $i++;
            }
        }

        if (!$tieu_de || !$noi_dung) {
            $error = 'Vui lòng nhập tiêu đề và nội dung bài viết.';
        } else {
            if ($id) {
                db()->prepare("UPDATE posts SET tieu_de=:td, noi_dung=:nd, excerpt=:ex, tag=:tag, icon=:ic, tag_color=:tc, read_time=:rt, trang_thai=:tt WHERE id=:id")
                   ->execute([':td'=>$tieu_de,':nd'=>$noi_dung,':ex'=>$excerpt,':tag'=>$tag,':ic'=>$icon,':tc'=>$color,':rt'=>$rt,':tt'=>$tt,':id'=>$id]);
                $msg = '✓ Đã cập nhật bài viết.';
            } else {
                db()->prepare("INSERT INTO posts (tac_gia_id,tieu_de,slug,noi_dung,excerpt,tag,icon,tag_color,read_time,trang_thai) VALUES (?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$_SESSION['user_id'],$tieu_de,$slug,$noi_dung,$excerpt,$tag,$icon,$color,$rt,$tt]);
                $msg = '✓ Đã thêm bài viết mới.';
            }
        }
    }

    /* ── XOÁ ── */
    if ($action === 'delete') {
        db()->prepare("DELETE FROM posts WHERE id=:id")->execute([':id'=>(int)$_POST['id']]);
        $msg = '✓ Đã xoá bài viết.';
    }

    /* ── ĐỔI TRẠNG THÁI NHANH ── */
    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $cur = db()->query("SELECT trang_thai FROM posts WHERE id=$id")->fetchColumn();
        $new = $cur === 'da_dang' ? 'nhap' : 'da_dang';
        db()->prepare("UPDATE posts SET trang_thai=:t WHERE id=:id")->execute([':t'=>$new,':id'=>$id]);
        $msg = '✓ Đã ' . ($new === 'da_dang' ? 'đăng' : 'ẩn') . ' bài viết.';
    }
}

/* ── Load danh sách ── */
$s = trim($_GET['s'] ?? '');
$where = $s ? "WHERE tieu_de LIKE :s" : "";
$stmt = db()->prepare("SELECT p.*, u.ho_ten tac_gia FROM posts p JOIN users u ON u.id=p.tac_gia_id $where ORDER BY p.id DESC");
if ($s) $stmt->bindValue(':s', "%$s%");
$stmt->execute();
$posts = $stmt->fetchAll();

/* ── Load bài để edit ── */
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
<title>Quản lý bài đăng — Admin</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/style.css">
<style>
body{margin:0}
.adm{display:flex;min-height:100vh}
.adm-side{width:230px;flex-shrink:0;background:#010D05;border-right:1px solid #1a2e1c;position:sticky;top:0;height:100vh;overflow-y:auto}
.adm-logo{padding:20px;border-bottom:1px solid #1a2e1c}
.adm-logo img{height:36px}
.adm-nav{padding:12px 8px}
.adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:#4A6650;font-size:13px;font-weight:500;margin-bottom:2px;transition:all .15s}
.adm-nav a:hover,.adm-nav a.on{background:#0A1E0C;color:#C8FF00;font-weight:600}
.adm-main{flex:1;background:#F5F7F5;padding:28px;overflow-x:hidden}

/* Table */
.tbl-wrap{background:#fff;border:1px solid #DDE3DD;border-radius:12px;overflow:hidden;margin-top:16px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{padding:10px 16px;background:#F5F7F5;color:#7A8F7C;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.06em;font-weight:700}
td{padding:12px 16px;border-bottom:1px solid #EEF1EE;vertical-align:middle}
tr:hover td{background:#F9FBF9}
.btn-sm{padding:5px 11px;font-size:12px;border-radius:6px;cursor:pointer;border:none;font-weight:600;font-family:'Inter',sans-serif}
.btn-e{background:#E8FFF3;color:#065F46}
.btn-t{background:#FEF3C7;color:#854D0E}
.btn-d{background:#FEE2E2;color:#991B1B}
.msg-ok{background:#D1FAE5;color:#065F46;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px;border:1px solid #6EE7B7}
.msg-err{background:#FEE2E2;color:#991B1B;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.b-dang{background:#D1FAE5;color:#065F46}
.b-nhap{background:#F3F4F6;color:#6B7280}
.b-an{background:#FEE2E2;color:#991B1B}
.sbar{display:flex;gap:8px;margin-bottom:0;flex-wrap:wrap;align-items:center}
.sbar input{padding:8px 12px;border:1.5px solid #DDE3DD;border-radius:8px;font-size:13px;background:#fff;color:#0D1A0F;font-family:'Inter',sans-serif;min-width:220px}
.sbar button{padding:8px 16px;background:#065E34;color:#C8FF00;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer}

/* Modal full-screen editor */
.editor-modal{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:500;display:none;align-items:flex-start;justify-content:center;backdrop-filter:blur(4px);padding:20px;overflow-y:auto}
.editor-modal.open{display:flex}
.editor-box{background:#fff;border-radius:18px;width:min(860px,100%);margin:auto;box-shadow:0 24px 80px rgba(0,0,0,.25);animation:fadeUp .25s cubic-bezier(.22,1,.36,1)}
@keyframes fadeUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.editor-head{padding:22px 28px 18px;border-bottom:1px solid #EEF1EE;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;border-radius:18px 18px 0 0;z-index:1}
.editor-head h3{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:800;margin:0;letter-spacing:-.01em}
.editor-close{width:32px;height:32px;border:none;background:#F0F2F0;border-radius:8px;cursor:pointer;font-size:15px;color:#7A8F7C;display:flex;align-items:center;justify-content:center;transition:background .15s}
.editor-close:hover{background:#E2E8E2;color:#0D1A0F}
.editor-body{padding:24px 28px}
.editor-foot{padding:16px 28px 24px;display:flex;gap:10px;border-top:1px solid #EEF1EE;align-items:center}
.fl{font-size:11px;font-weight:700;color:#3D5040;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px}
.fi{width:100%;padding:10px 13px;border:1.5px solid #DDE3DD;border-radius:9px;font-size:13px;font-family:'Inter',sans-serif;color:#0D1A0F;background:#fff;outline:none;transition:border-color .2s;box-sizing:border-box}
.fi:focus{border-color:#065E34;box-shadow:0 0 0 3px rgba(6,94,52,.08)}
.mg{margin-bottom:16px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.row3{display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px}

/* Content editor area */
.content-editor{width:100%;min-height:320px;padding:14px;border:1.5px solid #DDE3DD;border-radius:9px;font-size:14px;font-family:'Inter',sans-serif;color:#0D1A0F;background:#fff;outline:none;resize:vertical;line-height:1.75;box-sizing:border-box;transition:border-color .2s}
.content-editor:focus{border-color:#065E34;box-shadow:0 0 0 3px rgba(6,94,52,.08)}
.editor-toolbar{display:flex;gap:4px;margin-bottom:8px;flex-wrap:wrap}
.tb-btn{padding:5px 10px;border:1px solid #DDE3DD;border-radius:6px;background:#fff;cursor:pointer;font-size:12px;font-weight:600;color:#3D5040;transition:all .12s;font-family:'Inter',sans-serif}
.tb-btn:hover{background:#E8FFF3;border-color:#6EE7B7;color:#065F46}

/* Tag picker */
.tag-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px;margin-top:6px}
.tag-opt{padding:8px 10px;border:1.5px solid #DDE3DD;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;text-align:center;transition:all .15s;user-select:none}
.tag-opt:hover{border-color:#6EE7B7;background:#E8FFF3}
.tag-opt.selected{border-color:#065E34;background:#E8FFF3;color:#065F46}

/* Preview */
.preview-panel{background:#F8FAF8;border:1px solid #DDE3DD;border-radius:10px;padding:16px;min-height:120px;font-size:13px;line-height:1.7;color:#3D5040;max-height:200px;overflow-y:auto}
.preview-panel h2{font-size:16px;font-weight:700;color:#0D1A0F;margin:14px 0 8px;padding-left:10px;border-left:3px solid #065E34}
.preview-panel h3{font-size:14px;font-weight:700;color:#0D1A0F;margin:10px 0 6px}
.preview-panel p{margin-bottom:10px}
</style>
</head>
<body>
<div class="adm">

  <!-- ── SIDEBAR ── -->
  <aside class="adm-side">
    <div class="adm-logo"><img src="<?= SITE_URL ?>/images/logo.png" alt="FSW"></div>
    <nav class="adm-nav">
      <a href="<?= SITE_URL ?>/admin/">📊 Dashboard</a>
      <a href="<?= SITE_URL ?>/admin/products.php">📦 Sản phẩm</a>
      <a href="<?= SITE_URL ?>/admin/posts.php" class="on">✍️ Bài đăng</a>
      <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Đơn hàng</a>
      <a href="<?= SITE_URL ?>/admin/users.php">👥 Người dùng</a>
      <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Danh mục</a>
      <div style="border-top:1px solid #1a2e1c;margin:12px 4px"></div>
      <a href="<?= SITE_URL ?>/blog.php" target="_blank">📰 Xem Blog</a>
      <a href="<?= SITE_URL ?>/index.php" target="_blank">🌐 Xem website</a>
      <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
    </nav>
  </aside>

  <!-- ── MAIN ── -->
  <main class="adm-main">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div>
        <h1 style="font-size:22px;font-weight:800;margin:0;font-family:'Space Grotesk',sans-serif">
          ✍️ Bài đăng
          <span style="font-size:14px;color:#7A8F7C;font-weight:500">(<?= count($posts) ?>)</span>
        </h1>
      </div>
      <button class="btn-primary" onclick="openEditor()" style="padding:10px 20px;font-size:13px">
        + Viết bài mới
      </button>
    </div>

    <?php if($msg): ?><div class="msg-ok">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($error): ?><div class="msg-err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Search -->
    <form class="sbar" method="GET" style="margin-bottom:16px">
      <input type="text" name="s" placeholder="🔍 Tìm tiêu đề bài viết..." value="<?= htmlspecialchars($s) ?>">
      <button type="submit">Tìm</button>
      <?php if($s): ?><a href="?" style="font-size:13px;color:#7A8F7C;padding:8px;text-decoration:none">✕ Xoá</a><?php endif; ?>
    </form>

    <!-- Table -->
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Tiêu đề</th><th>Tag</th>
            <th>Tác giả</th><th>Đọc</th>
            <th>Trạng thái</th><th>Ngày</th><th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
        <?php if(empty($posts)): ?>
          <tr><td colspan="8" style="text-align:center;padding:40px;color:#7A8F7C">
            Chưa có bài viết nào. <a href="#" onclick="openEditor();return false" style="color:#065E34;font-weight:600">Viết bài đầu tiên →</a>
          </td></tr>
        <?php endif; ?>
        <?php foreach($posts as $p): ?>
        <tr>
          <td style="color:#7A8F7C;font-size:12px">#<?= $p['id'] ?></td>
          <td>
            <div style="font-weight:600;max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= htmlspecialchars($p['tieu_de']) ?>
            </div>
            <div style="font-size:11px;color:#7A8F7C;margin-top:2px">
              <?= htmlspecialchars($p['slug']) ?>
            </div>
          </td>
          <td>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:<?= htmlspecialchars($p['tag_color'] ?? '#065E34') ?>">
              <?= htmlspecialchars($p['icon'] ?? '') ?> <?= htmlspecialchars($p['tag'] ?? '') ?>
            </span>
          </td>
          <td style="color:#7A8F7C;font-size:12px"><?= htmlspecialchars($p['tac_gia']) ?></td>
          <td style="color:#7A8F7C;font-size:12px"><?= $p['read_time'] ?? 5 ?> phút</td>
          <td>
            <?php $ss = $p['trang_thai'];
              $cls = match($ss){'da_dang'=>'b-dang','nhap'=>'b-nhap',default=>'b-an'};
              $lbl = match($ss){'da_dang'=>'Đã đăng','nhap'=>'Nháp',default=>'Ẩn'};
            ?>
            <span class="badge <?= $cls ?>"><?= $lbl ?></span>
          </td>
          <td style="color:#7A8F7C;font-size:12px;white-space:nowrap">
            <?= date('d/m/Y', strtotime($p['ngay_dang'])) ?>
          </td>
          <td>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <!-- Sửa -->
              <button class="btn-sm btn-e"
                onclick='openEditor(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
              <!-- Toggle đăng/ẩn -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn-sm btn-t" type="submit" title="<?= $ss==='da_dang'?'Ẩn':'Đăng' ?>">
                  <?= $ss==='da_dang' ? '👁' : '🚀' ?>
                </button>
              </form>
              <!-- Xem -->
              <a class="btn-sm" href="<?= SITE_URL ?>/blog-detail.php?slug=<?= urlencode($p['slug']) ?>"
                 target="_blank" style="background:#E6F1FB;color:#185FA5;text-decoration:none">👁</a>
              <!-- Xoá -->
              <form method="POST" style="display:inline" onsubmit="return confirm('Xoá bài viết này?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn-sm btn-d" type="submit">🗑</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<!-- ══ EDITOR MODAL ══ -->
<div class="editor-modal" id="editorModal" onclick="if(event.target===this)closeEditor()">
  <div class="editor-box">
    <div class="editor-head">
      <h3 id="editorTitle">✍️ Viết bài mới</h3>
      <button class="editor-close" onclick="closeEditor()">✕</button>
    </div>

    <form method="POST" id="postForm">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="fId" value="0">
      <input type="hidden" name="tag_color" id="fColor" value="#065E34">

      <div class="editor-body">

        <!-- Tiêu đề -->
        <div class="mg">
          <label class="fl">Tiêu đề bài viết *</label>
          <input class="fi" type="text" name="tieu_de" id="fTitle"
                 placeholder="VD: Hướng dẫn cài đặt Photoshop 2025..." required
                 style="font-size:15px;font-weight:600;padding:12px 14px">
        </div>

        <!-- Tag + icon + read time + status -->
        <div class="row3 mg">
          <div>
            <label class="fl">Tag / Danh mục</label>
            <input class="fi" type="text" name="tag" id="fTag" placeholder="Thiết kế"
                   readonly style="cursor:pointer" onclick="document.getElementById('tagPicker').classList.toggle('open')">
          </div>
          <div>
            <label class="fl">Icon</label>
            <input class="fi" type="text" name="icon" id="fIcon" placeholder="🎨"
                   style="font-size:18px;text-align:center">
          </div>
          <div>
            <label class="fl">Thời gian đọc (phút)</label>
            <input class="fi" type="number" name="read_time" id="fReadTime" value="5" min="1" max="60">
          </div>
        </div>

        <!-- Tag picker -->
        <div id="tagPicker" style="display:none;margin-bottom:16px">
          <div class="tag-grid">
            <?php foreach($tags as [$tname,$ticon,$tcolor]): ?>
            <div class="tag-opt" onclick="selectTag('<?= $tname ?>','<?= $ticon ?>','<?= $tcolor ?>')"
                 style="color:<?= $tcolor ?>">
              <?= $ticon ?> <?= $tname ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Toolbar -->
        <label class="fl">Nội dung bài viết *</label>
        <div class="editor-toolbar">
          <button type="button" class="tb-btn" onclick="insertText('\n\n1. Tiêu đề mục\n')">📌 Tiêu đề</button>
          <button type="button" class="tb-btn" onclick="insertText('\n\n')">¶ Đoạn mới</button>
          <button type="button" class="tb-btn" onclick="insertText('**')">B</button>
          <button type="button" class="tb-btn" onclick="insertText('\n• ')">• List</button>
          <button type="button" class="tb-btn" onclick="insertText('\n\n---\n\n')">── Phân cách</button>
          <button type="button" class="tb-btn" onclick="togglePreview()" id="previewBtn">👁 Xem trước</button>
          <span style="margin-left:auto;font-size:12px;color:#7A8F7C" id="charCount">0 ký tự</span>
        </div>

        <!-- Editor -->
        <textarea class="content-editor" name="noi_dung" id="fContent"
                  placeholder="Viết nội dung bài đăng ở đây...&#10;&#10;Gợi ý:&#10;- Bắt đầu bằng đoạn giới thiệu hấp dẫn&#10;- Dùng '1. Tiêu đề mục' để tạo heading&#10;- Mỗi đoạn cách nhau 1 dòng trống"
                  oninput="updateCount(this)"
                  required></textarea>

        <!-- Preview panel (ẩn mặc định) -->
        <div id="previewPanel" style="display:none;margin-top:12px">
          <label class="fl">Xem trước nội dung</label>
          <div class="preview-panel" id="previewContent"></div>
        </div>

        <!-- Trạng thái -->
        <div class="row2 mg" style="margin-top:16px">
          <div>
            <label class="fl">Trạng thái</label>
            <select class="fi" name="trang_thai" id="fStatus">
              <option value="nhap">📝 Nháp (chưa hiện)</option>
              <option value="da_dang">🚀 Đăng ngay</option>
              <option value="an">🔒 Ẩn</option>
            </select>
          </div>
          <div style="display:flex;align-items:flex-end">
            <div style="background:#E8FFF3;border:1px solid #6EE7B7;border-radius:8px;padding:10px 14px;font-size:12px;color:#065F46;width:100%">
              💡 <b>Nháp</b>: Chỉ admin thấy<br>
              🚀 <b>Đăng ngay</b>: Hiện trên blog
            </div>
          </div>
        </div>

      </div><!-- /editor-body -->

      <div class="editor-foot">
        <button type="submit" class="btn-submit" style="flex:1;padding:13px;font-size:14px">
          💾 Lưu bài viết
        </button>
        <button type="button" onclick="closeEditor()"
                style="padding:13px 20px;border:1.5px solid #DDE3DD;border-radius:10px;background:none;cursor:pointer;font-size:13px;font-weight:600;color:#7A8F7C">
          Huỷ
        </button>
        <a id="viewLink" href="#" target="_blank"
           style="display:none;padding:13px 18px;background:#E6F1FB;color:#185FA5;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none">
          👁 Xem bài
        </a>
      </div>
    </form>
  </div>
</div>

<script src="<?= SITE_URL ?>/shared.js"></script>
<script>
restoreTheme();

/* ── MỞ EDITOR ── */
function openEditor(p) {
  const modal = document.getElementById('editorModal');
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';

  if (p) {
    /* Sửa bài cũ */
    document.getElementById('editorTitle').textContent = '✏️ Sửa bài viết';
    document.getElementById('fId').value        = p.id;
    document.getElementById('fTitle').value     = p.tieu_de || '';
    document.getElementById('fContent').value   = p.noi_dung || '';
    document.getElementById('fTag').value       = p.tag || '';
    document.getElementById('fIcon').value      = p.icon || '📝';
    document.getElementById('fReadTime').value  = p.read_time || 5;
    document.getElementById('fStatus').value    = p.trang_thai || 'nhap';
    document.getElementById('fColor').value     = p.tag_color || '#065E34';
    updateCount(document.getElementById('fContent'));

    const vl = document.getElementById('viewLink');
    if (p.trang_thai === 'da_dang') {
      vl.href = '<?= SITE_URL ?>/blog-detail.php?slug=' + encodeURIComponent(p.slug);
      vl.style.display = 'inline-flex';
    } else { vl.style.display = 'none'; }

    /* Highlight tag đã chọn */
    highlightTag(p.tag);
  } else {
    /* Bài mới */
    document.getElementById('editorTitle').textContent = '✍️ Viết bài mới';
    document.getElementById('fId').value = '0';
    document.getElementById('postForm').reset();
    document.getElementById('fId').value = '0';
    document.getElementById('fReadTime').value = '5';
    document.getElementById('viewLink').style.display = 'none';
    updateCount(document.getElementById('fContent'));
  }

  /* Focus vào title */
  setTimeout(() => document.getElementById('fTitle').focus(), 100);
}

function closeEditor() {
  document.getElementById('editorModal').classList.remove('open');
  document.body.style.overflow = '';
  document.getElementById('tagPicker').style.display = 'none';
  document.getElementById('previewPanel').style.display = 'none';
  document.getElementById('previewBtn').textContent = '👁 Xem trước';
}

/* ── CHỌN TAG ── */
function selectTag(name, icon, color) {
  document.getElementById('fTag').value   = name;
  document.getElementById('fIcon').value  = icon;
  document.getElementById('fColor').value = color;
  document.getElementById('tagPicker').style.display = 'none';
  highlightTag(name);
}

function highlightTag(name) {
  document.querySelectorAll('.tag-opt').forEach(el => {
    el.classList.toggle('selected', el.textContent.trim().includes(name));
  });
}

/* ── INSERT TEXT VÀO EDITOR ── */
function insertText(text) {
  const ta = document.getElementById('fContent');
  const start = ta.selectionStart;
  const end   = ta.selectionEnd;
  ta.value = ta.value.slice(0, start) + text + ta.value.slice(end);
  ta.selectionStart = ta.selectionEnd = start + text.length;
  ta.focus();
  updateCount(ta);
}

/* ── ĐẾM KÝ TỰ ── */
function updateCount(ta) {
  const len = ta.value.length;
  document.getElementById('charCount').textContent =
    len.toLocaleString('vi-VN') + ' ký tự · ~' + Math.ceil(len / 800) + ' phút đọc';
  // Tự cập nhật read_time
  document.getElementById('fReadTime').value = Math.max(1, Math.ceil(len / 800));
}

/* ── XEM TRƯỚC ── */
function togglePreview() {
  const panel = document.getElementById('previewPanel');
  const btn   = document.getElementById('previewBtn');
  const show  = panel.style.display === 'none';
  panel.style.display = show ? 'block' : 'none';
  btn.textContent = show ? '✕ Đóng xem trước' : '👁 Xem trước';

  if (show) {
    const raw = document.getElementById('fContent').value;
    document.getElementById('previewContent').innerHTML = renderPreview(raw);
  }
}

function renderPreview(raw) {
  return raw.split('\n\n').map(para => {
    para = para.trim();
    if (!para) return '';
    if (/^\d+\.\s/.test(para)) return `<h2>${esc(para)}</h2>`;
    return `<p>${esc(para).replace(/\n/g,'<br>')}</p>`;
  }).join('');
}

function esc(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

/* ── ĐÓNG TAG PICKER KHI CLICK NGOÀI ── */
document.addEventListener('click', e => {
  const tp = document.getElementById('tagPicker');
  const tf = document.getElementById('fTag');
  if (tp && !tp.contains(e.target) && e.target !== tf) {
    tp.style.display = 'none';
  }
});

/* ── AUTO-OPEN NẾU CÓ ?edit= ── */
<?php if($editPost): ?>
openEditor(<?= json_encode($editPost, JSON_UNESCAPED_UNICODE) ?>);
<?php endif; ?>
</script>
</body>
</html>
