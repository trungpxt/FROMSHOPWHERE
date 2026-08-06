<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/referral.php';
startSession();
if (!isLoggedIn()) redirect(SITE_URL . '/login.php?redirect=' . urlencode(SITE_URL . '/profile.php'));

$msg = ''; $error = '';
$user = currentUser();

// Lấy thông tin đầy đủ từ DB
$u = db()->prepare("SELECT * FROM users WHERE id=:id");
$u->execute([':id' => $user['id']]);
$u = $u->fetch();

$refStats = referral_stats((int)$user['id']);

// Lấy danh sách đơn hàng
$ordersStmt = db()->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.nguoi_dung_id = :id
    ORDER BY o.ngay_dat DESC
    LIMIT 10
");
$ordersStmt->execute([':id' => $user['id']]);
$orders = $ordersStmt->fetchAll();

// Lấy chi tiết sản phẩm từng đơn (tên + ảnh + số lượng)
$orderItemsByOrder = [];
if (!empty($orders)) {
    $ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $itemsStmt = db()->prepare("
        SELECT
            oi.don_hang_id,
            oi.so_luong,
            oi.don_gia,
            p.ten_san_pham,
            p.hinh_anh
        FROM order_items oi
        JOIN products p ON p.id = oi.san_pham_id
        WHERE oi.don_hang_id IN ($placeholders)
        ORDER BY oi.id ASC
    ");
    $itemsStmt->execute($ids);
    foreach ($itemsStmt->fetchAll() as $row) {
        $orderItemsByOrder[$row['don_hang_id']][] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $action = $_POST['action'] ?? '';

if ($action === 'update_info') {
    $ten = trim($_POST['ho_ten'] ?? '');
    $sdt = trim($_POST['so_dien_thoai'] ?? '');
    $dia = trim($_POST['dia_chi'] ?? '');

    if (!$ten) {
        $error = 'Họ tên không được để trống.';
    } elseif ($sdt !== '' && !isValidVnPhonePhp($sdt)) {
        // SĐT để trống vẫn cho lưu; có nhập thì phải đúng format
        $error = 'Số điện thoại không đúng (10 số, bắt đầu 0).';
    } else {
        $sdtLuu = $sdt !== '' ? normalizePhonePhp($sdt) : null;
        db()->prepare("UPDATE users SET ho_ten=:t, so_dien_thoai=:s, dia_chi=:d WHERE id=:id")
           ->execute([':t' => $ten, ':s' => $sdtLuu, ':d' => $dia, ':id' => $user['id']]);
        $_SESSION['user_name'] = $ten;
        $msg = '✓ Đã cập nhật thông tin.';
        $u['ho_ten'] = $ten;
        $u['so_dien_thoai'] = $sdtLuu;
        $u['dia_chi'] = $dia;
    }
}
    

    if ($action === 'change_pass') {
        $old  = $_POST['old_pass']  ?? '';
        $new  = $_POST['new_pass']  ?? '';
        $new2 = $_POST['new_pass2'] ?? '';
        if (!$old || !$new) { $error = 'Vui lòng điền đầy đủ.'; }
        elseif (!password_verify($old, $u['mat_khau'])) { $error = 'Mật khẩu cũ không đúng.'; }
        elseif (strlen($new) < 6) { $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.'; }
        elseif ($new !== $new2)   { $error = 'Mật khẩu nhập lại không khớp.'; }
        else {
            db()->prepare("UPDATE users SET mat_khau=:p WHERE id=:id")
               ->execute([':p'=>password_hash($new,PASSWORD_BCRYPT),':id'=>$user['id']]);
            $msg = '✓ Đã đổi mật khẩu thành công.';
        }
    }
}

$currentPage = '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tài khoản — FROMSHOPWHERE</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/profile.css">
</head>
<body data-initial-tab="<?= ($msg && str_contains($msg,'mật khẩu')) ? 'pass' : 'info' ?>">
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>



<script src="assets/js/profile.js"></script>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">👤 Tài khoản</span></div>
    <h1>Tài khoản của tôi</h1>
    <p>Quản lý thông tin và lịch sử đơn hàng</p>
  </div>
</div>

<div class="profile-wrap">
  <?php if($msg):?><div class="msg-ok"><?= $msg ?></div><?php endif;?>
  <?php if($error):?><div class="msg-err">⚠ <?= e($error) ?></div><?php endif;?>

  <div class="profile-grid">
    <!-- Sidebar -->
    <div class="profile-side">
      <div class="profile-avatar"><?= strtoupper(mb_substr($u['ho_ten'],0,1)) ?></div>
      <div class="profile-name"><?= e($u['ho_ten']) ?></div>
      <div class="profile-email"><?= e($u['email']) ?></div>
      <div class="profile-role"><?= $u['vai_tro']==='admin' ? '⚙️ Admin' : '👤 Khách hàng' ?></div>
      <?php if($u['vai_tro']==='admin'): ?>
      <a href="<?= SITE_URL ?>/admin/" class="btn-primary" style="display:block;margin-top:14px;font-size:13px;text-align:center">⚙️ Vào trang Admin</a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/logout.php" style="display:block;margin-top:10px;color:var(--text-muted);font-size:13px;text-decoration:none">🚪 Đăng xuất</a>
    </div>

    <!-- Main -->
    <div>
      <div class="profile-tabs">
        <button class="ptab on" onclick="showTab('info',this)">👤 Thông tin</button>
        <button class="ptab" onclick="showTab('pass',this)">🔒 Đổi mật khẩu</button>
        <button class="ptab" onclick="showTab('orders',this)">🛒 Đơn hàng</button>
        <button class="ptab" onclick="showTab('referral',this)">🎁 Giới thiệu bạn bè</button>
      </div>

      <!-- Tab: Thông tin -->
      <div class="tab-content on" id="tab-info">
        <div class="info-box">
          <h3>Thông tin cá nhân</h3>
          <form method="POST">
      <?= csrfField() ?>
            <input type="hidden" name="action" value="update_info">
            <div class="form-group">
              <label class="form-label">Họ và tên</label>
              <input class="form-input" type="text" name="ho_ten" required value="<?= e($u['ho_ten']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Email <span style="color:var(--text-muted);font-weight:400">(không thể đổi)</span></label>
              <input class="form-input" type="email" value="<?= e($u['email']) ?>" disabled style="opacity:.6">
            </div>
            <div class="form-group">
              <label class="form-label">Số điện thoại</label>
              <input class="form-input" type="tel" name="so_dien_thoai" id="profilePhone"
                     placeholder="0901234567" inputmode="numeric" maxlength="10"
                     pattern="0(3|5|7|8|9)[0-9]{8}"
                     title="Số điện thoại VN: 10 số, đầu 03/05/07/08/09"
                     value="<?= e($u['so_dien_thoai']??'') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Địa chỉ</label>
              <textarea class="form-input" name="dia_chi" rows="2" style="resize:vertical"><?= e($u['dia_chi']??'') ?></textarea>
            </div>
            <button type="submit" class="btn-submit" style="width:auto;padding:11px 28px">💾 Lưu thay đổi</button>
          </form>
        </div>
      </div>

      <!-- Tab: Đổi mật khẩu -->
      <div class="tab-content" id="tab-pass">
        <div class="info-box">
          <h3>Đổi mật khẩu</h3>
          <form method="POST">
      <?= csrfField() ?>
            <input type="hidden" name="action" value="change_pass">
            <div class="form-group">
              <label class="form-label">Mật khẩu hiện tại</label>
              <input class="form-input" type="password" name="old_pass" required placeholder="••••••••">
            </div>
            <div class="form-group">
              <label class="form-label">Mật khẩu mới</label>
              <input class="form-input" type="password" name="new_pass" required placeholder="Tối thiểu 6 ký tự">
            </div>
            <div class="form-group">
              <label class="form-label">Nhập lại mật khẩu mới</label>
              <input class="form-input" type="password" name="new_pass2" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-submit" style="width:auto;padding:11px 28px">🔒 Đổi mật khẩu</button>
          </form>
        </div>
      </div>

      <!-- Tab: Đơn hàng -->
      <div class="tab-content" id="tab-orders">
        <div class="info-box">
          <h3>Lịch sử đơn hàng</h3>
          <?php if(empty($orders)): ?>
            <div style="text-align:center;padding:32px 0;color:var(--text-muted)">
              <div style="font-size:40px;margin-bottom:12px">🛒</div>
              <p>Chưa có đơn hàng nào.</p>
              <a class="btn-primary" href="<?= SITE_URL ?>/products.php" style="display:inline-block;margin-top:12px;font-size:13px">Mua ngay →</a>
            </div>
          <?php else: ?>
          <table class="ord-tbl">
            <thead><tr><th>#</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày đặt</th><th></th></tr></thead>
            <tbody>
            <?php foreach($orders as $o):
              [$cls,$lbl]=match($o['trang_thai']){
                'da_thanh_toan'=>['b-tt','Đã thanh toán'],
                'hoan_thanh'   =>['b-hoan','Hoàn thành'],
                'huy'          =>['b-huy','Đã huỷ'],
                default        =>['b-cho','Chờ xử lý'],
              };
            ?>
            <tr>
              <td style="font-weight:700;color:var(--green-600,#0A8A4C)">#<?= $o['id']?></td>
<td>
  <?php
    $items = $orderItemsByOrder[$o['id']] ?? [];
    if (empty($items)):
  ?>
    <span style="color:var(--text-muted);font-size:13px">—</span>
  <?php else: ?>
    <div class="ord-products">
      <?php foreach ($items as $it):
        $imgPath = $it['hinh_anh'] ?? '';
        if ($imgPath === '') {
            $imgUrl = SITE_URL . '/images/ui/default.jpg';
        } else {
            $path = str_contains($imgPath, '/') ? $imgPath : 'products/' . $imgPath;
            $imgUrl = SITE_URL . '/images/' . ltrim($path, '/');
        }
        $lineTotal = (float)$it['don_gia'] * (int)$it['so_luong'];
      ?>
      <div class="ord-product-row">
        <img src="<?= e($imgUrl) ?>" alt="<?= e($it['ten_san_pham']) ?>"
             width="48" height="48"
             onerror="this.src='<?= SITE_URL ?>/images/ui/default.jpg'">
        <div>
          <div class="ord-product-name"><?= e($it['ten_san_pham']) ?></div>
          <div class="ord-product-meta">×<?= (int)$it['so_luong'] ?> · <?= fmtVND($lineTotal) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</td>
              <td style="font-weight:700;color:var(--green-600,#0A8A4C)"><?= fmtVND($o['tong_tien'])?></td>
              <td><span class="badge <?= $cls?>"><?= $lbl?></span></td>
              <td style="color:var(--text-muted)"><?= date('d/m/Y H:i',strtotime($o['ngay_dat']))?></td>
              <td><a class="btn-detail" style="white-space:nowrap;font-size:12px;padding:6px 12px" href="<?= SITE_URL ?>/order-detail.php?id=<?= (int)$o['id'] ?>">Chi tiết →</a></td>
            </tr>
            <?php endforeach;?>
            </tbody>
          </table>
          <?php endif;?>
        </div>
      </div>

      <!-- Tab: Giới thiệu bạn bè -->
      <div class="tab-content" id="tab-referral">
        <div class="info-box">
          <h3>🎁 Giới thiệu bạn bè, nhận quà</h3>
          <p style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
            Chia sẻ link dưới đây cho bạn bè. Khi họ đăng ký và <strong>mua hàng thành công lần đầu tiên</strong>,
            bạn sẽ nhận ngay mã giảm giá <strong><?= REFERRAL_REWARD_PERCENT ?>%</strong> cho lần mua tiếp theo.
          </p>

          <div class="ref-link-row">
            <input type="text" id="refLinkInput" class="form-input" readonly value="<?= e($refStats['link']) ?>">
            <button type="button" class="btn-detail" onclick="copyRefLink()">📋 Sao chép</button>
          </div>

          <div class="ref-stats-row">
            <div class="ref-stat"><strong><?= $refStats['total'] ?></strong><span>Đã giới thiệu</span></div>
            <div class="ref-stat"><strong><?= $refStats['rewarded'] ?></strong><span>Đã nhận quà</span></div>
            <div class="ref-stat"><strong><?= REFERRAL_REWARD_PERCENT ?>%</strong><span>Ưu đãi mỗi lượt</span></div>
          </div>

          <?php if (empty($refStats['list'])): ?>
            <p style="font-size:13px;color:var(--text-muted);text-align:center;padding:24px 0">
              Bạn chưa giới thiệu ai. Hãy chia sẻ link ở trên nhé!
            </p>
          <?php else: ?>
            <table class="ord-tbl">
              <thead><tr><th>Người được giới thiệu</th><th>Ngày tham gia</th><th>Trạng thái</th></tr></thead>
              <tbody>
                <?php foreach ($refStats['list'] as $r): ?>
                <tr>
                  <td><?= e($r['ho_ten']) ?></td>
                  <td style="color:var(--text-muted)"><?= date('d/m/Y', strtotime($r['ngay_tao'])) ?></td>
                  <td>
                    <?php if ($r['da_thuong']): ?>
                      <span class="badge b-tt">🎁 Đã tặng <?= e($r['coupon_code']) ?></span>
                    <?php else: ?>
                      <span class="badge b-cho">Chờ đơn hàng đầu tiên</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="footer-inner">
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div><div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div><div class="pay-badge">ZALO</div>
      </div>
    </div>
  </div>
</footer>

</body>
</html>
