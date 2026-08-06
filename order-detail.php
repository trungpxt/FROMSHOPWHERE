<?php
require_once __DIR__ . '/config.php';
startSession();
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode('/order-detail.php?id=' . (int)($_GET['id'] ?? 0)));
}
$_user = currentUser();
$orderId = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM orders WHERE id = ? AND nguoi_dung_id = ?");
$stmt->execute([$orderId, $_user['id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$itemsStmt = db()->prepare(
    "SELECT oi.*, p.ten_san_pham, p.hinh_anh, p.slug
     FROM order_items oi
     JOIN products p ON p.id = oi.san_pham_id
     WHERE oi.don_hang_id = ?
     ORDER BY oi.id ASC"
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

[$statusClass, $statusLabel, $statusStep] = match ($order['trang_thai']) {
    'da_thanh_toan' => ['b-tt',   'Đã thanh toán', 2],
    'hoan_thanh'    => ['b-hoan', 'Hoàn thành',    3],
    'huy'           => ['b-huy',  'Đã huỷ',        0],
    default         => ['b-cho',  'Chờ xử lý',     1],
};
$showKeys = in_array($order['trang_thai'], ['da_thanh_toan', 'hoan_thanh'], true);
$currentPage = '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đơn hàng #<?= $orderId ?> — FROMSHOPWHERE</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/profile.css">
<link rel="stylesheet" href="assets/css/order-detail.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">📦 Chi tiết đơn hàng</span></div>
    <h1>Đơn hàng #<?= $orderId ?></h1>
    <p>Đặt lúc <?= date('H:i d/m/Y', strtotime($order['ngay_dat'])) ?></p>
  </div>
</div>

<div class="section">
  <div class="od-wrap">

    <a class="od-back" href="profile.php">← Quay lại danh sách đơn hàng</a>

    <?php if ($order['trang_thai'] !== 'huy'): ?>
    <div class="od-steps">
      <div class="od-step <?= $statusStep >= 1 ? 'on' : '' ?>"><span>1</span>Đặt hàng</div>
      <div class="od-step <?= $statusStep >= 2 ? 'on' : '' ?>"><span>2</span>Đã thanh toán</div>
      <div class="od-step <?= $statusStep >= 3 ? 'on' : '' ?>"><span>3</span>Hoàn thành</div>
    </div>
    <?php else: ?>
      <div class="od-cancelled">🚫 Đơn hàng này đã bị huỷ.</div>
    <?php endif; ?>

    <div class="od-card">
      <div class="od-card-head">
        <h3>Sản phẩm đã mua</h3>
        <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
      </div>

      <?php foreach ($items as $it):
        $imgPath = $it['hinh_anh'] ?? '';
        $imgUrl  = $imgPath === ''
            ? SITE_URL . '/images/ui/default.jpg'
            : SITE_URL . '/images/' . ltrim(str_contains($imgPath, '/') ? $imgPath : 'products/' . $imgPath, '/');
        $lineTotal = (float)$it['don_gia'] * (int)$it['so_luong'];
      ?>
      <div class="od-item">
        <img src="<?= e($imgUrl) ?>" alt="<?= e($it['ten_san_pham']) ?>" width="56" height="56"
             onerror="this.src='<?= SITE_URL ?>/images/ui/default.jpg'">
        <div class="od-item-info">
          <div class="od-item-name"><?= e($it['ten_san_pham']) ?></div>
          <div class="od-item-meta">Số lượng: ×<?= (int)$it['so_luong'] ?> &nbsp;·&nbsp; Đơn giá: <?= fmtVND((float)$it['don_gia']) ?></div>

          <?php if ($showKeys): ?>
            <?php if (!empty($it['license_key'])): ?>
              <div class="od-key-row">
                <code class="od-key" id="key-<?= (int)$it['id'] ?>"><?= e($it['license_key']) ?></code>
                <button type="button" class="od-copy-btn" onclick="copyOrderKey('key-<?= (int)$it['id'] ?>', this)">📋 Sao chép</button>
              </div>
            <?php else: ?>
              <div class="od-key-pending">Key đang được cấp, vui lòng kiểm tra lại email hoặc chờ giây lát.</div>
            <?php endif; ?>
          <?php else: ?>
            <div class="od-key-pending">Key sẽ hiển thị sau khi đơn hàng được thanh toán.</div>
          <?php endif; ?>
        </div>
        <div class="od-item-total"><?= fmtVND($lineTotal) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="od-card od-summary">
      <h3>Thông tin đơn hàng</h3>
      <div class="od-row"><span>Mã đơn hàng</span><strong>#<?= $orderId ?></strong></div>
      <div class="od-row"><span>Phương thức thanh toán</span><strong><?= e($order['phuong_thuc_tt']) ?></strong></div>
      <?php if (!empty($order['ma_giam_gia'])): ?>
      <div class="od-row"><span>Mã giảm giá</span><strong><?= e($order['ma_giam_gia']) ?></strong></div>
      <?php endif; ?>
      <div class="od-row"><span>Ngày đặt</span><strong><?= date('H:i d/m/Y', strtotime($order['ngay_dat'])) ?></strong></div>
      <div class="od-row od-row-total"><span>Tổng cộng</span><strong><?= fmtVND((float)$order['tong_tien']) ?></strong></div>
    </div>

    <?php if ($order['trang_thai'] !== 'huy'): ?>
    <div class="od-help">
      <div>
        <strong>Gặp vấn đề với đơn hàng này?</strong>
        <p>Key không kích hoạt được, sai sản phẩm, hoặc cần hỗ trợ khác — chúng tôi bảo hành 1 đổi 1.</p>
      </div>
      <a class="btn-detail" href="contact.php?subject=<?= urlencode('Bảo hành / Đổi trả Key') ?>&order=<?= $orderId ?>">Yêu cầu bảo hành / hỗ trợ →</a>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
function copyOrderKey(elId, btn) {
  const text = document.getElementById(elId).textContent.trim();
  navigator.clipboard.writeText(text).then(() => {
    const old = btn.textContent;
    btn.textContent = '✓ Đã sao chép';
    setTimeout(() => { btn.textContent = old; }, 1600);
  });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
