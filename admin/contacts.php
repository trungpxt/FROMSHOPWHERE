<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mail-config.php';
require_once __DIR__ . '/../includes/notify.php';
requireAdmin();

/* Tạo bảng nếu chưa có */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ho_ten VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        chu_de VARCHAR(200) NOT NULL,
        noi_dung TEXT NOT NULL,
        nguoi_dung_id INT DEFAULT NULL,
        trang_thai ENUM('chua_doc','da_doc','da_tra_loi') DEFAULT 'chua_doc',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

$msg = '';

/* Cập nhật trạng thái */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $a = $_POST['action'] ?? '';
    if ($a === 'status') {
        db()->prepare("UPDATE contact_messages SET trang_thai=:s WHERE id=:id")
           ->execute([':s'=>$_POST['status'],':id'=>(int)$_POST['id']]);
        $msg = 'Đã cập nhật trạng thái.';
    }
    if ($a === 'delete') {
        db()->prepare("DELETE FROM contact_messages WHERE id=:id")
           ->execute([':id'=>(int)$_POST['id']]);
        $msg = 'Đã xoá tin nhắn.';
    }
    if ($a === 'reply') {
        $id    = (int)$_POST['id'];
        $reply = trim($_POST['reply_text'] ?? '');
        $row   = db()->query("SELECT * FROM contact_messages WHERE id=$id")->fetch();
        if ($row && $reply) {
            try {
                $mail = createMailer();
                $mail->addAddress($row['email'], $row['ho_ten']);
                $mail->Subject = "Re: " . $row['chu_de'] . " — FROMSHOPWHERE";
                $mail->isHTML(true);
                $mail->Body = "
                <div style='font-family:sans-serif;max-width:560px;margin:0 auto;padding:24px'>
                  <div style='background:#16123F;padding:16px 24px;border-radius:12px 12px 0 0'>
                    <h2 style='color:#fff;margin:0;font-size:18px'>FROMSHOPWHERE Support</h2>
                  </div>
                  <div style='background:#fff;border:1px solid #e0e0e0;border-top:none;padding:28px 24px;border-radius:0 0 12px 12px'>
                    <p>Xin chào <strong>" . e($row['ho_ten']) . "</strong>,</p>
                    <div style='background:#f5f5f5;border-left:3px solid #3B2FA0;padding:14px 16px;border-radius:4px;font-size:14px;line-height:1.7;color:#333;margin-bottom:16px'>
                      " . nl2br(e($reply)) . "
                    </div>
                    <p style='color:#777;font-size:13px'>Câu hỏi gốc của bạn: <em>" . e(mb_substr($row['noi_dung'],0,150)) . "...</em></p>
                    <hr style='border:none;border-top:1px solid #eee;margin:16px 0'>
                    <p style='color:#999;font-size:12px'>© " . date('Y') . " FROMSHOPWHERE</p>
                  </div>
                </div>";
                $mail->send();
                db()->prepare("UPDATE contact_messages SET trang_thai='da_tra_loi' WHERE id=:id")
                   ->execute([':id'=>$id]);

                // Thông báo trong chuông cho khách (nếu tin nhắn gắn với tài khoản đã đăng nhập)
                if (!empty($row['nguoi_dung_id'])) {
                    createNotification(
                        (int)$row['nguoi_dung_id'],
                        'lien_he',
                        'Yêu cầu liên hệ của bạn đã được trả lời',
                        mb_substr($reply, 0, 150),
                        SITE_URL . '/contact.php'
                    );
                }

                $msg = "✅ Đã gửi email trả lời cho " . e($row['email']);
            } catch(Exception $ex) {
                $msg = "❌ Lỗi gửi email: " . $ex->getMessage();
            }
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$s      = trim($_GET['s'] ?? '');
$where  = []; $pp = [];
if ($filter !== 'all') { $where[] = "trang_thai=:ft"; $pp[':ft'] = $filter; }
if ($s) { $where[] = "(ho_ten LIKE :s OR email LIKE :s2 OR chu_de LIKE :s3)"; $pp[':s']="%$s%"; $pp[':s2']="%$s%"; $pp[':s3']="%$s%"; }
$wh = $where ? "WHERE ".implode(" AND ",$where) : "";
$stmt = db()->prepare("SELECT * FROM contact_messages $wh ORDER BY created_at DESC");
$stmt->execute($pp);
$contacts = $stmt->fetchAll();

$counts = db()->query("SELECT trang_thai,COUNT(*) c FROM contact_messages GROUP BY trang_thai")->fetchAll(PDO::FETCH_KEY_PAIR);
$total   = array_sum($counts);
$unread  = $counts['chua_doc'] ?? 0;
$replied = $counts['da_tra_loi'] ?? 0;

$viewMsg = null;
if (isset($_GET['view'])) {
    $viewMsg = db()->query("SELECT * FROM contact_messages WHERE id=".(int)$_GET['view'])->fetch();
    if ($viewMsg && $viewMsg['trang_thai'] === 'chua_doc') {
        db()->prepare("UPDATE contact_messages SET trang_thai='da_doc' WHERE id=:id")->execute([':id'=>$viewMsg['id']]);
        $viewMsg['trang_thai'] = 'da_doc';
    }
}
$admPageTitle = 'Tin nhắn — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel = 'Tin nhắn liên hệ';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <div class="adm-topbar">
      <button onclick="document.querySelector('.adm-side').classList.toggle('open');document.querySelector('.adm-side-backdrop').classList.toggle('open')" class="adm-hamburger" aria-label="Mở menu" title="Menu">☰</button>
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <strong>Tin nhắn liên hệ</strong>
        <?php if($unread > 0): ?>
          <span class="badge b-red" style="margin-left:8px"><span class="badge-dot"></span><?= $unread ?> chưa đọc</span>
        <?php endif; ?>
      </div>
      <div class="adm-topbar-right">
        <button onclick="toggleTheme()" class="adm-theme-btn" title="Đổi giao diện" id="admThemeBtn">☀️</button>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-secondary">🌐 Xem website</a>
      </div>
    </div>
    <div class="adm-side-backdrop" onclick="document.querySelector('.adm-side').classList.remove('open');this.classList.remove('open')"></div>

    <div class="adm-content">
      <?php if($msg): ?>
      <div class="adm-alert adm-alert-ok" style="margin-bottom:16px">✓ <?= $msg ?></div>
      <?php endif; ?>

      <!-- STATS -->
      <div class="stats-grid stats-grid-3" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-icon si-blue">📩</div><div><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Tổng tin nhắn</div></div></div>
        <div class="stat-card"><div class="stat-icon si-red">🔴</div><div><div class="stat-num"><?= $unread ?></div><div class="stat-lbl">Chưa đọc</div></div></div>
        <div class="stat-card"><div class="stat-icon si-green">✅</div><div><div class="stat-num"><?= $replied ?></div><div class="stat-lbl">Đã trả lời</div></div></div>
      </div>

      <?php if($viewMsg): ?>
      <!-- VIEW & REPLY MODAL -->
      <div class="table-card" style="margin-bottom:20px;padding:24px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
          <h3 style="font-size:16px;font-weight:800;margin:0">📩 Tin nhắn #<?= $viewMsg['id'] ?></h3>
          <a href="?" class="btn" style="font-size:13px;padding:8px 16px;border:1.5px solid var(--green);color:var(--green);background:transparent;transition:all .18s" onmouseover="this.style.background='var(--green)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--green)'">← Quay lại danh sách</a>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;font-size:13px">
          <div><span style="color:var(--ink-4);font-weight:600">Người gửi:</span> <strong><?= e($viewMsg['ho_ten']) ?></strong></div>
          <div><span style="color:var(--ink-4);font-weight:600">Email:</span> <a href="mailto:<?= e($viewMsg['email']) ?>" style="color:var(--green)"><?= e($viewMsg['email']) ?></a></div>
          <div><span style="color:var(--ink-4);font-weight:600">Chủ đề:</span> <?= e($viewMsg['chu_de']) ?></div>
          <div><span style="color:var(--ink-4);font-weight:600">Thời gian:</span> <?= date('H:i d/m/Y',strtotime($viewMsg['created_at'])) ?></div>
        </div>
        <div style="background:var(--bg);border-left:3px solid var(--green);padding:16px;border-radius:8px;font-size:14px;line-height:1.75;color:var(--ink-2);margin-bottom:20px;white-space:pre-wrap"><?= e($viewMsg['noi_dung']) ?></div>

        <!-- Reply form -->
        <h4 style="font-size:14px;font-weight:700;margin:0 0 12px">✉️ Trả lời qua email</h4>
        <form method="POST" id="replyForm" onsubmit="return submitReplyForm(this)">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="reply">
          <input type="hidden" name="id" value="<?= $viewMsg['id'] ?>">
          <textarea name="reply_text" required
            style="width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-family:inherit;min-height:100px;resize:vertical;outline:none;color:var(--ink);background:var(--white)"
            placeholder="Nội dung email trả lời..."></textarea>
          <div style="display:flex;gap:10px;margin-top:12px">
            <button type="submit" class="btn btn-primary" id="replySubmitBtn">📨 Gửi email trả lời</button>
            <a href="?" class="btn btn-secondary">Huỷ</a>
          </div>
        </form>
      </div>

      <!-- Màn hình chờ khi đang gửi email trả lời -->
      <div id="replyOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:16px">
        <div style="width:46px;height:46px;border-radius:50%;border:4px solid rgba(255,255,255,.15);border-top-color:var(--adm-lime,#c8ff00);animation:replySpin .8s linear infinite"></div>
        <div style="color:#fff;font-size:14px;font-weight:700">📨 Đang gửi email trả lời...</div>
        <div style="color:rgba(255,255,255,.6);font-size:12.5px">Vui lòng đợi trong giây lát</div>
      </div>
      <style>@keyframes replySpin{to{transform:rotate(360deg)}}</style>
      <script>
        function submitReplyForm(form){
          var btn = document.getElementById('replySubmitBtn');
          var overlay = document.getElementById('replyOverlay');
          if (btn.disabled) return false; // tránh bấm gửi 2 lần
          btn.disabled = true;
          btn.textContent = '⏳ Đang gửi...';
          if (overlay) overlay.style.display = 'flex';
          return true;
        }
      </script>
      <?php else: ?>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <form method="GET" style="display:contents">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <div class="search-wrap">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6.5" cy="6.5" r="5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
              <input class="search-input" type="text" name="s" value="<?= e($s) ?>" placeholder="Tìm tên, email, chủ đề...">
            </div>
            <button class="btn-search" type="submit">Tìm</button>
            <?php if($s): ?><a href="?filter=<?= $filter ?>" style="font-size:12px;color:var(--ink-3);padding:8px 4px">✕</a><?php endif; ?>
          </form>
        </div>
        <div class="filter-tabs">
          <a href="?s=<?= urlencode($s) ?>&filter=all" class="filter-tab <?= $filter==='all'?'active':'' ?>">Tất cả (<?= $total ?>)</a>
          <a href="?s=<?= urlencode($s) ?>&filter=chua_doc" class="filter-tab <?= $filter==='chua_doc'?'active':'' ?>">Chưa đọc (<?= $unread ?>)</a>
          <a href="?s=<?= urlencode($s) ?>&filter=da_doc" class="filter-tab <?= $filter==='da_doc'?'active':'' ?>">Đã đọc</a>
          <a href="?s=<?= urlencode($s) ?>&filter=da_tra_loi" class="filter-tab <?= $filter==='da_tra_loi'?'active':'' ?>">Đã trả lời (<?= $replied ?>)</a>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead><tr><th>#</th><th>Người gửi</th><th>Chủ đề</th><th>Nội dung</th><th>Trạng thái</th><th>Thời gian</th><th>Thao tác</th></tr></thead>
          <tbody>
          <?php if(empty($contacts)): ?>
            <tr><td colspan="7" class="table-empty"><span class="te-icon">📩</span>Chưa có tin nhắn nào.</td></tr>
          <?php endif; ?>
          <?php foreach($contacts as $ct):
            [$cls,$lbl] = match($ct['trang_thai']){
              'da_tra_loi'=>['b-green','Đã trả lời'],
              'da_doc'    =>['b-blue','Đã đọc'],
              default     =>['b-red','Chưa đọc'],
            };
            $isNew = $ct['trang_thai'] === 'chua_doc';
          ?>
          <tr style="<?= $isNew?'font-weight:600':'' ?>">
            <td class="mono text-muted2"><?= $ct['id'] ?></td>
            <td>
              <div style="font-weight:700;font-size:13px"><?= e($ct['ho_ten']) ?></div>
              <div class="mono text-muted2"><?= e($ct['email']) ?></div>
            </td>
            <td style="font-size:13px"><?= e($ct['chu_de']) ?></td>
            <td style="font-size:12.5px;color:var(--ink-3);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e(mb_substr($ct['noi_dung'],0,80)) ?>...</td>
            <td><span class="badge <?= $cls ?>"><span class="badge-dot"></span><?= $lbl ?></span></td>
            <td class="mono text-muted2"><?= date('d/m/Y H:i',strtotime($ct['created_at'])) ?></td>
            <td>
              <div class="act-row">
                <a href="?view=<?= $ct['id'] ?>" class="act-btn ab-view" title="Xem & trả lời">👁</a>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xoá tin nhắn này?')">
          <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $ct['id'] ?>">
                  <button class="act-btn ab-del" type="submit" title="Xoá">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

</body>
</html>
