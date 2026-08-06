<?php
/* ══════════════════════════════════════════════════════════════════
   admin/chatbot-faq.php — Quản lý câu hỏi/trả lời (FAQ) của chatbot

   Đây là "bộ não" của chatbot: mỗi dòng gồm 1 nhóm từ khoá (cách nhau bởi
   dấu phẩy) và 1 câu trả lời tương ứng. Khi khách chat, api/chatbot.php sẽ
   so khớp câu hỏi của khách với các từ khoá này để chọn câu trả lời phù hợp
   nhất — không cần sửa code, admin tự thêm/sửa/xoá tại đây.

   Mẹo viết từ khoá: gõ liền không dấu hay có dấu đều được (hệ thống tự bỏ
   dấu khi so khớp), cách nhau bởi dấu phẩy, càng nhiều biến thể càng dễ
   khớp trúng — vd: "đổi trả, hoàn tiền, key lỗi, key die, refund"
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';
requireAdmin();

try {
    db()->exec("CREATE TABLE IF NOT EXISTS chatbot_faq (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        tu_khoa    VARCHAR(600) NOT NULL,
        tra_loi    TEXT NOT NULL,
        kich_hoat  TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $a = $_POST['action'] ?? '';
    if ($a === 'save') {
        $id  = (int) ($_POST['id'] ?? 0);
        $tk  = trim($_POST['tu_khoa'] ?? '');
        $tl  = trim($_POST['tra_loi'] ?? '');
        $on  = isset($_POST['kich_hoat']) ? 1 : 0;
        if ($tk !== '' && $tl !== '') {
            if ($id) {
                db()->prepare("UPDATE chatbot_faq SET tu_khoa=:k, tra_loi=:r, kich_hoat=:on WHERE id=:id")
                    ->execute([':k' => $tk, ':r' => $tl, ':on' => $on, ':id' => $id]);
                $msg = 'Đã cập nhật.';
            } else {
                db()->prepare("INSERT INTO chatbot_faq (tu_khoa, tra_loi, kich_hoat) VALUES (:k, :r, :on)")
                    ->execute([':k' => $tk, ':r' => $tl, ':on' => $on]);
                $msg = 'Đã thêm câu trả lời mới.';
            }
        } else {
            $msg = '⚠️ Vui lòng nhập đủ từ khoá và câu trả lời.';
        }
    }
    if ($a === 'toggle') {
        db()->prepare("UPDATE chatbot_faq SET kich_hoat = 1 - kich_hoat WHERE id=:id")
            ->execute([':id' => (int) $_POST['id']]);
        $msg = 'Đã đổi trạng thái.';
    }
    if ($a === 'delete') {
        db()->prepare("DELETE FROM chatbot_faq WHERE id=:id")->execute([':id' => (int) $_POST['id']]);
        $msg = 'Đã xoá.';
    }
}

$s = trim($_GET['s'] ?? '');
$where = ''; $params = [];
if ($s) { $where = 'WHERE tu_khoa LIKE :s OR tra_loi LIKE :s2'; $params[':s'] = "%$s%"; $params[':s2'] = "%$s%"; }
$stmt = db()->prepare("SELECT * FROM chatbot_faq $where ORDER BY id DESC");
$stmt->execute($params);
$faqs = $stmt->fetchAll();
$total = count($faqs);
$activeCount = count(array_filter($faqs, fn($f) => (int) $f['kich_hoat'] === 1));

$admPageTitle  = 'FAQ Chatbot — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel  = 'FAQ Chatbot';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <div class="adm-topbar">
      <button onclick="document.querySelector('.adm-side').classList.toggle('open');document.querySelector('.adm-side-backdrop').classList.toggle('open')" class="adm-hamburger" aria-label="Mở menu" title="Menu">☰</button>
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <strong>FAQ Chatbot</strong></div>
      <div class="adm-topbar-right">
        <button onclick="toggleTheme()" class="adm-theme-btn" title="Đổi giao diện" id="admThemeBtn">☀️</button>
        <a href="<?= SITE_URL ?>/admin/chatbot-log.php" class="btn btn-secondary">📊 Chất lượng Chatbot</a>
        <button class="btn btn-primary" onclick="cbOpenForm()">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="1" x2="8" y2="15"/><line x1="1" y1="8" x2="15" y2="8"/></svg>
          Thêm câu trả lời
        </button>
      </div>
    </div>
    <div class="adm-side-backdrop" onclick="document.querySelector('.adm-side').classList.remove('open');this.classList.remove('open')"></div>

    <div class="adm-content">
      <?php if ($msg): ?><div class="adm-alert adm-alert-ok" style="margin-bottom:16px">✓ <?= e($msg) ?></div><?php endif; ?>

      <p style="font-size:13px;color:var(--ink-3);margin:0 0 18px;line-height:1.6">
        Đây là "bộ não" của chatbot. Mỗi dòng = 1 nhóm từ khoá (cách nhau bởi dấu phẩy) + 1 câu trả lời.
        Khách chat gõ gì, chatbot so khớp với các từ khoá này để chọn câu trả lời sát nhất — không cần sửa code.
      </p>

      <div class="stats-grid stats-grid-3" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-icon si-blue">🤖</div><div><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Tổng số FAQ</div></div></div>
        <div class="stat-card"><div class="stat-icon si-green">✅</div><div><div class="stat-num"><?= $activeCount ?></div><div class="stat-lbl">Đang bật</div></div></div>
        <div class="stat-card"><div class="stat-icon si-red">⏸️</div><div><div class="stat-num"><?= $total - $activeCount ?></div><div class="stat-lbl">Đang tắt</div></div></div>
      </div>

      <!-- FORM (ẩn mặc định) -->
      <div class="form-card" id="cbFormWrap" style="display:none;margin-bottom:20px;max-width:640px">
        <div class="form-card-header">
          <span class="form-card-title" id="cbFTitle">➕ Thêm câu trả lời mới</span>
          <button type="button" onclick="cbResetF()" style="font-size:20px;background:none;border:none;cursor:pointer;color:var(--ink-4);line-height:1">×</button>
        </div>
        <form method="POST" id="cbForm">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" id="cbFId" value="0">
          <div class="fg">
            <label>Từ khoá (cách nhau bởi dấu phẩy) *</label>
            <input type="text" name="tu_khoa" id="cbFTuKhoa" required
                   placeholder="VD: đổi trả, hoàn tiền, key lỗi, key die, refund">
          </div>
          <div class="fg">
            <label>Câu trả lời *</label>
            <textarea name="tra_loi" id="cbFTraLoi" required rows="5"
              style="width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-family:inherit;resize:vertical;outline:none;color:var(--ink);background:var(--white)"
              placeholder="Nội dung chatbot sẽ trả lời khi khớp từ khoá trên..."></textarea>
          </div>
          <div class="fg" style="display:flex;align-items:center;gap:8px">
            <input type="checkbox" name="kich_hoat" id="cbFOn" checked style="width:16px;height:16px">
            <label style="margin:0" for="cbFOn">Kích hoạt (bật để chatbot dùng câu trả lời này)</label>
          </div>
          <div style="display:flex;gap:8px;margin-top:4px">
            <button type="submit" class="btn btn-primary" style="flex:1">💾 Lưu</button>
            <button type="button" onclick="cbResetF()" class="btn btn-secondary">Huỷ</button>
          </div>
        </form>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <form method="GET" style="display:contents">
            <div class="search-wrap">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6.5" cy="6.5" r="5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
              <input class="search-input" type="text" name="s" value="<?= e($s) ?>" placeholder="Tìm từ khoá hoặc câu trả lời...">
            </div>
            <button class="btn-search" type="submit">Tìm</button>
            <?php if ($s): ?><a href="?" style="font-size:12px;color:var(--ink-3);padding:8px 4px">✕</a><?php endif; ?>
          </form>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead><tr><th>#</th><th>Từ khoá</th><th>Câu trả lời</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
          <tbody>
          <?php if (empty($faqs)): ?>
            <tr><td colspan="5" class="table-empty"><span class="te-icon">🤖</span>Chưa có FAQ nào.</td></tr>
          <?php endif; ?>
          <?php foreach ($faqs as $f): ?>
          <tr style="<?= (int)$f['kich_hoat']===0 ? 'opacity:.55' : '' ?>">
            <td class="mono text-muted2"><?= $f['id'] ?></td>
            <td style="font-size:12.5px;max-width:220px;color:var(--ink-3)"><?= e($f['tu_khoa']) ?></td>
            <td style="font-size:13px;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e(mb_substr($f['tra_loi'], 0, 90)) ?><?= mb_strlen($f['tra_loi']) > 90 ? '...' : '' ?></td>
            <td>
              <form method="POST" style="display:contents">
          <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                <button type="submit" class="badge <?= (int)$f['kich_hoat']===1 ? 'b-green' : 'b-red' ?>" style="border:none;cursor:pointer">
                  <span class="badge-dot"></span><?= (int)$f['kich_hoat']===1 ? 'Đang bật' : 'Đang tắt' ?>
                </button>
              </form>
            </td>
            <td>
              <div class="act-row">
                <button class="act-btn ab-edit" title="Sửa"
                  onclick='cbEdit(<?= json_encode($f, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_TAG) ?>)'>✏️</button>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xoá FAQ này?')">
          <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $f['id'] ?>">
                  <button class="act-btn ab-del" type="submit" title="Xoá">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<script>
function cbOpenForm(){
  document.getElementById('cbFormWrap').style.display = 'block';
  document.getElementById('cbFormWrap').scrollIntoView({behavior:'smooth', block:'center'});
}
function cbResetF(){
  document.getElementById('cbForm').reset();
  document.getElementById('cbFId').value = '0';
  document.getElementById('cbFTitle').textContent = '➕ Thêm câu trả lời mới';
  document.getElementById('cbFormWrap').style.display = 'none';
}
function cbEdit(f){
  document.getElementById('cbFId').value = f.id;
  document.getElementById('cbFTuKhoa').value = f.tu_khoa;
  document.getElementById('cbFTraLoi').value = f.tra_loi;
  document.getElementById('cbFOn').checked = parseInt(f.kich_hoat) === 1;
  document.getElementById('cbFTitle').textContent = '✏️ Sửa câu trả lời #' + f.id;
  cbOpenForm();
}
</script>

</body>
</html>
