<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
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
        $hinh     = $_POST['hinh_anh_cu'] ?? '';

        if (!$tieu_de || !$noi_dung) {
            $error = 'Vui lòng nhập tiêu đề và nội dung.';
        } else {
            if (!empty($_FILES['hinh_anh']['name'])) {
                $ext = strtolower(pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp'])) { $error = 'Định dạng ảnh không hợp lệ.'; }
                elseif ($_FILES['hinh_anh']['size'] > 5*1024*1024) { $error = 'Ảnh tối đa 5MB.'; }
                elseif ($_FILES['hinh_anh']['error'] !== UPLOAD_ERR_OK) { $error = 'Tải ảnh lên thất bại (mã lỗi '.$_FILES['hinh_anh']['error'].').'; }
                else {
                    $destDir = __DIR__ . '/../images/blog';
                    if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }
                    $baseSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$tieu_de),'-')) ?: 'bai-viet';
                    $fn  = $baseSlug.'-'.time().'.'.$ext;
                    $dst = $destDir.'/'.$fn;
                    if (is_writable($destDir) && move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $dst)) $hinh = 'blog/'.$fn;
                    else $error = 'Không upload được ảnh (kiểm tra quyền ghi thư mục images/blog).';
                }
            }

            // File Word (.docx) đính kèm — lưu NGUYÊN FILE gốc (không quy đổi ra HTML nữa),
            // rồi chèn vào nội dung một "mốc" trỏ tới file đó. Khi hiển thị công khai, trang
            // blog-detail.php sẽ tự render trực tiếp file .docx này bằng docx-preview.js
            // (xem assets/js/blog-detail.js) -> luôn đúng y hệt file gốc, không lệch do quy đổi.
            if (!$error && !empty($_FILES['word_file']['name'])) {
                $wext = strtolower(pathinfo($_FILES['word_file']['name'], PATHINFO_EXTENSION));
                if ($wext !== 'docx') { $error = 'File đính kèm phải là .docx.'; }
                elseif ($_FILES['word_file']['size'] > 20*1024*1024) { $error = 'File Word tối đa 20MB.'; }
                elseif ($_FILES['word_file']['error'] !== UPLOAD_ERR_OK) { $error = 'Tải file Word lên thất bại (mã lỗi '.$_FILES['word_file']['error'].').'; }
                else {
                    if (!is_dir(UPLOAD_BLOG_DOCS_DIR)) { @mkdir(UPLOAD_BLOG_DOCS_DIR, 0775, true); }
                    $baseSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$tieu_de),'-')) ?: 'bai-viet';
                    $wfn  = $baseSlug.'-'.time().'.docx';
                    $wdst = UPLOAD_BLOG_DOCS_DIR.$wfn;
                    if (is_writable(UPLOAD_BLOG_DOCS_DIR) && move_uploaded_file($_FILES['word_file']['tmp_name'], $wdst)) {
                        $wordMarker = '<!--fsw-word-file:blog-docs/'.$wfn.'-->';
                        $noi_dung = (strpos($noi_dung, '[[FSW_WORD_FILE]]') !== false)
                            ? str_replace('[[FSW_WORD_FILE]]', $wordMarker, $noi_dung)
                            : trim($noi_dung)."\n\n".$wordMarker."\n\n";
                    } else {
                        $error = 'Không upload được file Word (kiểm tra quyền ghi thư mục uploads/blog-docs).';
                    }
                }
            }
            // Dọn "mốc" còn sót lại nếu người dùng bấm nút chọn file nhưng cuối cùng không có file thật đi kèm
            $noi_dung = str_replace('[[FSW_WORD_FILE]]', '', $noi_dung);
        }

        // Tóm tắt (excerpt) tính SAU khi nội dung đã chốt (gồm cả mốc file Word vừa chèn)
        $plain = preg_replace('/^[#>*•\-\s]+/m', '', strip_tags(preg_replace('/<!--fsw-word-file:[^>]*-->/', ' [File Word đính kèm] ', $noi_dung))); // bỏ ##, ###, •, --- ở đầu dòng
        $plain = str_replace(['**','*'], '', $plain); // bỏ ký tự đậm/nghiêng
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        $excerpt  = mb_substr($plain, 0, 250);

        if (!$error) {
            if ($id) {
                db()->prepare("UPDATE posts SET tieu_de=:td,noi_dung=:nd,excerpt=:ex,tag=:tag,icon=:ic,tag_color=:tc,read_time=:rt,trang_thai=:tt,hinh_anh=:ha WHERE id=:id")
                   ->execute([':td'=>$tieu_de,':nd'=>$noi_dung,':ex'=>$excerpt,':tag'=>$tag,':ic'=>$icon,':tc'=>$color,':rt'=>$rt,':tt'=>$tt,':ha'=>$hinh,':id'=>$id]);
                $msg = 'Đã cập nhật bài viết.';
            } else {
                $count = (int)db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
                $slug  = 'bai-viet-'.($count+1);
                $i=1; while(true){ $chk=db()->prepare("SELECT COUNT(*) FROM posts WHERE slug=:s"); $chk->execute([':s'=>$slug]); if(!$chk->fetchColumn()) break; $slug='bai-viet-'.($count+1).'-'.$i++; }
                db()->prepare("INSERT INTO posts (tac_gia_id,tieu_de,slug,noi_dung,excerpt,tag,icon,tag_color,read_time,trang_thai,hinh_anh) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$_SESSION['user_id'],$tieu_de,$slug,$noi_dung,$excerpt,$tag,$icon,$color,$rt,$tt,$hinh]);
                $msg = 'Đã thêm bài viết mới.';
            }
        }

        // Lưu thất bại -> mở lại modal với dữ liệu vừa nhập, kèm thông báo lỗi (trước đây modal tự đóng và mất hết dữ liệu)
        if ($error) {
            $reopenPost = [
                'id'=>$id,'tieu_de'=>$tieu_de,'noi_dung'=>$noi_dung,'tag'=>$tag,'icon'=>$icon,
                'tag_color'=>$color,'read_time'=>$rt,'trang_thai'=>$tt,'hinh_anh'=>$hinh,
            ];
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
if (!empty($reopenPost)) { $editPost = $reopenPost; }

$tag_list = [
    ['Văn phòng','📄','#185FA5'],['Thiết kế','🎨','#f04923'],
    ['Bảo mật','🛡️','#A32D2D'],['Hướng dẫn','📖','#065E34'],
    ['Doanh nghiệp','💼','#534AB7'],['Developer','💻','#534AB7'],
    ['Mẹo hay','💡','#BA7517'],['Lưu trữ','☁️','#185FA5'],
    ['Đánh giá','⭐','#065E34'],['Tin tức','📰','#A32D2D'],
];

function bgByColor($c) {
    $m = [
        '#185FA5'=>'linear-gradient(135deg,#1a3a6b,#2563a8)',
        '#f04923'=>'linear-gradient(135deg,#1a0800,#f04923)',
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
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin-posts.css?v=<?= CSS_VER ?>">
<?php
$admExtraHead = ob_get_clean();
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">

    <!-- TOPBAR -->
    <div class="adm-topbar">
      <button onclick="document.querySelector('.adm-side').classList.toggle('open');document.querySelector('.adm-side-backdrop').classList.toggle('open')" class="adm-hamburger" aria-label="Mở menu" title="Menu">☰</button>
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <strong>Bài đăng</strong></div>
      <div class="adm-topbar-right">
        <button onclick="toggleTheme()" class="adm-theme-btn" title="Đổi giao diện sáng/tối" id="admThemeBtn">☀️</button>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-secondary" style="padding:6px 13px;font-size:12px">🌐 Xem website</a>
        <a href="<?= SITE_URL ?>/blog.php" class="btn btn-secondary" style="padding:6px 13px;font-size:12px">📰 Xem blog</a>
        <a href="<?= SITE_URL ?>/admin/migrate-word-blog.php" class="btn btn-secondary" style="padding:6px 13px;font-size:12px">📄 Chuyển bài cũ sang Word</a>
        <button class="btn btn-primary" onclick="openEditor()">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="1" x2="8" y2="15"/><line x1="1" y1="8" x2="15" y2="8"/></svg>
          Viết bài mới
        </button>
      </div>
    </div>
    <div class="adm-side-backdrop" onclick="document.querySelector('.adm-side').classList.remove('open');this.classList.remove('open')"></div>

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
    <div style="margin:16px 24px;padding:11px 15px;border-radius:9px;background:rgba(34,197,94,.1);color:#22c55e;border:1px solid #A7F3D0;font-size:13px;font-weight:500">✓ <?= htmlspecialchars($msg) ?></div>
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
                onclick='openEditor(<?= e(json_encode($featured,JSON_UNESCAPED_UNICODE)) ?>)'>✏️</button>
              <form method="POST" style="display:contents">
          <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $featured['id'] ?>">
                <button class="adm-mini adm-mini-t" title="<?= $isDraft_f?'Đăng':'Ẩn' ?>"><?= $isDraft_f?'🚀':'⏸' ?></button>
              </form>
              <form method="POST" style="display:contents" onsubmit="return confirm('Xoá bài này?')">
          <?= csrfField() ?>
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
              <span class="pf-read" style="cursor:pointer" onclick='openEditor(<?= e(json_encode($featured,JSON_UNESCAPED_UNICODE)) ?>)'>✏️ Sửa bài →</span>
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
                  onclick='openEditor(<?= e(json_encode($p,JSON_UNESCAPED_UNICODE)) ?>)'>✏️</button>
                <form method="POST" style="display:contents">
          <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="adm-mini adm-mini-t"><?= $isDraft_p?'🚀':'⏸' ?></button>
                </form>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xoá bài «<?= htmlspecialchars($p['tieu_de'],ENT_QUOTES) ?>»?')">
          <?= csrfField() ?>
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
                <span class="read-link" style="cursor:pointer" onclick='openEditor(<?= e(json_encode($p,JSON_UNESCAPED_UNICODE)) ?>)'>✏️ Sửa →</span>
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
            <button onclick="openEditor()" style="width:100%;padding:10px;background:linear-gradient(135deg,#065E34,#0A8A4E);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Be Vietnam Pro',sans-serif">✍️ Viết bài mới</button>
            <a href="<?= SITE_URL ?>/blog.php" style="display:block;text-align:center;padding:9px;background:rgba(255,255,255,.06);border:1px solid var(--b-border);border-radius:9px;font-size:13px;font-weight:600;color:var(--b-muted);text-decoration:none">📰 Xem blog công khai</a>
          </div>
        </div>

        <!-- Recent posts -->
        <div class="sidebar-widget">
          <div class="sw-header">📌 Bài viết gần đây</div>
          <div class="sw-body" style="padding:10px 14px">
            <?php
            $recent = db()->query("SELECT id,tieu_de,slug,icon,tag_color,hinh_anh,ngay_dang,trang_thai,noi_dung,tag,read_time FROM posts ORDER BY ngay_dang DESC LIMIT 6")->fetchAll();
            foreach($recent as $r):
              $rb = bgByColor($r['tag_color']??'#065E34');
            ?>
            <a class="recent-item" href="?edit=<?= $r['id'] ?>" onclick="event.preventDefault();openEditor(<?= e(json_encode($r,JSON_UNESCAPED_UNICODE)) ?>)">
              <div class="recent-thumb" style="background:<?= $rb ?>">
                <?php if(!empty($r['hinh_anh'])): ?>
                  <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($r['hinh_anh']) ?>" alt="" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none'">
                <?php else: ?>
                  <?= htmlspecialchars($r['icon']??'📝') ?>
                <?php endif; ?>
              </div>
              <?php
                // Dọn tiêu đề: gộp xuống dòng thật/ký tự "\n" thô về khoảng trắng và giới hạn độ dài,
                // để widget không bao giờ vỡ layout dù dữ liệu tiêu đề bị lỗi/quá dài.
                $rTitle = str_replace(['\\n', '\\r', "\n", "\r", "\t"], ' ', (string)($r['tieu_de'] ?? ''));
                $rTitle = trim(preg_replace('/\s+/u', ' ', $rTitle));
                if (mb_strlen($rTitle) > 70) $rTitle = mb_substr($rTitle, 0, 70) . '…';
              ?>
              <div class="recent-info">
                <div class="recent-title"><?= htmlspecialchars($rTitle) ?></div>
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
    <form method="POST" id="postForm" enctype="multipart/form-data">
          <?= csrfField() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="fId" value="0">
      <input type="hidden" name="tag_color" id="fColor" value="#065E34">
      <input type="hidden" name="icon" id="fIconH" value="📝">
      <input type="hidden" name="hinh_anh_cu" id="fHinhCu" value="">
      <div class="ep-body">
        <div class="adm-alert adm-alert-err" id="epError" style="display:none"></div>
        <div class="fg">
          <label>Ảnh đại diện bài viết</label>
          <div class="img-upload-area" onclick="document.getElementById('fHinh').click()">
            <input type="file" name="hinh_anh" id="fHinh" accept="image/*" style="display:none" onchange="previewPostImg(this)">
            <div id="postImgPreviewWrap">
              <div style="font-size:28px;margin-bottom:6px">🖼️</div>
              <div style="font-size:12px;color:var(--ink-3)">Click để chọn ảnh (JPG/PNG/WEBP, tối đa 5MB)</div>
            </div>
          </div>
        </div>
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
        <div class="fg" id="contentFg">
          <label>Nội dung *</label>
          <div class="tb-wrap">
            <button type="button" class="tb-b" onclick="ins('## Tiêu đề\n\n')">H2</button>
            <button type="button" class="tb-b" onclick="ins('### Phụ đề\n\n')">H3</button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-b" onclick="ins('**đậm**')"><b>B</b></button>
            <button type="button" class="tb-b" onclick="ins('*nghiêng*')"><i>I</i></button>
            <button type="button" class="tb-b" onclick="ins('__gạch chân__')"><u>U</u></button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-b" onclick="ins('\n• ')">• List</button>
            <button type="button" class="tb-b" onclick="ins('\n\n---\n\n')">── Line</button>
            <div class="tb-sep"></div>
            <button type="button" class="tb-b" id="wordImportBtn" onclick="document.getElementById('fWordImport').click()">📄 Đính kèm file Word</button>
            <input type="file" name="word_file" id="fWordImport" accept=".docx" style="display:none" onchange="importWordFile(this)">
            <span class="tb-word-chip" id="wordFileChip" style="display:none"></span>
            <div class="tb-sep"></div>
            <button type="button" class="tb-b" id="contentExpandBtn" onclick="openContentFull()" title="Mở rộng gần toàn màn hình để đọc/sửa dễ hơn">⛶ Mở rộng</button>
            <button type="button" class="tb-b" id="contentCollapseBtn" onclick="closeContentFull()" style="display:none">✕ Thu nhỏ</button>
            <span class="char-c" id="charC">0 ký tự</span>
          </div>
          <textarea name="noi_dung" id="fContent" placeholder="Viết nội dung ở đây..." required oninput="updC(this)"></textarea>
          <div class="tb-hint" style="font-size:11.5px;color:var(--b-muted);margin-top:6px">📄 Đính kèm file Word: chọn file .docx, trang blog sẽ <b>nhúng hiển thị đúng file gốc</b> qua Microsoft Office Online Viewer (không đọc/dựng lại bằng JS — dùng chính bộ máy hiển thị của Office nên đúng 100% như mở bằng Word). File tối đa 20MB. <b>Lưu ý:</b> tính năng xem trước này chỉ chạy được khi web đã lên hosting/domain thật — <b>không hoạt động trên localhost/XAMPP</b> vì Microsoft cần tải được file qua internet; lúc test ở localhost, khách vẫn tải file gốc về xem được bình thường.</div>
        </div>
        <div class="content-zoom-backdrop" id="contentZoomBackdrop" onclick="closeContentFull()"></div>
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

<script>window.SITE_URL = "<?= SITE_URL ?>";</script>
<script src="<?= SITE_URL ?>/assets/js/admin-posts.js"></script>
<?php if($editPost): ?>
<script>openEditor(<?= json_encode($editPost, JSON_UNESCAPED_UNICODE) ?><?= $error ? ', '.json_encode($error, JSON_UNESCAPED_UNICODE) : '' ?>);</script>
<?php endif; ?>
</body>
</html>