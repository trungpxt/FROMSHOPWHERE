<?php
/**
 * vnpay-return.php
 * VNPay redirect về đây sau khi khách thanh toán
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vnpay-config.php';
require_once __DIR__ . '/mail-config.php';
require_once __DIR__ . '/includes/coupon-lib.php';
require_once __DIR__ . '/includes/referral.php';
require_once __DIR__ . '/includes/notify.php';
startSession();

$params      = $_GET;
$responseCode = $params['vnp_ResponseCode'] ?? '';
$orderId     = (int)explode('_', $params['vnp_TxnRef'] ?? '0')[0];
$amount      = (int)(($params['vnp_Amount'] ?? 0) / 100);
$transNo     = $params['vnp_TransactionNo'] ?? '';
$bankCode    = $params['vnp_BankCode'] ?? '';
$payDate     = $params['vnp_PayDate'] ?? '';

// ── Xác minh chữ ký ──
$validSig = vnpay_verify_signature($params);

$status  = 'error';
$title   = 'Thanh toán thất bại';
$message = 'Đã có lỗi xảy ra.';
$icon    = '❌';
$color   = '#EF4444';

if (!$validSig) {
    $message = 'Chữ ký không hợp lệ. Vui lòng liên hệ hỗ trợ.';
} elseif ($responseCode === '00') {
    // ── Thanh toán thành công ──
    $status  = 'success';
    $title   = 'Thanh toán thành công!';
    $icon    = '🎉';
    $color   = '#3B2FA0';

    try {
        // Cập nhật trạng thái đơn hàng
        $order = db()->query("SELECT * FROM orders WHERE id=$orderId")->fetch();
        if ($order && $order['trang_thai'] === 'cho_xu_ly') {
            db()->prepare("UPDATE orders SET trang_thai='da_thanh_toan', phuong_thuc_tt=? WHERE id=?")
               ->execute(["VNPay - " . $bankCode, $orderId]);

            if (!empty($order['ma_giam_gia'])) {
                coupon_mark_used($order['ma_giam_gia']);
            }

            referral_maybe_reward((int)$order['nguoi_dung_id']);

            // Lấy thông tin user để gửi email
            $user = db()->query("SELECT u.ho_ten, u.email FROM orders o JOIN users u ON u.id=o.nguoi_dung_id WHERE o.id=$orderId")->fetch();

            if ($user) {
                $message = "Đơn hàng <strong>#$orderId</strong> đã được xác nhận. Chúng tôi sẽ gửi license key qua email <strong>{$user['email']}</strong> trong vài phút.";

                // Gửi email xác nhận thanh toán
                try {
                    $mail = createMailer();
                    $mail->addAddress($user['email'], $user['ho_ten']);
                    $mail->Subject = "✅ Xác nhận thanh toán đơn #$orderId — FROMSHOPWHERE";
                    $mail->isHTML(true);
                    $mail->Body = "
                    <div style='font-family:sans-serif;max-width:560px;margin:0 auto'>
                      <div style='background:linear-gradient(135deg,#16123F,#3B2FA0);padding:22px 28px;border-radius:12px 12px 0 0;text-align:center'>
                        <h2 style='color:#fff;margin:0;font-size:20px'>✅ Thanh toán thành công!</h2>
                      </div>
                      <div style='background:#fff;border:1px solid #e5e7eb;border-top:none;padding:26px 28px;border-radius:0 0 12px 12px'>
                        <p>Xin chào <strong>{$user['ho_ten']}</strong>,</p>
                        <p style='font-size:14px;color:#555;line-height:1.7'>
                          VNPay đã xác nhận thanh toán thành công cho đơn hàng <strong>#$orderId</strong>.
                        </p>
                        <div style='background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:16px;margin:16px 0;font-size:14px'>
                          <div style='display:flex;justify-content:space-between;margin-bottom:8px'><span style='color:#6b7280'>Mã đơn hàng</span><strong>#$orderId</strong></div>
                          <div style='display:flex;justify-content:space-between;margin-bottom:8px'><span style='color:#6b7280'>Số tiền</span><strong style='color:#3B2FA0'>" . number_format($amount,0,',','.') . "đ</strong></div>
                          <div style='display:flex;justify-content:space-between;margin-bottom:8px'><span style='color:#6b7280'>Ngân hàng</span><strong>$bankCode</strong></div>
                          <div style='display:flex;justify-content:space-between'><span style='color:#6b7280'>Mã GD VNPay</span><strong>$transNo</strong></div>
                        </div>
                        <p style='font-size:13px;color:#555;line-height:1.7'>
                          License key sẽ được gửi bổ sung khi admin xác nhận hoàn thành đơn hàng.
                        </p>
                        <div style='text-align:center;margin-top:18px'>
                          <a href='" . SITE_URL . "/profile.php' style='background:#3B2FA0;color:#EEECFB;padding:11px 24px;border-radius:9px;text-decoration:none;font-size:14px;font-weight:700'>
                            📋 Xem đơn hàng
                          </a>
                        </div>
                        <p style='font-size:12px;color:#9ca3af;margin-top:18px'>© " . date('Y') . " FROMSHOPWHERE</p>
                      </div>
                    </div>";
                    $mail->send();
                } catch(Exception $ex) {
                    error_log('[VNPay Return] Email error: ' . $ex->getMessage());
                }
            }
        } else {
            $message = "Đơn hàng <strong>#$orderId</strong> đã được xử lý trước đó.";
        }
    } catch(Exception $e) {
        error_log('[VNPay Return] DB error: ' . $e->getMessage());
        $message = "Thanh toán thành công nhưng lỗi cập nhật. Liên hệ hỗ trợ với mã GD: $transNo";
    }
} else {
    // Các mã lỗi phổ biến
    $errorMap = [
        '07'=>'Giao dịch bị nghi ngờ gian lận.',
        '09'=>'Thẻ/Tài khoản chưa đăng ký Internet Banking.',
        '10'=>'Xác thực thông tin thẻ quá 3 lần.',
        '11'=>'Phiên thanh toán hết hạn. Vui lòng thử lại.',
        '12'=>'Thẻ/Tài khoản bị khóa.',
        '13'=>'Sai mật khẩu OTP.',
        '24'=>'Khách hàng hủy giao dịch.',
        '51'=>'Tài khoản không đủ số dư.',
        '65'=>'Vượt hạn mức giao dịch trong ngày.',
        '75'=>'Ngân hàng thanh toán đang bảo trì.',
        '79'=>'Sai mật khẩu thanh toán quá số lần quy định.',
    ];
    $message = $errorMap[$responseCode] ?? "Giao dịch thất bại (Mã lỗi: $responseCode).";

    // Hủy đơn nếu user chủ động hủy (mã 24)
    if ($responseCode === '24' && $orderId > 0) {
        db()->prepare("UPDATE orders SET trang_thai='huy' WHERE id=? AND trang_thai='cho_xu_ly'")
           ->execute([$orderId]);
    }
}

// Xóa session vnpay
unset($_SESSION['vnpay_order_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?> — FROMSHOPWHERE</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/vnpay-return.css">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>
<nav>
  <div class="nav-inner">
    <a class="logo" href="<?= SITE_URL ?>/index.php">
      <img src="<?= SITE_URL ?>/images/ui/logo.png" alt="FROMSHOPWHERE" class="logo-img-light" style="height:44px;width:auto;object-fit:contain">
      <img src="<?= SITE_URL ?>/images/ui/logo-dark.png" alt="FROMSHOPWHERE" class="logo-img-dark" style="height:44px;width:auto;object-fit:contain">
    </a>
  </div>
</nav>

<div class="result-wrap">
  <div class="result-card">
    <span class="result-icon"><?= $icon ?></span>
    <h1 class="result-title" style="color:<?= $color ?>"><?= $title ?></h1>
    <p class="result-msg"><?= $message ?></p>

    <?php if($status === 'success'): ?>
    <div class="result-info">
      <div><span>Mã đơn hàng</span><span><strong>#<?= $orderId ?></strong></span></div>
      <div><span>Số tiền</span><span><?= number_format($amount,0,',','.') ?>đ</span></div>
      <?php if($bankCode): ?><div><span>Ngân hàng</span><span><?= e($bankCode) ?></span></div><?php endif; ?>
      <?php if($transNo): ?><div><span>Mã GD VNPay</span><span><?= e($transNo) ?></span></div><?php endif; ?>
    </div>
    <div>
      <a href="<?= SITE_URL ?>/profile.php" class="btn-home">📋 Xem đơn hàng</a>
      <a href="<?= SITE_URL ?>/products.php" class="btn-retry">🛒 Tiếp tục mua sắm</a>
    </div>
    <?php else: ?>
    <div>
      <a href="<?= SITE_URL ?>/checkout.php" class="btn-home">🔄 Thử lại</a>
      <a href="<?= SITE_URL ?>/products.php" class="btn-retry">← Quay lại</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<script src="assets/js/shared.js"></script>
<script>
const VNPAY_STATUS = <?= json_encode($status) ?>;
if (VNPAY_STATUS === 'success') localStorage.removeItem('fsw-cart');
document.addEventListener('DOMContentLoaded', () => { if (window.initFswEffects) window.initFswEffects(); });
</script>
<script src="assets/js/vnpay-return.js"></script>
</body>
</html>
