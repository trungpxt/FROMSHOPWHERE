<?php
/**
 * CÔNG CỤ CHUYỂN ĐỔI 1 LẦN: lấy nội dung các bài blog CŨ (viết theo cú pháp
 * ## / ** / • / <table> / <img> của thanh công cụ) rồi tạo ra 1 file .docx
 * thật cho mỗi bài, lưu vào uploads/blog-docs/, sau đó gắn bài đó vào file
 * .docx vừa tạo — giống hệt cơ chế "Đính kèm file Word" của bài viết mới.
 *
 * Dùng thư viện PHPWord (đã vendor sẵn trong vendor/phpoffice/) để dựng file
 * .docx thật từ nội dung cũ, không phải chuyển đổi giả.
 *
 * AN TOÀN: chỉ THỰC SỰ đổi dữ liệu khi bấm nút xác nhận (POST). Mở trang này
 * bằng GET chỉ hiện DANH SÁCH xem trước (không đổi gì). Bài nào đã có file
 * Word đính kèm rồi (marker <!--fsw-word-file:...--> hoặc <!--fsw-word-block-->)
 * sẽ được bỏ qua, không đụng tới.
 */
require_once __DIR__ . '/../config.php';
requireAdmin();
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

/* ── Các hàm dựng file Word từ nội dung cũ (dùng lại đúng logic phân đoạn
   như hàm renderContent() trong blog-detail.php, để bản Word tạo ra khớp
   với những gì đang hiển thị trên web hiện tại) ── */

function fswStripInlineMd(string $t): string {
    $t = preg_replace(['/\*\*(.+?)\*\*/s', '/\*(.+?)\*/s', '/__(.+?)__/s'], '$1', $t);
    return html_entity_decode($t, ENT_QUOTES, 'UTF-8');
}

function fswAddParagraph($section, string $text): void {
    $run = $section->addTextRun(['spaceAfter' => 160]);
    $parts = preg_split('/(\*\*.+?\*\*|\*.+?\*|__.+?__)/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    if (!$parts) { $run->addText(' '); return; }
    foreach ($parts as $part) {
        $dec = fn($s) => html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        if (preg_match('/^\*\*(.+)\*\*$/s', $part, $m)) $run->addText($dec($m[1]), ['bold' => true]);
        elseif (preg_match('/^__(.+)__$/s', $part, $m)) $run->addText($dec($m[1]), ['underline' => 'single']);
        elseif (preg_match('/^\*(.+)\*$/s', $part, $m)) $run->addText($dec($m[1]), ['italic' => true]);
        else $run->addText($dec($part));
    }
}

function fswAddHtmlTable($section, string $html): void {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
    libxml_clear_errors();
    $table = $dom->getElementsByTagName('table')->item(0);
    if (!$table) return;
    $wTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80, 'width' => 100 * 50, 'unit' => 'pct']);
    foreach ($table->getElementsByTagName('tr') as $tr) {
        $wTable->addRow();
        foreach ($tr->childNodes as $cellNode) {
            if (!($cellNode instanceof DOMElement)) continue;
            $tag = strtolower($cellNode->tagName);
            if ($tag !== 'td' && $tag !== 'th') continue;
            $cell = $wTable->addCell(9000);
            $cell->addText(trim($cellNode->textContent), $tag === 'th' ? ['bold' => true] : []);
        }
    }
}

function fswAddImage($section, string $src): void {
    if (stripos($src, 'data:image/') === 0) {
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/is', $src, $m)) {
            $data = base64_decode($m[2]);
            if ($data === false) return;
            $tmp = tempnam(sys_get_temp_dir(), 'fswimg') . '.' . strtolower($m[1]);
            file_put_contents($tmp, $data);
            // QUAN TRỌNG: PHPWord chỉ thực sự ĐỌC file ảnh lúc save() (không phải lúc addImage()),
            // nên KHÔNG được xoá file tạm ngay ở đây — phải đợi tới khi IOFactory->save() hoàn tất
            // toàn bộ tài liệu thì mới xoá (dọn qua register_shutdown_function ở cuối script).
            try { $section->addImage($tmp, ['width' => 420, 'style' => ['alignment' => 'center']]); } catch (\Throwable $e) {}
            register_shutdown_function(function () use ($tmp) { @unlink($tmp); });
        }
        return;
    }
    // Ảnh dạng đường dẫn thật trên site (vd images/blog/xxx.jpg hoặc URL đầy đủ)
    $path = preg_match('#^https?://#i', $src)
        ? preg_replace('#^https?://[^/]+/#i', __DIR__ . '/../', $src)
        : __DIR__ . '/../' . ltrim($src, '/');
    if (is_file($path)) {
        try { $section->addImage($path, ['width' => 420, 'style' => ['alignment' => 'center']]); } catch (\Throwable $e) {}
    }
}

function fswBuildWordFromLegacyContent(PhpWord $word, string $raw): void {
    $section = $word->addSection(['marginTop' => 600, 'marginBottom' => 600, 'marginLeft' => 700, 'marginRight' => 700]);
    $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
    if ($raw === '') { $section->addText(' '); return; }

    $blocks = preg_split('/\n{2,}/', $raw);
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '' ) continue;
        if ($block === '---') { $section->addTextBreak(1); continue; }
        // Phòng hờ: bỏ qua nếu lỡ có mốc file Word cũ lẫn trong nội dung (không nên xảy ra)
        if (strpos($block, '<!--fsw-word-file:') === 0 || strpos($block, '<!--fsw-word-block-->') === 0) continue;

        if (preg_match('/^<table[\s>]/i', $block) && preg_match('/<\/table>\s*$/i', $block)) {
            fswAddHtmlTable($section, $block);
            continue;
        }
        if (preg_match('/^<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>$/i', $block, $mi)) {
            fswAddImage($section, $mi[1]);
            continue;
        }
        if (preg_match('/^##\s+(.+)$/s', $block, $m)) { $section->addTitle(fswStripInlineMd(trim($m[1])), 2); continue; }
        if (preg_match('/^###\s+(.+)$/s', $block, $m)) { $section->addTitle(fswStripInlineMd(trim($m[1])), 3); continue; }

        $lines = preg_split('/\n/', $block);
        $isList = count($lines) > 0;
        foreach ($lines as $l) { if (strpos(trim($l), '• ') !== 0) { $isList = false; break; } }
        if ($isList) {
            foreach ($lines as $l) {
                $item = preg_replace('/^•\s*/', '', trim($l));
                $section->addListItem(fswStripInlineMd($item), 0, null, null, ['spaceAfter' => 60]);
            }
            continue;
        }

        fswAddParagraph($section, $block);
    }
}

/* ── Lấy danh sách bài cần chuyển đổi ── */
$posts = db()->query("SELECT id, tieu_de, slug, noi_dung FROM posts ORDER BY id")->fetchAll();
$toMigrate = array_values(array_filter($posts, function ($p) {
    return strpos($p['noi_dung'], '<!--fsw-word-file:') === false
        && strpos($p['noi_dung'], '<!--fsw-word-block-->') === false
        && trim($p['noi_dung']) !== '';
}));
$alreadyDone = count($posts) - count($toMigrate);

$result = null; $migErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === '1') {
    csrfCheck();
    if (!is_dir(UPLOAD_BLOG_DOCS_DIR)) { @mkdir(UPLOAD_BLOG_DOCS_DIR, 0775, true); }
    $ok = 0;
    foreach ($toMigrate as $p) {
        try {
            $word = new PhpWord();
            fswBuildWordFromLegacyContent($word, $p['noi_dung']);
            $baseSlug = $p['slug'] ?: ('bai-' . $p['id']);
            $fn  = $baseSlug . '-migrated-' . time() . mt_rand(100, 999) . '.docx';
            $dst = UPLOAD_BLOG_DOCS_DIR . $fn;
            IOFactory::createWriter($word, 'Word2007')->save($dst);
            $marker = '<!--fsw-word-file:blog-docs/' . $fn . '-->';
            $stmt = db()->prepare("UPDATE posts SET noi_dung = ? WHERE id = ?");
            $stmt->execute([$marker, $p['id']]);
            $ok++;
        } catch (\Throwable $e) {
            $migErrors[] = $p['tieu_de'] . ': ' . $e->getMessage();
        }
    }
    $result = $ok;
    // Nạp lại danh sách sau khi chuyển đổi
    $posts = db()->query("SELECT id, tieu_de, slug, noi_dung FROM posts ORDER BY id")->fetchAll();
    $toMigrate = array_values(array_filter($posts, function ($p) {
        return strpos($p['noi_dung'], '<!--fsw-word-file:') === false
            && strpos($p['noi_dung'], '<!--fsw-word-block-->') === false
            && trim($p['noi_dung']) !== '';
    }));
}

$admPageTitle = 'Chuyển bài blog cũ sang file Word — Admin FSW';
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

    <div class="adm-topbar">
      <button onclick="document.querySelector('.adm-side').classList.toggle('open');document.querySelector('.adm-side-backdrop').classList.toggle('open')" class="adm-hamburger" aria-label="Mở menu" title="Menu">☰</button>
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <a href="<?= SITE_URL ?>/admin/posts.php">Bài đăng</a> <span class="sep">/</span> <strong>Chuyển sang file Word</strong></div>
    </div>
    <div class="adm-side-backdrop" onclick="document.querySelector('.adm-side').classList.remove('open');this.classList.remove('open')"></div>

    <div style="max-width:760px;margin:24px auto;padding:0 16px">
      <h1 style="font-size:22px;margin-bottom:6px">📄 Chuyển bài blog cũ sang file Word đính kèm</h1>
      <p style="color:var(--text-muted,#7873A0);font-size:13.5px;line-height:1.6;margin-bottom:20px">
        Công cụ này tạo 1 file <b>.docx thật</b> cho từng bài blog đang viết theo cách cũ
        (##, **, •, bảng, ảnh...), lưu vào <code>uploads/blog-docs/</code>, rồi gắn bài đó
        vào file .docx vừa tạo — hiển thị y hệt cơ chế bài viết mới (nhúng file gốc qua
        Office Online / xem trước bằng docx-preview khi ở localhost). Bài đã có file Word
        đính kèm rồi sẽ tự bỏ qua, không đụng tới. Nên <b>sao lưu database</b> trước khi bấm
        xác nhận, phòng trường hợp cần khôi phục lại nội dung gốc.
      </p>

      <?php if ($result !== null): ?>
        <div style="padding:14px 16px;border-radius:10px;background:#EEECFB;color:#3B2FA0;font-weight:700;margin-bottom:16px">
          ✅ Đã chuyển đổi thành công <?= (int)$result ?> bài viết.
        </div>
        <?php if ($migErrors): ?>
          <div style="padding:14px 16px;border-radius:10px;background:#FCEAEB;color:#B0242B;margin-bottom:16px;font-size:13px">
            ⚠ Có <?= count($migErrors) ?> bài lỗi, giữ nguyên nội dung cũ:
            <ul style="margin:6px 0 0 18px">
              <?php foreach ($migErrors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div style="display:flex;gap:10px;margin-bottom:18px">
        <div style="flex:1;padding:14px 16px;border-radius:10px;background:var(--bg-alt,#F1EFFB);text-align:center">
          <div style="font-size:22px;font-weight:800"><?= count($toMigrate) ?></div>
          <div style="font-size:12px;color:var(--text-muted,#7873A0)">Bài cần chuyển đổi</div>
        </div>
        <div style="flex:1;padding:14px 16px;border-radius:10px;background:var(--bg-alt,#F1EFFB);text-align:center">
          <div style="font-size:22px;font-weight:800"><?= (int)$alreadyDone ?></div>
          <div style="font-size:12px;color:var(--text-muted,#7873A0)">Đã có file Word / bỏ qua</div>
        </div>
      </div>

      <?php if ($toMigrate): ?>
        <div style="border:1px solid var(--border,#E3E0F5);border-radius:10px;overflow:hidden;margin-bottom:20px">
          <?php foreach ($toMigrate as $p): ?>
            <div style="padding:10px 14px;border-bottom:1px solid var(--border,#E3E0F5);font-size:13.5px">📝 <?= e($p['tieu_de']) ?></div>
          <?php endforeach; ?>
        </div>

        <form method="POST" onsubmit="return confirm('Chuyển đổi <?= count($toMigrate) ?> bài viết sang file Word? Hành động này sẽ thay nội dung các bài trên bằng file .docx mới tạo.')">
          <?= csrfField() ?>
          <input type="hidden" name="confirm" value="1">
          <button type="submit" class="btn btn-primary" style="padding:10px 22px">✅ Xác nhận chuyển đổi <?= count($toMigrate) ?> bài</button>
        </form>
      <?php else: ?>
        <div style="padding:20px;text-align:center;color:var(--text-muted,#7873A0)">🎉 Không còn bài nào cần chuyển đổi.</div>
      <?php endif; ?>

      <p style="margin-top:24px"><a href="<?= SITE_URL ?>/admin/posts.php">← Quay lại quản lý bài đăng</a></p>
    </div>

  </main>
</div>
</body>
</html>
