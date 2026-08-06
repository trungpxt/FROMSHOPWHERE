<?php
require_once __DIR__ . '/config.php';
startSession();
$_user   = currentUser();
$_isAdmin = isAdmin();
$currentPage = 'blog';

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

function mdInlineFmt(string $text): string {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    $text = preg_replace('/__(.+?)__/s', '<u>$1</u>', $text);
    return $text;
}

/* Bảng/ảnh nhập từ Word được lưu nguyên dạng HTML (thay vì chuyển thành text như tiêu đề/đậm/nghiêng)
   để giữ đúng cấu trúc bảng và hình ảnh thật. Trước khi in ra trang công khai, lọc bỏ mọi thứ có thể
   gây hại (script, thuộc tính on*=, link javascript:) — dù chỉ admin mới đăng được nội dung này. */
function sanitizeRawHtmlBlock(string $html): string {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html);
    $html = preg_replace('/\son\w+\s*=\s*[^\s>]+/i', '', $html);
    $html = preg_replace('/\s(src|href)\s*=\s*"\s*javascript:[^"]*"/i', '', $html);
    $html = preg_replace("/\s(src|href)\s*=\s*'\s*javascript:[^']*'/i", '', $html);
    return $html;
}

/* Khối nội dung nhập từ Word qua docx-preview.js được lưu nguyên dạng HTML+CSS
   (font, cỡ chữ, màu sắc, bảng, ảnh y hệt bản Word gốc) đánh dấu bằng comment
   "<!--fsw-word-block-->" ở đầu khối. Trước khi in ra trang công khai, lọc bỏ
   mọi thứ có thể gây hại (script, iframe, thuộc tính on*=, link javascript:,
   và bên trong <style> chặn @import / expression() / url(javascript:)) — dù
   chỉ admin mới đăng được nội dung này. */
function sanitizeWordHtmlBlock(string $html): string {
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
    $html = preg_replace('/<(iframe|object|embed|link|meta)\b[^>]*>/i', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html);
    $html = preg_replace('/\son\w+\s*=\s*[^\s>]+/i', '', $html);
    $html = preg_replace('/\s(src|href)\s*=\s*"\s*javascript:[^"]*"/i', '', $html);
    $html = preg_replace("/\s(src|href)\s*=\s*'\s*javascript:[^']*'/i", '', $html);
    $html = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function($m) {
        $css = $m[1];
        $css = preg_replace('/@import[^;]*;?/i', '', $css);
        $css = preg_replace('/expression\s*\([^)]*\)/i', '', $css);
        $css = preg_replace('/url\s*\(\s*[\'"]?\s*javascript:[^)]*\)/i', '', $css);
        return '<style>' . $css . '</style>';
    }, $html);
    return $html;
}

function renderContent(string $raw): string {
    $raw = str_replace(["\r\n", "\r"], "\n", trim($raw)); // form gửi textarea bằng \r\n, chuẩn hoá về \n
    if ($raw === '') return '';

    $out = '';
    // Nội dung được soạn theo cú pháp của thanh công cụ editor:
    // "## " = H2, "### " = H3, "**đậm**", "*nghiêng*", "• " = danh sách, "---" = gạch ngang.
    // Trước đây hàm này bỏ qua hoàn toàn cú pháp trên, khiến bài viết (đặc biệt bài
    // nhập từ Word, vốn luôn sinh ra đúng cú pháp này) hiển thị lộ ký tự ##, **, • thô.
    $blocks = preg_split('/\n{2,}/', $raw);
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;

        if ($block === '---') { $out .= '<hr>'; continue; }

        // File Word (.docx) đính kèm — hiển thị THẲNG file gốc, không đọc/dựng lại giao diện bằng JS.
        // Nhúng qua Microsoft Office Online Viewer: chính bộ máy hiển thị của Office render file
        // thật (dạng ảnh trang lật được) trong iframe, nên đúng 100% như mở bằng Word.
        // LƯU Ý QUAN TRỌNG: Office Online Viewer là dịch vụ của Microsoft chạy trên internet, nó
        // phải TỰ TẢI được file qua URL công khai -> KHÔNG hoạt động khi web đang chạy ở
        // localhost/127.0.0.1 (máy XAMPP cục bộ), vì Microsoft không thể truy cập vào máy bạn.
        // Chỉ hiển thị được sau khi đưa web lên hosting/domain thật có thể truy cập từ internet.
        if (preg_match('/^<!--fsw-word-file:(.*?)-->$/s', $block, $m)) {
            $relPath = trim($m[1]);
            // Chỉ chấp nhận đường dẫn hợp lệ bên trong uploads/blog-docs/ — chặn path traversal (../)
            if (preg_match('#^blog-docs/[A-Za-z0-9._-]+\.docx$#', $relPath)) {
                $docUrl  = SITE_URL . '/uploads/' . $relPath;
                $isLocal = (bool)preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?/#i', $docUrl . '/');
                $out .= '<div class="fsw-word-doc-embed">';
                if ($isLocal) {
                    // Đang chạy trên localhost -> Office Online không tải được file để hiển thị.
                    // Phương án dự phòng CHỈ dùng lúc phát triển local: tự dựng lại file bằng
                    // docx-preview.js ngay trong trình duyệt (xem assets/js/blog-detail.js) để
                    // bạn vẫn xem trước được lúc test trên XAMPP. Độ chính xác rất cao nhưng vẫn
                    // là "dựng lại", không phải file gốc 100% như bản Office Online ở hosting thật.
                    $out .= '<div class="fsw-word-doc-notice" style="margin-bottom:10px">📄 Đang ở localhost nên xem trước bằng bản dựng lại (không phải Office Online) — lên hosting thật sẽ tự chuyển sang hiển thị file gốc 100%.</div>';
                    $out .= '<div class="fsw-word-doc-embed" data-docx-src="' . htmlspecialchars($docUrl, ENT_QUOTES) . '"><div class="fsw-word-doc-loading">📄 Đang tải nội dung file Word…</div></div>';
                } else {
                    $embedSrc = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($docUrl);
                    $out .= '<iframe class="fsw-word-doc-frame" src="' . htmlspecialchars($embedSrc, ENT_QUOTES) . '" frameborder="0" loading="lazy"></iframe>';
                }
                $out .= '<a class="fsw-word-doc-download" href="' . htmlspecialchars($docUrl, ENT_QUOTES) . '" download>⬇ Tải file Word gốc (.docx)</a>';
                $out .= '</div>';
            }
            continue;
        }

        // Khối nhập từ Word kiểu cũ (đã quy đổi sẵn ra HTML+CSS lúc lưu bài) — giữ để bài viết cũ vẫn hiển thị đúng
        if (strpos($block, '<!--fsw-word-block-->') === 0) {
            $out .= sanitizeWordHtmlBlock(substr($block, strlen('<!--fsw-word-block-->')));
            continue;
        }

        // Bảng / ảnh nhập từ Word (cách cũ, các bài đã đăng trước đây) — giữ nguyên HTML thật
        if (preg_match('/^<table[\s>]/i', $block) && preg_match('/<\/table>\s*$/i', $block)) {
            $out .= '<div class="post-table-wrap">' . sanitizeRawHtmlBlock($block) . '</div>';
            continue;
        }
        if (preg_match('/^<img\s[^>]*>$/i', $block)) {
            $out .= sanitizeRawHtmlBlock($block);
            continue;
        }

        if (preg_match('/^##\s+(.+)$/s', $block, $m)) { $out .= '<h2>' . mdInlineFmt(trim($m[1])) . '</h2>'; continue; }
        if (preg_match('/^###\s+(.+)$/s', $block, $m)) { $out .= '<h3>' . mdInlineFmt(trim($m[1])) . '</h3>'; continue; }

        $lines = preg_split('/\n/', $block);
        $isList = true;
        foreach ($lines as $l) {
            if (strncmp(trim($l), '• ', strlen('• ')) !== 0) { $isList = false; break; }
        }
        if ($isList) {
            $out .= '<ul>';
            foreach ($lines as $l) {
                $item = preg_replace('/^•\s*/', '', trim($l));
                $out .= '<li>' . mdInlineFmt($item) . '</li>';
            }
            $out .= '</ul>';
            continue;
        }

        $out .= '<p>' . nl2br(mdInlineFmt($block)) . '</p>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
$seoDesc = trim((string)($post['excerpt'] ?? ''));
$seoDesc = preg_replace('/\s+/', ' ', $seoDesc);
if ($seoDesc === '') {
    $seoDesc = 'Bài viết trên blog FROMSHOPWHERE — hướng dẫn và mẹo sử dụng phần mềm bản quyền.';
}
if (mb_strlen($seoDesc) > 155) {
    $seoDesc = mb_substr($seoDesc, 0, 152) . '...';
}
$seoImg = !empty($post['hinh_anh']) ? SITE_URL . '/images/' . $post['hinh_anh'] : SITE_URL . '/images/ui/logo.png';
?>
<title><?= htmlspecialchars($post['tieu_de']) ?> — FROMSHOPWHERE</title>
<meta name="description" content="<?= htmlspecialchars($seoDesc) ?>">
<link rel="canonical" href="<?= SITE_URL ?>/blog-detail.php?slug=<?= urlencode($post['slug']) ?>">
<meta property="og:type" content="article">
<meta property="og:title" content="<?= htmlspecialchars($post['tieu_de']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($seoDesc) ?>">
<meta property="og:image" content="<?= htmlspecialchars($seoImg) ?>">
<meta property="og:url" content="<?= SITE_URL ?>/blog-detail.php?slug=<?= urlencode($post['slug']) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/blog-detail.css">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>


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
            <?= csrfField() ?>
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" value="<?= $post['id'] ?>">
        <button style="padding:7px 14px;background:#FEF3C7;color:#92400E;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer" type="submit">👁 Ẩn bài</button>
      </form>
      <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Xoá bài viết này vĩnh viễn?')">
            <?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $post['id'] ?>">
        <button style="padding:7px 14px;background:rgba(239,68,68,.1);color:var(--error,#ef4444);border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer" type="submit">🗑 Xoá</button>
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
    <p>Phần mềm chính hãng từ Adobe, Microsoft, Kaspersky... Giá tốt nhất — Giao key qua email trong 5 giây!</p>
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
            <img src="images/<?= htmlspecialchars($r['hinh_anh']) ?>" alt="<?= htmlspecialchars($r['tieu_de']) ?>">
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
<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="assets/js/blog-detail.js"></script>
</body>
</html>
