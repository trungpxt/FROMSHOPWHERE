<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
require_once __DIR__ . '/../includes/coupon-lib.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
  $a = $_POST['action'] ?? '';

  // Tạo mã giảm giá thủ công (admin tự đặt % và số ngày hết hạn, có thể gán riêng cho 1 tài khoản qua email)
  if ($a === 'create_manual') {
    $percent  = max(1, min(90, (int)($_POST['phan_tram_giam'] ?? 10)));
    $days     = max(1, (int)($_POST['so_ngay_het_han'] ?? 7));
    $emailFor = trim($_POST['email_rieng'] ?? '');
    $uid = null;
    if ($emailFor !== '') {
      $us = db()->prepare("SELECT id FROM users WHERE email=:e");
      $us->execute([':e' => $emailFor]);
      $uid = $us->fetchColumn() ?: null;
      if (!$uid) { $msg = '⚠ Không tìm thấy tài khoản với email này — mã đã được tạo dạng công khai (ai dùng trước). '; }
    }
    try {
      $res = coupon_create($uid, 'manual', $days, $percent);
      $msg .= 'Đã tạo mã ' . $res['code'] . ' (-' . $res['percent'] . '%, hết hạn sau ' . $days . ' ngày).';
    } catch (Exception $e) {
      $msg = '⚠ Tạo mã thất bại, vui lòng thử lại.';
    }
  }

  // Xoá 1 mã giảm giá (chỉ nên xoá mã chưa dùng)
  if ($a === 'delete') {
    db()->prepare("DELETE FROM coupons WHERE id=:id")->execute([':id' => (int)($_POST['id'] ?? 0)]);
    $msg = 'Đã xoá mã giảm giá.';
  }
}

try {
  $total_coupons  = (int)db()->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
  $used_coupons   = (int)db()->query("SELECT COUNT(*) FROM coupons WHERE da_su_dung=1")->fetchColumn();
  $active_coupons = (int)db()->query("SELECT COUNT(*) FROM coupons WHERE da_su_dung=0 AND ngay_het_han >= NOW()")->fetchColumn();
  $last_email_run = coupon_last_cron_run();
  $recent_coupons = db()->query("
      SELECT c.*, u.ho_ten, u.email
      FROM coupons c
      LEFT JOIN users u ON u.id = c.nguoi_dung_id
      ORDER BY c.ngay_tao DESC
      LIMIT 30
  ")->fetchAll();
  $total_users = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (Exception $e) {
  $total_coupons=0; $used_coupons=0; $active_coupons=0; $last_email_run=null; $recent_coupons=[]; $total_users=0;
}

$admPageTitle = 'Mã giảm giá — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel = 'Mã giảm giá';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <?php include __DIR__ . '/../includes/admin-topbar.php'; ?>

    <div class="adm-content">

      <?php if ($msg): ?><div class="adm-alert adm-alert-ok" style="margin-bottom:22px">✓ <?= e($msg) ?></div><?php endif; ?>

      <div class="adm-welcome" style="margin-bottom:22px">
        <h1>🎁 Mã giảm giá tự động</h1>
        <p>Popup phát mã ngẫu nhiên (5–20%) khi khách vào web (1 lần/phiên) + email định kỳ mỗi ~4 tiếng cho <?= $total_users ?> tài khoản đã đăng ký.</p>
      </div>

      <!-- TẠO MÃ THỦ CÔNG -->
      <div class="table-card" style="padding:18px 20px;margin-bottom:24px">
        <div style="font-size:14px;font-weight:800;margin-bottom:12px">➕ Tạo mã giảm giá thủ công</div>
        <form method="POST" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="create_manual">
          <div>
            <label style="display:block;font-size:12px;color:var(--b-muted);margin-bottom:4px">% Giảm giá</label>
            <input type="number" name="phan_tram_giam" min="1" max="90" value="10" required style="width:100px;padding:9px 10px;border:1.5px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text)">
          </div>
          <div>
            <label style="display:block;font-size:12px;color:var(--b-muted);margin-bottom:4px">Số ngày hết hạn</label>
            <input type="number" name="so_ngay_het_han" min="1" value="7" required style="width:110px;padding:9px 10px;border:1.5px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text)">
          </div>
          <div>
            <label style="display:block;font-size:12px;color:var(--b-muted);margin-bottom:4px">Email riêng (không bắt buộc)</label>
            <input type="email" name="email_rieng" placeholder="Để trống = mã công khai" style="width:230px;padding:9px 10px;border:1.5px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text)">
          </div>
          <button type="submit" class="btn btn-primary">Tạo mã</button>
        </form>
      </div>

      <!-- STATS -->
      <div class="stats-grid stats-grid-4" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-icon si-green">🎁</div>
          <div>
            <div class="stat-num"><?= $total_coupons ?></div>
            <div class="stat-lbl">Tổng mã đã tạo</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-blue">✅</div>
          <div>
            <div class="stat-num"><?= $active_coupons ?></div>
            <div class="stat-lbl">Đang còn hiệu lực</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-purple">🛒</div>
          <div>
            <div class="stat-num"><?= $used_coupons ?></div>
            <div class="stat-lbl">Đã sử dụng</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-yellow">📧</div>
          <div>
            <div class="stat-num" style="font-size:14px"><?= $last_email_run ? date('d/m H:i', strtotime($last_email_run)) : 'Chưa gửi lần nào' ?></div>
            <div class="stat-lbl">Lần gửi email gần nhất</div>
          </div>
        </div>
      </div>

      <!-- SEND TEST -->
      <div class="table-card" style="padding:18px 20px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
          <div style="font-size:14px;font-weight:800;margin-bottom:4px">📧 Gửi email mã giảm giá ngay</div>
          <div style="font-size:12.5px;color:var(--b-muted)">Bấm để gửi thật ngay lập tức cho tất cả <?= $total_users ?> tài khoản — không cần chờ đủ 4 tiếng như cron. Dùng để test.</div>
        </div>
        <button type="button" class="btn btn-primary" id="sendCouponTestBtn" onclick="sendCouponTest()">🚀 Gửi ngay để test</button>
      </div>

      <!-- LIST -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <span style="font-size:14px;font-weight:800">🎟️ 30 mã gần nhất</span>
      </div>
      <div class="table-card">
        <table>
          <thead>
            <tr><th>Mã</th><th>Giảm</th><th>Nguồn</th><th>Người nhận</th><th>Trạng thái</th><th>Hết hạn</th><th>Thao tác</th></tr>
          </thead>
          <tbody>
          <?php if (empty($recent_coupons)): ?>
            <tr><td colspan="7" class="table-empty"><span class="te-icon">🎁</span>Chưa có mã nào được tạo. Popup/email sẽ tự tạo khi có khách vào web hoặc email chạy.</td></tr>
          <?php endif; ?>
          <?php foreach ($recent_coupons as $c):
            $expired = strtotime($c['ngay_het_han']) < time();
            if ($c['da_su_dung']) { $cls='b-blue'; $lbl='Đã dùng'; }
            elseif ($expired) { $cls='b-red'; $lbl='Hết hạn'; }
            else { $cls='b-green'; $lbl='Còn hiệu lực'; }
            $nguonLbl = match($c['nguon']) {
              'popup' => '🖥️ Popup web',
              'email' => '📧 Email định kỳ',
              'admin_test' => '🧪 Admin test',
              default => 'Thủ công',
            };
          ?>
          <tr>
            <td class="mono" style="font-weight:700"><?= e($c['ma_code']) ?></td>
            <td><?= (int)$c['phan_tram_giam'] ?>%</td>
            <td class="text-muted2"><?= $nguonLbl ?></td>
            <td>
              <?php if ($c['ho_ten']): ?>
                <div style="font-size:13px"><?= e($c['ho_ten']) ?></div>
                <div class="mono text-muted2" style="font-size:11px"><?= e($c['email']) ?></div>
              <?php else: ?>
                <span class="text-muted2">Công khai (ai dùng trước)</span>
              <?php endif; ?>
            </td>
            <td><span class="badge <?= $cls ?>"><span class="badge-dot"></span><?= $lbl ?></span></td>
            <td class="text-muted2 mono"><?= date('d/m/Y H:i', strtotime($c['ngay_het_han'])) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Xoá mã «<?= e($c['ma_code']) ?>»?')">
          <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="act-btn ab-del" title="Xoá">🗑</button>
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

<script>
async function sendCouponTest() {
  const btn = document.getElementById('sendCouponTestBtn');
  if (!confirm('Gửi email mã giảm giá thật ngay bây giờ cho tất cả <?= $total_users ?> tài khoản?')) return;
  btn.disabled = true;
  btn.textContent = '⏳ Đang gửi...';
  try {
    const res = await fetch('<?= SITE_URL ?>/api/admin-send-coupon-test.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': '<?= csrfToken() ?>' }
    });
    const data = await res.json();
    if (data.ok) {
      alert('✓ Đã gửi cho ' + data.sent + '/' + data.total + ' tài khoản' + (data.failed > 0 ? ' (' + data.failed + ' gửi lỗi)' : '') + '.');
      location.reload();
    } else {
      alert('⚠ ' + (data.error || 'Gửi thất bại'));
    }
  } catch (e) {
    alert('⚠ Lỗi kết nối, thử lại sau');
  } finally {
    btn.disabled = false;
    btn.textContent = '🚀 Gửi ngay để test';
  }
}
</script>
</body>
</html>
