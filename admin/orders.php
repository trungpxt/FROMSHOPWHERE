<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mail-config.php';
require_once __DIR__ . '/../includes/notify.php';
require_once __DIR__ . '/../includes/review-reminder-lib.php';
require_once __DIR__ . '/../includes/referral.php';
requireAdmin();

/* ── Hàm gửi email hoàn thành đơn hàng ── */
function sendOrderCompletedEmail(int $orderId): bool {
    try {
        // Lấy thông tin đơn hàng
        $order = db()->prepare("
            SELECT o.*, u.ho_ten, u.email
            FROM orders o
            JOIN users u ON u.id = o.nguoi_dung_id
            WHERE o.id = :id
        ");
        $order->execute([':id' => $orderId]);
        $order = $order->fetch();
        if (!$order) return false;

        // Lấy danh sách sản phẩm + license key
        $items = db()->prepare("
            SELECT oi.*, p.ten_san_pham, p.hinh_anh, p.phien_ban
            FROM order_items oi
            JOIN products p ON p.id = oi.san_pham_id
            WHERE oi.don_hang_id = :oid
        ");
        $items->execute([':oid' => $orderId]);
        $items = $items->fetchAll();
        if (empty($items)) return false;

        $mail = createMailer();
        $mail->addAddress($order['email'], $order['ho_ten']);
        $mail->Subject = "🎉 Đơn hàng #$orderId đã hoàn thành — License key của bạn";
        $mail->isHTML(true);

        /* Build items HTML */
        $itemsHtml = '';
        foreach ($items as $it) {
            $keyHtml = $it['license_key']
                ? "<div style='margin-top:10px'>
                     <div style='font-size:11px;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px'>License Key</div>
                     <div style='background:#f0fdf4;border:1.5px solid #86efac;border-radius:8px;padding:10px 14px;font-family:monospace;font-size:14px;font-weight:700;color:#15803d;letter-spacing:.06em;word-break:break-all'>{$it['license_key']}</div>
                   </div>"
                : "<div style='margin-top:8px;font-size:12px;color:#f59e0b'>⏳ License key sẽ được gửi trong vòng 15 phút</div>";

            $itemsHtml .= "
            <div style='border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin-bottom:12px'>
                <div style='display:flex;align-items:center;gap:12px'>
                    <div>
                        <div style='font-size:15px;font-weight:700;color:#111'>" . e($it['ten_san_pham']) . "</div>
                        " . ($it['phien_ban'] ? "<div style='font-size:12px;color:#888;margin-top:2px'>Phiên bản: " . e($it['phien_ban']) . "</div>" : '') . "
                        <div style='font-size:13px;font-weight:600;color:#3B2FA0;margin-top:4px'>" . fmtVND($it['don_gia']) . " × " . $it['so_luong'] . "</div>
                    </div>
                </div>
                $keyHtml
            </div>";
        }

        $mail->Body = "
        <div style='font-family:\"Be Vietnam Pro\",sans-serif;max-width:580px;margin:0 auto;background:#fff'>
          <!-- Header -->
          <div style='background:linear-gradient(135deg,#16123F,#3B2FA0);padding:28px 32px;border-radius:14px 14px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:22px;font-weight:800'>🎉 Đơn hàng hoàn thành!</h1>
            <p style='color:rgba(225,252,246,.7);font-size:13px;margin:8px 0 0'>Cảm ơn bạn đã tin tưởng FROMSHOPWHERE</p>
          </div>
          <!-- Body -->
          <div style='padding:28px 32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 14px 14px'>
            <p style='font-size:15px;color:#333;margin-top:0'>Xin chào <strong>" . e($order['ho_ten']) . "</strong>,</p>
            <p style='color:#555;font-size:14px;line-height:1.7'>Đơn hàng <strong>#$orderId</strong> của bạn đã được xác nhận thanh toán và hoàn thành. Dưới đây là thông tin license key của bạn:</p>

            <!-- Items -->
            $itemsHtml

            <!-- Order summary -->
            <div style='background:#f8fafb;border-radius:10px;padding:16px;margin-top:16px'>
              <div style='display:flex;justify-content:space-between;font-size:13px;color:#555;margin-bottom:6px'>
                <span>Mã đơn hàng</span><strong style='color:#111'>#$orderId</strong>
              </div>
              <div style='display:flex;justify-content:space-between;font-size:13px;color:#555;margin-bottom:6px'>
                <span>Phương thức thanh toán</span><span>" . e($order['phuong_thuc_tt']) . "</span>
              </div>
              " . ($order['ma_giam_gia'] ? "<div style='display:flex;justify-content:space-between;font-size:13px;color:#555;margin-bottom:6px'><span>Mã giảm giá</span><span style='color:#3B2FA0;font-weight:700'>" . e($order['ma_giam_gia']) . "</span></div>" : '') . "
              <div style='border-top:1px solid #e5e7eb;margin-top:8px;padding-top:10px;display:flex;justify-content:space-between'>
                <span style='font-weight:700;color:#111'>Tổng tiền</span>
                <span style='font-size:18px;font-weight:800;color:#3B2FA0'>" . fmtVND($order['tong_tien']) . "</span>
              </div>
            </div>

            <!-- CTA -->
            <div style='text-align:center;margin-top:22px'>
              <a href='" . SITE_URL . "/profile.php' style='background:#3B2FA0;color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700;display:inline-block'>
                📋 Xem lịch sử đơn hàng
              </a>
            </div>

            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'>
            <p style='font-size:13px;color:#888;margin:0;line-height:1.7'>
              Nếu bạn gặp bất kỳ vấn đề gì khi kích hoạt phần mềm, hãy liên hệ với chúng tôi qua
              <a href='" . SITE_URL . "/contact.php' style='color:#3B2FA0;font-weight:600'>trang hỗ trợ</a>
              hoặc email <a href='mailto:" . MAIL_FROM . "' style='color:#3B2FA0;font-weight:600'>" . MAIL_FROM . "</a>.
            </p>
            <p style='font-size:12px;color:#aaa;margin-top:16px'>© " . date('Y') . " FROMSHOPWHERE — Phần mềm bản quyền chính hãng</p>
          </div>
        </div>";

        $mail->send();
        return true;
    } catch(Exception $ex) {
        error_log('[OrderComplete] Email error: ' . $ex->getMessage());
        return false;
    }
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'update') {
    csrfCheck();
    $orderId    = (int)$_POST['id'];
    $newStatus  = $_POST['trang_thai'];

    // Lấy trạng thái cũ để check xem có phải lần đầu chuyển sang hoan_thanh không
    $oldStatus = db()->query("SELECT trang_thai FROM orders WHERE id=$orderId")->fetchColumn();

    db()->prepare("UPDATE orders SET trang_thai=:s WHERE id=:id")
       ->execute([':s'=>$newStatus,':id'=>$orderId]);

    $msg = 'Đã cập nhật trạng thái đơn hàng.';

    // Thông báo trong chuông cho khách khi đơn được xác nhận/hoàn thành
    if ($newStatus !== $oldStatus && in_array($newStatus, ['da_thanh_toan', 'hoan_thanh'], true)) {
        $buyerId = (int)db()->query("SELECT nguoi_dung_id FROM orders WHERE id=$orderId")->fetchColumn();
        $label   = $newStatus === 'hoan_thanh' ? 'đã hoàn thành, key đã được gửi qua email' : 'đã được xác nhận thanh toán';
        createNotification(
            $buyerId,
            'don_hang',
            'Đơn hàng #' . $orderId . ' ' . ($newStatus === 'hoan_thanh' ? 'đã hoàn thành' : 'đã được xác nhận'),
            'Đơn hàng #' . $orderId . ' của bạn ' . $label . '.',
            SITE_URL . '/profile.php'
        );
        referral_maybe_reward($buyerId);
    }

    // Gửi email khi vừa chuyển sang hoàn thành
    if ($newStatus === 'hoan_thanh' && $oldStatus !== 'hoan_thanh') {
        if (sendOrderCompletedEmail($orderId)) {
            $msg = '✅ Đơn hàng hoàn thành! Đã gửi email kèm license key cho khách hàng.';
        } else {
            $msg = '✅ Đã cập nhật hoàn thành. (Lưu ý: Gửi email thất bại — kiểm tra cấu hình SMTP)';
        }

        // Đặt lịch gửi email nhắc đánh giá sau REVIEW_REMINDER_DELAY_DAYS ngày
        // (cron/send-review-reminders.php sẽ gửi khi tới hạn)
        $buyerIdForReminder = (int)db()->query("SELECT nguoi_dung_id FROM orders WHERE id=$orderId")->fetchColumn();
        reviewReminderSchedule($orderId, $buyerIdForReminder);
    }

    // Nếu đơn bị chuyển ngược khỏi "hoàn thành" (vd. admin sửa nhầm) -> huỷ lịch nhắc chưa gửi
    if ($newStatus !== 'hoan_thanh' && $oldStatus === 'hoan_thanh') {
        reviewReminderCancel($orderId);
    }
}

/* ── Lưu license key cho 1 sản phẩm trong đơn (gọi qua AJAX, trả JSON) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_license_key') {
    csrfCheck();
    header('Content-Type: application/json; charset=utf-8');
    $itemId = (int)($_POST['item_id'] ?? 0);
    $key    = trim($_POST['license_key'] ?? '');

    if ($itemId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'Thiếu item_id.']);
        exit;
    }
    try {
        db()->prepare("UPDATE order_items SET license_key = ? WHERE id = ?")
            ->execute([$key === '' ? null : $key, $itemId]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'Lỗi khi lưu key.']);
    }
    exit;
}

/* ── Gửi lại email hoàn thành (dùng khi vừa bổ sung/sửa key sau khi đơn đã "hoàn thành") ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend_completed_email') {
    csrfCheck();
    header('Content-Type: application/json; charset=utf-8');
    $orderId = (int)($_POST['id'] ?? 0);
    $ok = $orderId > 0 && sendOrderCompletedEmail($orderId);
    echo json_encode(['ok' => $ok, 'error' => $ok ? null : 'Gửi email thất bại — kiểm tra cấu hình SMTP.']);
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$s      = trim($_GET['s'] ?? '');
$where  = '';
$params = [];
if ($filter !== 'all') { $where = "WHERE o.trang_thai=:ft"; $params[':ft']=$filter; }
if ($s) {
    $cond  = "u.ho_ten LIKE :s OR u.email LIKE :s2";
    $where = $where ? "$where AND ($cond)" : "WHERE $cond";
    $params[':s']="%$s%"; $params[':s2']="%$s%";
}
$stmt = db()->prepare("SELECT o.*,u.ho_ten,u.email,(SELECT COUNT(*) FROM order_items WHERE don_hang_id=o.id) sp FROM orders o JOIN users u ON u.id=o.nguoi_dung_id $where ORDER BY o.ngay_dat DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Lấy tất cả order_items + sản phẩm cho các đơn hiện tại
$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $ph = implode(',', array_fill(0, count($orderIds), '?'));
    $iStmt = db()->prepare("
        SELECT oi.*, p.ten_san_pham, p.hinh_anh, p.phien_ban, c.ten_danh_muc
        FROM order_items oi
        JOIN products p ON p.id = oi.san_pham_id
        LEFT JOIN categories c ON c.id = p.danh_muc_id
        WHERE oi.don_hang_id IN ($ph)
        ORDER BY oi.id ASC
    ");
    $iStmt->execute($orderIds);
    foreach ($iStmt->fetchAll() as $row) {
        $orderItems[$row['don_hang_id']][] = $row;
    }
}

$counts = db()->query("SELECT trang_thai,COUNT(*) c FROM orders GROUP BY trang_thai")->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);
$admPageTitle = 'Đơn hàng — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel = 'Đơn hàng';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <?php include __DIR__ . '/../includes/admin-topbar.php'; ?>
    <div class="adm-content">
      <?php if($msg): ?><div class="adm-alert adm-alert-ok" style="margin-bottom:16px">✓ <?= e($msg) ?></div><?php endif; ?>

      <!-- STATS -->
      <div class="stats-grid stats-grid-4" style="margin-bottom:20px">
        <?php
        $statDefs = [
          ['Tất cả','🛒','si-blue',$total],
          ['Chờ xử lý','⏳','si-yellow',$counts['cho_xu_ly']??0],
          ['Đã thanh toán','✅','si-green',$counts['da_thanh_toan']??0],
          ['Hoàn thành','🎉','si-purple',$counts['hoan_thanh']??0],
        ];
        foreach($statDefs as [$lbl,$ico,$cls,$cnt]): ?>
        <div class="stat-card">
          <div class="stat-icon <?= $cls ?>"><?= $ico ?></div>
          <div><div class="stat-num"><?= $cnt ?></div><div class="stat-lbl"><?= $lbl ?></div></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Info banner -->
      <div style="background:rgba(240,73,35,.06);border:1px solid rgba(240,73,35,.15);border-radius:12px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--ink-2);display:flex;align-items:center;gap:10px">
        <span style="font-size:18px">📧</span>
        <span>Khi bạn chuyển trạng thái đơn sang <strong>🎉 Hoàn thành</strong>, hệ thống sẽ <strong>tự động gửi email kèm license key</strong> đến khách hàng.</span>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <form method="GET" style="display:contents">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <div class="search-wrap">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6.5" cy="6.5" r="5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
              <input class="search-input" type="text" name="s" value="<?= e($s) ?>" placeholder="Tìm khách hàng, email...">
            </div>
            <button class="btn-search" type="submit">Tìm</button>
            <?php if($s): ?><a href="?filter=<?= $filter ?>" style="font-size:12px;color:var(--ink-3);padding:8px 4px">✕</a><?php endif; ?>
          </form>
        </div>
        <div class="filter-tabs">
          <?php
          $tabs = ['all'=>"Tất cả ($total)",'cho_xu_ly'=>'Chờ xử lý','da_thanh_toan'=>'Đã TT','hoan_thanh'=>'Hoàn thành','huy'=>'Huỷ'];
          foreach($tabs as $k=>$v): ?>
          <a href="?filter=<?= $k ?>&s=<?= urlencode($s) ?>" class="filter-tab <?= $filter===$k?'active':'' ?>"><?= $v ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead><tr><th>#</th><th>Khách hàng</th><th>Sản phẩm đã mua</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th><th style="width:38px"></th></tr></thead>
          <tbody>
          <?php if(empty($orders)): ?>
            <tr><td colspan="7" class="table-empty"><span class="te-icon">🛒</span>Chưa có đơn hàng nào.</td></tr>
          <?php endif; ?>
          <?php foreach($orders as $o):
            [$cls,$lbl] = match($o['trang_thai']){
              'da_thanh_toan'=>['b-green','Đã TT'],
              'hoan_thanh'  =>['b-blue','Hoàn thành'],
              'huy'         =>['b-red','Huỷ'],
              default       =>['b-yellow','Chờ xử lý']
            };
          ?>
          <tr>
            <td class="mono text-muted2 fw-bold">#<?= $o['id'] ?></td>
            <td>
              <div class="order-name"><?= e($o['ho_ten']) ?></div>
              <div class="order-email"><?= e($o['email']) ?></div>
            </td>
            <td>
              <?php
              $items = $orderItems[$o['id']] ?? [];
              if (!empty($items)):
                foreach (array_slice($items,0,2) as $it): ?>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                  <?php if (!empty($it['hinh_anh'])): ?>
                  <img src="<?= SITE_URL ?>/images/<?= e($it['hinh_anh']) ?>" style="width:28px;height:28px;object-fit:cover;border-radius:5px;flex-shrink:0" onerror="this.style.display='none'">
                  <?php endif; ?>
                  <span style="font-size:12px;color:var(--text);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px"><?= e($it['ten_san_pham']) ?></span>
                  <?php if ($it['so_luong']>1): ?><span style="font-size:11px;color:var(--text-muted)">×<?= $it['so_luong'] ?></span><?php endif; ?>
                </div>
                <?php endforeach;
                if (count($items)>2): ?>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">+<?= count($items)-2 ?> sản phẩm khác</div>
                <?php endif;
              else: ?>
                <span style="font-size:12px;color:var(--text-muted)"><?= $o['sp'] ?> SP</span>
              <?php endif; ?>
            </td>
            <td style="font-weight:800;color:var(--green-2)"><?= fmtVND($o['tong_tien']) ?></td>
            <td style="font-size:12px;color:var(--ink-4)"><?= e($o['phuong_thuc_tt']??'—') ?></td>
            <td>
              <form method="POST" onchange="submitStatusForm(this)">
          <?= csrfField() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                <select name="trang_thai" class="ss">
                  <?php foreach(['cho_xu_ly'=>'⏳ Chờ xử lý','da_thanh_toan'=>'✅ Đã TT','hoan_thanh'=>'🎉 Hoàn thành','huy'=>'❌ Huỷ'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= $o['trang_thai']===$v?'selected':'' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td class="mono text-muted2"><?= date('d/m/Y H:i',strtotime($o['ngay_dat'])) ?></td>
            <td style="width:38px;text-align:center;padding-left:6px;padding-right:12px">
              <button class="act-btn ab-view" title="Xem chi tiết đơn hàng"
                onclick="openOrderDetail(<?= $o['id'] ?>)"
                style="width:24px;height:24px;border-radius:6px;font-size:12px">👁</button>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<!-- Order Detail Sidebar -->
<div id="odBackdrop" class="od-backdrop" onclick="closeOrderDetail()"></div>

<!-- Confirm modal (thay window.confirm) -->
<div id="admConfirmOverlay" class="adm-confirm-overlay">
  <div class="adm-confirm-panel">
    <div class="adm-confirm-icon">🎉</div>
    <div class="adm-confirm-title" id="admConfirmTitle">Xác nhận hoàn thành đơn hàng?</div>
    <div class="adm-confirm-msg" id="admConfirmMsg">Hệ thống sẽ tự động gửi email kèm license key cho khách hàng.</div>
    <div class="adm-confirm-actions">
      <button type="button" class="adm-confirm-cancel" onclick="admConfirmResolve(false)">Huỷ</button>
      <button type="button" class="adm-confirm-ok" onclick="admConfirmResolve(true)">Xác nhận</button>
    </div>
  </div>
</div>

<!-- Loading overlay khi đang xử lý (gửi email có thể mất vài giây) -->
<div id="admLoadingOverlay" class="adm-loading-overlay">
  <div class="adm-spinner"></div>
  <div class="adm-loading-text" id="admLoadingText">Đang xử lý…</div>
</div>

<div id="odPanel" class="od-panel" style="top:0;right:0;width:min(460px,100vw);height:100vh;background:var(--card-bg);border-left:1.5px solid var(--border);z-index:401;flex-direction:column;overflow:hidden;box-shadow:-8px 0 40px rgba(0,0,0,.12)">
  <!-- Header -->
  <div style="padding:18px 20px;border-bottom:1.5px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:var(--card-bg)">
    <div>
      <div style="font-size:16px;font-weight:800;color:var(--text)" id="odTitle">Chi tiết đơn hàng</div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:2px" id="odDate"></div>
    </div>
    <button onclick="closeOrderDetail()" style="width:32px;height:32px;border:1px solid var(--border);border-radius:8px;background:none;cursor:pointer;font-size:18px;color:var(--text-muted);display:flex;align-items:center;justify-content:center;transition:all .15s"
      onmouseover="this.style.background='var(--bg-alt)'" onmouseout="this.style.background='none'">✕</button>
  </div>

  <div style="flex:1;overflow-y:auto;padding:0">
    <!-- Sản phẩm đã mua -->
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
      <div style="font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px">📦 Sản phẩm đã mua</div>
      <div id="odItems"></div>
    </div>

    <!-- Thông tin đơn hàng -->
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
      <div style="font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px">📋 Thông tin đơn hàng</div>
      <div id="odInfo"></div>
    </div>

    <!-- Tổng tiền -->
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:15px;font-weight:700;color:var(--text)">Tổng cộng</span>
        <span style="font-size:20px;font-weight:800;color:var(--teal-700)" id="odTotal"></span>
      </div>
    </div>

    <!-- Đổi trạng thái nhanh -->
    <div style="padding:16px 20px">
      <div style="font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--text-muted);margin-bottom:12px">⚡ Đổi trạng thái nhanh</div>
      <form method="POST" id="odStatusForm" onsubmit="return submitStatusForm(this)">
          <?= csrfField() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="odOrderId">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <?php foreach(['cho_xu_ly'=>['⏳','Chờ xử lý','#f59e0b'],'da_thanh_toan'=>['✅','Đã TT','#10b981'],'hoan_thanh'=>['🎉','Hoàn thành','#3b82f6'],'huy'=>['❌','Huỷ','#ef4444']] as $v=>[$ico,$lbl,$color]): ?>
          <button type="button" class="od-status-btn" data-value="<?= $v ?>"
            onclick="setOrderStatus('<?= $v ?>')"
            style="padding:10px;border:1.5px solid var(--border);border-radius:10px;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--text);transition:all .15s;text-align:left">
            <?= $ico ?> <?= $lbl ?>
          </button>
          <?php endforeach; ?>
        </div>
        <button type="submit" id="odSaveBtn" style="margin-top:12px;width:100%;padding:12px;background:var(--teal-700);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:background .2s"
          onmouseover="this.style.background='var(--teal-500)'" onmouseout="this.style.background='var(--teal-700)'">
          💾 Lưu trạng thái
        </button>
      </form>
      <button type="button" id="odResendBtn" onclick="resendCompletedEmail(currentOrderId, this)"
        style="display:none;margin-top:10px;width:100%;padding:11px;background:none;border:1.5px solid var(--border);color:var(--text);border-radius:10px;font-size:13px;font-weight:700;cursor:pointer">
        📧 Gửi lại email kèm key cho khách
      </button>
    </div>
  </div>
</div>

<script>
// Order items data từ PHP
const ORDER_ITEMS_DATA = <?= json_encode($orderItems, JSON_UNESCAPED_UNICODE) ?>;
const ORDERS_DATA = <?= json_encode(array_map(fn($o) => [
  'id'           => $o['id'],
  'ho_ten'       => $o['ho_ten'],
  'email'        => $o['email'],
  'tong_tien'    => $o['tong_tien'],
  'trang_thai'   => $o['trang_thai'],
  'phuong_thuc_tt'=> $o['phuong_thuc_tt'] ?? '',
  'ma_giam_gia'  => $o['ma_giam_gia'] ?? '',
  'ghi_chu'      => $o['ghi_chu'] ?? '',
  'ngay_dat'     => $o['ngay_dat'],
], $orders), JSON_UNESCAPED_UNICODE) ?>;
const SITE_URL_JS = "<?= SITE_URL ?>";

let currentOrderId = null;
let currentStatus  = null;

function openOrderDetail(orderId) {
  const order = ORDERS_DATA.find(o => +o.id === +orderId);
  if (!order) return;
  currentOrderId = orderId;
  currentStatus  = order.trang_thai;

  // Title
  document.getElementById('odTitle').textContent = 'Đơn hàng #' + orderId;
  document.getElementById('odDate').textContent  = new Date(order.ngay_dat).toLocaleString('vi-VN');
  document.getElementById('odOrderId').value     = orderId;
  document.getElementById('odTotal').textContent = Number(order.tong_tien).toLocaleString('vi-VN') + 'đ';

  // Items
  const items = ORDER_ITEMS_DATA[orderId] || [];
  const itemsEl = document.getElementById('odItems');
  if (items.length === 0) {
    itemsEl.innerHTML = '<div style="font-size:13px;color:var(--text-muted);padding:8px 0">Không có sản phẩm</div>';
  } else {
    itemsEl.innerHTML = items.map(it => {
      const imgUrl = it.hinh_anh ? `${SITE_URL_JS}/images/${it.hinh_anh}` : '';
      const keyHtml = `
        <div class="lk-box" data-item-id="${it.id}" style="margin-top:10px;background:rgba(240,73,35,.06);border:1.5px solid rgba(240,73,35,.2);border-radius:8px;padding:10px 12px">
          <div style="font-size:10px;font-weight:800;color:var(--teal-700);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">🔑 License Key</div>
          <div style="display:flex;gap:6px">
            <input type="text" class="lk-input" value="${it.license_key ? it.license_key.replace(/"/g,'&quot;') : ''}"
                   placeholder="Dán license key vào đây..."
                   style="flex:1;min-width:0;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-family:monospace;font-size:12.5px;background:var(--bg-card);color:var(--text)">
            <button type="button" class="btn btn-secondary lk-save-btn" style="padding:7px 12px;font-size:12px;white-space:nowrap" onclick="saveLicenseKey(${it.id}, this)">💾 Lưu</button>
          </div>
          <div class="lk-status" style="font-size:11px;margin-top:4px;min-height:14px">${it.license_key ? '<span style=\"color:#16a34a\">✓ Đã có key</span>' : '<span style=\"color:#f59e0b\">⏳ Chưa có license key</span>'}</div>
        </div>`;

      return `<div style="display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);align-items:flex-start">
        ${imgUrl ? `<img src="${imgUrl}" style="width:52px;height:52px;object-fit:cover;border-radius:8px;flex-shrink:0;border:1px solid var(--border)" onerror="this.style.display='none'">` : '<div style="width:52px;height:52px;background:var(--bg-alt);border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:22px">📦</div>'}
        <div style="flex:1;min-width:0">
          <div style="font-size:14px;font-weight:700;color:var(--text)">${it.ten_san_pham}</div>
          ${it.phien_ban ? `<div style="font-size:11px;color:var(--text-muted);margin-top:2px">v${it.phien_ban}</div>` : ''}
          <div style="font-size:13px;font-weight:700;color:var(--teal-700);margin-top:4px">${Number(it.don_gia).toLocaleString('vi-VN')}đ × ${it.so_luong}</div>
          ${keyHtml}
        </div>
      </div>`;
    }).join('');
  }

  // Order info
  const infoRows = [
    ['Khách hàng', order.ho_ten],
    ['Email', `<a href="mailto:${order.email}" style="color:var(--teal-700)">${order.email}</a>`],
    ['Thanh toán', order.phuong_thuc_tt || '—'],
    ['Mã giảm giá', order.ma_giam_gia || '—'],
    ['Ghi chú', order.ghi_chu || '—'],
  ];
  document.getElementById('odInfo').innerHTML = infoRows.map(([k,v]) =>
    `<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:7px 0;border-bottom:1px solid var(--border);gap:12px">
      <span style="font-size:13px;color:var(--text-muted);flex-shrink:0">${k}</span>
      <span style="font-size:13px;font-weight:600;color:var(--text);text-align:right">${v}</span>
    </div>`
  ).join('');

  // Status buttons
  updateStatusButtons(order.trang_thai);
  document.getElementById('odResendBtn').style.display = (order.trang_thai === 'hoan_thanh') ? 'block' : 'none';

  // Show panel
  document.getElementById('odBackdrop').classList.add('show');
  document.getElementById('odPanel').classList.add('show');
}

function closeOrderDetail() {
  document.getElementById('odBackdrop').classList.remove('show');
  document.getElementById('odPanel').classList.remove('show');
  currentOrderId = null;
}

function saveLicenseKey(itemId, btnEl) {
  const box    = document.querySelector('.lk-box[data-item-id="' + itemId + '"]');
  const input  = box.querySelector('.lk-input');
  const status = box.querySelector('.lk-status');
  const key    = input.value.trim();

  btnEl.disabled = true;
  const oldLabel = btnEl.textContent;
  btnEl.textContent = '...';

  const fd = new FormData();
  fd.append('action', 'save_license_key');
  fd.append('item_id', itemId);
  fd.append('license_key', key);

  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      btnEl.disabled = false;
      btnEl.textContent = oldLabel;
      if (data.ok) {
        status.innerHTML = key
          ? '<span style="color:#16a34a">✓ Đã lưu key</span>'
          : '<span style="color:#f59e0b">⏳ Chưa có license key</span>';
        // Cập nhật cache local để lần mở lại modal hiển thị đúng
        const items = ORDER_ITEMS_DATA[currentOrderId] || [];
        const it = items.find(x => +x.id === +itemId);
        if (it) it.license_key = key || null;
      } else {
        status.innerHTML = '<span style="color:#ef4444">✕ ' + (data.error || 'Lỗi khi lưu') + '</span>';
      }
    })
    .catch(() => {
      btnEl.disabled = false;
      btnEl.textContent = oldLabel;
      status.innerHTML = '<span style="color:#ef4444">✕ Lỗi kết nối</span>';
    });
}

function resendCompletedEmail(orderId, btnEl) {
  btnEl.disabled = true;
  const oldLabel = btnEl.textContent;
  btnEl.textContent = 'Đang gửi...';

  const fd = new FormData();
  fd.append('action', 'resend_completed_email');
  fd.append('id', orderId);

  fetch(location.href, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      btnEl.disabled = false;
      btnEl.textContent = oldLabel;
      alert(data.ok ? '✅ Đã gửi lại email kèm license key cho khách.' : '❌ ' + (data.error || 'Gửi email thất bại.'));
    })
    .catch(() => {
      btnEl.disabled = false;
      btnEl.textContent = oldLabel;
      alert('❌ Lỗi kết nối, thử lại sau.');
    });
}

function setOrderStatus(val) {
  currentStatus = val;
  updateStatusButtons(val);
}

function updateStatusButtons(activeVal) {
  const colorMap = {
    'cho_xu_ly':   '#f59e0b',
    'da_thanh_toan': '#10b981',
    'hoan_thanh':  '#3b82f6',
    'huy':         '#ef4444',
  };
  document.querySelectorAll('.od-status-btn').forEach(btn => {
    const v = btn.dataset.value;
    if (v === activeVal) {
      btn.style.background    = colorMap[v] || 'var(--teal-700)';
      btn.style.color         = '#fff';
      btn.style.borderColor   = colorMap[v] || 'var(--teal-700)';
    } else {
      btn.style.background    = 'none';
      btn.style.color         = 'var(--text)';
      btn.style.borderColor   = 'var(--border)';
    }
  });
  // Đặt value vào hidden input khi submit
  document.getElementById('odOrderId').value = currentOrderId;
  // Thêm input hidden trang_thai nếu chưa có
  let statusInput = document.getElementById('odStatusInput');
  if (!statusInput) {
    statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'trang_thai';
    statusInput.id   = 'odStatusInput';
    document.getElementById('odStatusForm').appendChild(statusInput);
  }
  statusInput.value = activeVal;
}

// Close on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOrderDetail(); });
</script>

<script src="<?= SITE_URL ?>/assets/js/admin-orders.js"></script>
</body>
</html>
