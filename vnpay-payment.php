<?php
/**
 * vnpay-payment.php
 * Nhận đơn hàng từ checkout, lưu DB (trạng thái chờ), rồi redirect sang VNPay
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vnpay-config.php';
require_once __DIR__ . '/includes/mail.php';
require_once __DIR__ . '/includes/coupon-lib.php';
startSession();

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php?redirect=' . urlencode(SITE_URL . '/checkout.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/checkout.php');
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['items'])) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Dữ liệu không hợp lệ']);
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$coupon  = strtoupper(trim($data['ma_giam_gia'] ?? ''));
$payment = 'VNPay';

// ── Tính tiền ──
$lines = [];
$subtotal = 0;

foreach ($data['items'] as $row) {
    $pid   = (int)($row['id'] ?? 0);
    $qty   = max(1, (int)($row['qty'] ?? 1));
    $price = (float)($row['price'] ?? 0);
    if ($pid <= 0) continue;
    $lines[]   = ['id'=>$pid,'qty'=>$qty,'price'=>$price];
    $subtotal += $price * $qty;
}

if (empty($lines)) {
    echo json_encode(['ok'=>false,'error'=>'Giỏ hàng trống']); exit;
}

$tongTien = (int)$subtotal;
$couponCode = null;

if ($coupon !== '') {
    $check = coupon_validate($coupon, $userId);
    if (!$check['ok']) {
        echo json_encode(['ok'=>false,'error'=>$check['error']]);
        exit;
    }
    $tongTien   = (int)round($subtotal * (100 - $check['percent']) / 100);
    $couponCode = $coupon;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Lưu đơn hàng trạng thái 'cho_xu_ly' (chờ VNPay xác nhận)
    $pdo->prepare("
        INSERT INTO orders (nguoi_dung_id, tong_tien, trang_thai, phuong_thuc_tt, ma_giam_gia)
        VALUES (?, ?, 'cho_xu_ly', 'VNPay', ?)
    ")->execute([$userId, $tongTien, $couponCode]);
    $orderId = (int)$pdo->lastInsertId();

    $insItem = $pdo->prepare("INSERT INTO order_items (don_hang_id, san_pham_id, so_luong, don_gia) VALUES (?,?,?,?)");
    foreach ($lines as $l) {
        $insItem->execute([$orderId, $l['id'], $l['qty'], $l['price']]);
    }

    $pdo->commit();

    // Gửi email xác nhận đặt hàng — không để lỗi gửi mail chặn redirect sang VNPay
    $mailSent = sendOrderPlacedEmail($orderId);
    if (!$mailSent) {
        error_log('[vnpay-payment] Không gửi được email xác nhận cho đơn #' . $orderId);
    }

    // Lưu orderId vào session để verify khi callback
    $_SESSION['vnpay_order_id'] = $orderId;
    $_SESSION['ck_name']  = trim($data['ho_ten']  ?? '');
    $_SESSION['ck_email'] = trim($data['email']   ?? '');
    $_SESSION['ck_phone'] = trim($data['phone']   ?? '');

    // Tạo URL VNPay
    $ip       = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $orderInfo = 'Thanh toan don hang #' . $orderId . ' FROMSHOPWHERE';
    $payUrl   = vnpay_create_payment_url($orderId, $tongTien, $orderInfo, $ip);

    echo json_encode(['ok'=>true, 'pay_url'=>$payUrl, 'order_id'=>$orderId]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[VNPay] Create order error: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'Không tạo được đơn hàng. Vui lòng thử lại hoặc liên hệ hỗ trợ.']);
}
