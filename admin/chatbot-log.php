<?php
/* ══════════════════════════════════════════════════════════════════
   admin/chatbot-log.php — Theo dõi CHẤT LƯỢNG chatbot

   Gồm 2 nguồn dữ liệu bổ trợ nhau:
   1. chatbot_missed — câu hỏi bot HOÀN TOÀN không nhận diện được.
   2. chatbot_feedback — câu bot ĐÃ trả lời nhưng bị khách đánh giá 👎
      chưa đúng ý (góc mù mà (1) không thấy được).
   Xem thường xuyên để biết nên bổ sung/sửa FAQ nào tại admin/chatbot-faq.php.
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';
requireAdmin();

try {
    db()->exec("CREATE TABLE IF NOT EXISTS chatbot_missed (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        cau_hoi       VARCHAR(800) NOT NULL,
        cau_hoi_chuan VARCHAR(500) NOT NULL,
        thoi_gian     DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chuan (cau_hoi_chuan(191))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->exec("CREATE TABLE IF NOT EXISTS chatbot_feedback (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        cau_hoi   VARCHAR(800) NOT NULL,
        tra_loi   TEXT NOT NULL,
        danh_gia  ENUM('tot','chua_tot') NOT NULL,
        thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $a = $_POST['action'] ?? '';
    if ($a === 'delete') {
        db()->prepare("DELETE FROM chatbot_missed WHERE id=:id")->execute([':id' => (int)$_POST['id']]);
        $msg = 'Đã xoá.';
    }
    if ($a === 'clear_all') {
        db()->exec("TRUNCATE TABLE chatbot_missed");
        $msg = 'Đã xoá toàn bộ log.';
    }
    if ($a === 'delete_fb') {
        db()->prepare("DELETE FROM chatbot_feedback WHERE id=:id")->execute([':id' => (int)$_POST['id']]);
        $msg = 'Đã xoá đánh giá.';
    }
    if ($a === 'clear_fb') {
        db()->exec("TRUNCATE TABLE chatbot_feedback");
        $msg = 'Đã xoá toàn bộ đánh giá.';
    }
}

$fbGood = (int) db()->query("SELECT COUNT(*) FROM chatbot_feedback WHERE danh_gia='tot'")->fetchColumn();
$fbBad  = (int) db()->query("SELECT COUNT(*) FROM chatbot_feedback WHERE danh_gia='chua_tot'")->fetchColumn();
$fbBadRows = db()->query(
    "SELECT * FROM chatbot_feedback WHERE danh_gia='chua_tot' ORDER BY thoi_gian DESC LIMIT 100"
)->fetchAll();


$total = (int) db()->query("SELECT COUNT(*) FROM chatbot_missed")->fetchColumn();
$last7 = (int) db()->query("SELECT COUNT(*) FROM chatbot_missed WHERE thoi_gian >= NOW() - INTERVAL 7 DAY")->fetchColumn();

/* Top câu hỏi lặp lại nhiều nhất (theo bản đã chuẩn hoá) — đáng ưu tiên bổ
   sung từ khoá cho chatbot trước, vì nhiều khách cùng hỏi mà bot không hiểu */
$topRepeated = db()->query(
    "SELECT cau_hoi_chuan, COUNT(*) AS so_lan, MAX(cau_hoi) AS mau_cau, MAX(thoi_gian) AS gan_nhat
     FROM chatbot_missed GROUP BY cau_hoi_chuan
     HAVING so_lan > 1 ORDER BY so_lan DESC, gan_nhat DESC LIMIT 10"
)->fetchAll();

$s = trim($_GET['s'] ?? '');
$where = ''; $params = [];
if ($s) { $where = 'WHERE cau_hoi LIKE :s'; $params[':s'] = "%$s%"; }
$stmt = db()->prepare("SELECT * FROM chatbot_missed $where ORDER BY thoi_gian DESC LIMIT 200");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$admPageTitle  = 'Chất lượng Chatbot — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel  = 'Chất lượng Chatbot';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <div class="adm-topbar">
      <button onclick="document.querySelector('.adm-side').classList.toggle('open');document.querySelector('.adm-side-backdrop').classList.toggle('open')" class="adm-hamburger" aria-label="Mở menu" title="Menu">☰</button>
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <strong>Chất lượng Chatbot</strong></div>
      <div class="adm-topbar-right">
        <button onclick="toggleTheme()" class="adm-theme-btn" title="Đổi giao diện" id="admThemeBtn">☀️</button>
        <a href="<?= SITE_URL ?>/admin/chatbot-faq.php" class="btn btn-secondary">🤖 FAQ Chatbot</a>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-secondary">🌐 Xem website</a>
      </div>
    </div>
    <div class="adm-side-backdrop" onclick="document.querySelector('.adm-side').classList.remove('open');this.classList.remove('open')"></div>

    <div class="adm-content">
      <?php if ($msg): ?>
      <div class="adm-alert adm-alert-ok" style="margin-bottom:16px">✓ <?= e($msg) ?></div>
      <?php endif; ?>

      <p style="font-size:13px;color:var(--ink-3);margin:0 0 18px;line-height:1.6">
        Trang này theo dõi 2 loại "điểm mù" của chatbot: câu hỏi bot <strong>hoàn toàn không hiểu</strong>,
        và câu bot đã trả lời nhưng bị khách đánh giá <strong>👎 chưa đúng ý</strong>.
        Xem thường xuyên để biết nên bổ sung/sửa FAQ nào tại
        <a href="<?= SITE_URL ?>/admin/chatbot-faq.php">FAQ Chatbot</a>.
      </p>

      <!-- STATS -->
      <div class="stats-grid stats-grid-3" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-icon si-blue">❓</div><div><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Câu hỏi chưa hiểu</div></div></div>
        <div class="stat-card"><div class="stat-icon si-green">👍</div><div><div class="stat-num"><?= $fbGood ?></div><div class="stat-lbl">Đánh giá tốt</div></div></div>
        <div class="stat-card"><div class="stat-icon si-red">👎</div><div><div class="stat-num"><?= $fbBad ?></div><div class="stat-lbl">Đánh giá chưa tốt</div></div></div>
      </div>

      <?php if ($fbBadRows): ?>
      <div class="table-card" style="margin-bottom:20px">
        <div style="padding:16px 18px 10px;display:flex;align-items:center;justify-content:space-between">
          <span style="font-weight:800;font-size:14px">👎 Câu trả lời bị đánh giá chưa đúng ý</span>
          <form method="POST" onsubmit="return confirm('Xoá TOÀN BỘ đánh giá 👎? Không thể hoàn tác.')">
          <?= csrfField() ?>
            <input type="hidden" name="action" value="clear_fb">
            <button type="submit" class="btn btn-secondary" style="font-size:12px;padding:6px 12px">🗑️ Xoá hết</button>
          </form>
        </div>
        <table>
          <thead><tr><th>Khách hỏi</th><th>Bot đã trả lời</th><th>Thời gian</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($fbBadRows as $f): ?>
            <tr>
              <td style="font-size:13px;max-width:220px"><?= e($f['cau_hoi']) ?></td>
              <td style="font-size:12.5px;max-width:300px;color:var(--ink-3)"><?= e(mb_substr($f['tra_loi'],0,140)) ?><?= mb_strlen($f['tra_loi'])>140?'...':'' ?></td>
              <td class="mono text-muted2"><?= date('d/m/Y H:i', strtotime($f['thoi_gian'])) ?></td>
              <td>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xoá dòng này?')">
          <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete_fb">
                  <input type="hidden" name="id" value="<?= $f['id'] ?>">
                  <button class="act-btn ab-del" type="submit" title="Xoá">🗑</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php if ($topRepeated): ?>
      <div class="table-card" style="margin-bottom:20px">
        <div style="padding:16px 18px 4px;font-weight:800;font-size:14px">🔁 Đáng ưu tiên bổ sung (nhiều khách cùng hỏi)</div>
        <table>
          <thead><tr><th>Câu hỏi mẫu</th><th>Số lần</th><th>Gần nhất</th></tr></thead>
          <tbody>
          <?php foreach ($topRepeated as $r): ?>
            <tr>
              <td style="font-size:13px"><?= e($r['mau_cau']) ?></td>
              <td><span class="badge b-red"><span class="badge-dot"></span><?= $r['so_lan'] ?> lần</span></td>
              <td class="mono text-muted2"><?= date('d/m/Y H:i', strtotime($r['gan_nhat'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <form method="GET" style="display:contents">
            <div class="search-wrap">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6.5" cy="6.5" r="5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
              <input class="search-input" type="text" name="s" value="<?= e($s) ?>" placeholder="Tìm trong câu hỏi...">
            </div>
            <button class="btn-search" type="submit">Tìm</button>
            <?php if ($s): ?><a href="?" style="font-size:12px;color:var(--ink-3);padding:8px 4px">✕</a><?php endif; ?>
          </form>
        </div>
        <?php if ($total > 0): ?>
        <form method="POST" onsubmit="return confirm('Xoá TOÀN BỘ log câu hỏi chưa hiểu? Không thể hoàn tác.')">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="clear_all">
          <button type="submit" class="btn btn-secondary" style="font-size:13px">🗑️ Xoá toàn bộ log</button>
        </form>
        <?php endif; ?>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead><tr><th>#</th><th>Câu hỏi khách gõ</th><th>Thời gian</th><th>Thao tác</th></tr></thead>
          <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="4" class="table-empty"><span class="te-icon">🎉</span>Chưa có câu hỏi nào chatbot bị "bí" — hoặc chưa ai chat thử.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td class="mono text-muted2"><?= $r['id'] ?></td>
            <td style="font-size:13px;max-width:420px"><?= e($r['cau_hoi']) ?></td>
            <td class="mono text-muted2"><?= date('d/m/Y H:i', strtotime($r['thoi_gian'])) ?></td>
            <td>
              <form method="POST" style="display:contents" onsubmit="return confirm('Xoá dòng này?')">
          <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="act-btn ab-del" type="submit" title="Xoá">🗑</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

</body>
</html>
