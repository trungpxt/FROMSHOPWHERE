<?php
require_once __DIR__ . '/../config.php';
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

        require_once __DIR__ . '/../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_FROM;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
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
                        <div style='font-size:13px;font-weight:600;color:#0F6E56;margin-top:4px'>" . fmtVND($it['don_gia']) . " × " . $it['so_luong'] . "</div>
                    </div>
                </div>
                $keyHtml
            </div>";
        }

        $mail->Body = "
        <div style='font-family:\"Plus Jakarta Sans\",sans-serif;max-width:580px;margin:0 auto;background:#fff'>
          <!-- Header -->
          <div style='background:linear-gradient(135deg,#04342C,#0F6E56);padding:28px 32px;border-radius:14px 14px 0 0;text-align:center'>
            <h1 style='color:#E1FCF6;margin:0;font-size:22px;font-weight:800'>🎉 Đơn hàng hoàn thành!</h1>
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
              " . ($order['ma_giam_gia'] ? "<div style='display:flex;justify-content:space-between;font-size:13px;color:#555;margin-bottom:6px'><span>Mã giảm giá</span><span style='color:#0F6E56;font-weight:700'>" . e($order['ma_giam_gia']) . "</span></div>" : '') . "
              <div style='border-top:1px solid #e5e7eb;margin-top:8px;padding-top:10px;display:flex;justify-content:space-between'>
                <span style='font-weight:700;color:#111'>Tổng tiền</span>
                <span style='font-size:18px;font-weight:800;color:#0F6E56'>" . fmtVND($order['tong_tien']) . "</span>
              </div>
            </div>

            <!-- CTA -->
            <div style='text-align:center;margin-top:22px'>
              <a href='" . SITE_URL . "/profile.php' style='background:#0F6E56;color:#E1FCF6;padding:12px 28px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700;display:inline-block'>
                📋 Xem lịch sử đơn hàng
              </a>
            </div>

            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'>
            <p style='font-size:13px;color:#888;margin:0;line-height:1.7'>
              Nếu bạn gặp bất kỳ vấn đề gì khi kích hoạt phần mềm, hãy liên hệ với chúng tôi qua
              <a href='" . SITE_URL . "/contact.php' style='color:#0F6E56;font-weight:600'>trang hỗ trợ</a>
              hoặc email <a href='mailto:" . MAIL_FROM . "' style='color:#0F6E56;font-weight:600'>" . MAIL_FROM . "</a>.
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
    $orderId    = (int)$_POST['id'];
    $newStatus  = $_POST['trang_thai'];

    // Lấy trạng thái cũ để check xem có phải lần đầu chuyển sang hoan_thanh không
    $oldStatus = db()->query("SELECT trang_thai FROM orders WHERE id=$orderId")->fetchColumn();

    db()->prepare("UPDATE orders SET trang_thai=:s WHERE id=:id")
       ->execute([':s'=>$newStatus,':id'=>$orderId]);

    $msg = 'Đã cập nhật trạng thái đơn hàng.';

    // Gửi email khi vừa chuyển sang hoàn thành
    if ($newStatus === 'hoan_thanh' && $oldStatus !== 'hoan_thanh') {
        if (sendOrderCompletedEmail($orderId)) {
            $msg = '✅ Đơn hàng hoàn thành! Đã gửi email kèm license key cho khách hàng.';
        } else {
            $msg = '✅ Đã cập nhật hoàn thành. (Lưu ý: Gửi email thất bại — kiểm tra cấu hình SMTP)';
        }
    }
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
      <div style="background:linear-gradient(135deg,rgba(15,110,86,.08),rgba(29,158,117,.05));border:1px solid rgba(15,110,86,.2);border-radius:12px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:var(--ink-2);display:flex;align-items:center;gap:10px">
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
          <thead><tr><th>#</th><th>Khách hàng</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th></tr></thead>
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
            <td style="font-size:12px;color:var(--ink-3)"><?= $o['sp'] ?> SP</td>
            <td style="font-weight:800;color:var(--green-2)"><?= fmtVND($o['tong_tien']) ?></td>
            <td style="font-size:12px;color:var(--ink-4)"><?= e($o['phuong_thuc_tt']??'—') ?></td>
            <td>
              <form method="POST" onchange="submitStatusForm(this)">
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
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>

<script>
function submitStatusForm(form) {
  const sel = form.querySelector('select[name=trang_thai]');
  if (sel.value === 'hoan_thanh') {
    if (!confirm('Xác nhận hoàn thành đơn hàng?\n\nHệ thống sẽ tự động gửi email kèm license key cho khách hàng.')) {
      sel.value = sel.dataset.prev || 'cho_xu_ly';
      return;
    }
  }
  form.submit();
}
document.querySelectorAll('select[name=trang_thai]').forEach(sel => {
  sel.dataset.prev = sel.value;
  sel.addEventListener('focus', () => { sel.dataset.prev = sel.value; });
});

</script>
</body>
</html>
