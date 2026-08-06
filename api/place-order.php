<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/mail.php';
require_once __DIR__ . '/../includes/coupon-lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Chỉ chấp nhận POST']);
    exit;
}

startSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Vui lòng đăng nhập để lưu đơn hàng']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Dữ liệu đơn hàng không hợp lệ']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$itemsIn  = $data['items'];
$payment  = trim($data['phuong_thuc_tt'] ?? 'Chuyển khoản ngân hàng');
$coupon   = trim($data['ma_giam_gia'] ?? '');
$coupon   = $coupon !== '' ? strtoupper($coupon) : null;

// Chuẩn hoá từng dòng — CHỈ lấy id & số lượng từ client; giá sẽ được tra lại từ DB bên dưới.
// Không bao giờ tin giá do trình duyệt gửi lên, để khách hàng không thể sửa giá qua DevTools.
$lines = [];
foreach ($itemsIn as $row) {
    $pid = (int)($row['id'] ?? 0);
    $qty = max(1, (int)($row['qty'] ?? 1));
    if ($pid <= 0) continue;
    $lines[] = ['id' => $pid, 'qty' => $qty];
}

if (count($lines) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Giỏ hàng trống']);
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // Tra giá THẬT + kiểm tra sản phẩm tồn tại/đang hiển thị — bỏ qua hoàn toàn giá client gửi lên
    $check = $pdo->prepare("SELECT id, gia_ban FROM products WHERE id = :id AND trang_thai = 'hien' LIMIT 1");
    foreach ($lines as &$l) {
        $check->execute([':id' => $l['id']]);
        $row = $check->fetch();
        if (!$row) {
            throw new RuntimeException('Sản phẩm #' . $l['id'] . ' không tồn tại hoặc đã ẩn');
        }
        $l['price'] = (float)$row['gia_ban'];
    }
    unset($l);

    $subtotal = 0;
    foreach ($lines as $l) {
        $subtotal += $l['price'] * $l['qty'];
    }

    $tongTien = $subtotal;
    $giamPhanTram = 0;

    if ($coupon !== null) {
        $check2 = coupon_validate($coupon, $userId);
        if (!$check2['ok']) {
            throw new RuntimeException($check2['error']);
        }
        $giamPhanTram = $check2['percent'];
        $tongTien = (int)round($subtotal * (100 - $giamPhanTram) / 100);
    }

    $insOrder = $pdo->prepare("
        INSERT INTO orders (nguoi_dung_id, tong_tien, trang_thai, phuong_thuc_tt, ma_giam_gia)
        VALUES (:uid, :tong, 'cho_xu_ly', :pt, :mg)
    ");
    $insOrder->execute([
        ':uid'  => $userId,
        ':tong' => $tongTien,
        ':pt'   => $payment,
        ':mg'   => $coupon,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    $insItem = $pdo->prepare("
        INSERT INTO order_items (don_hang_id, san_pham_id, so_luong, don_gia)
        VALUES (:oid, :pid, :qty, :gia)
    ");
    foreach ($lines as $l) {
        $insItem->execute([
            ':oid' => $orderId,
            ':pid' => $l['id'],
            ':qty' => $l['qty'],
            ':gia' => $l['price'],
        ]);
    }

    $pdo->commit();

    // Đánh dấu mã giảm giá (nếu có) đã được sử dụng — chỉ áp dụng cho mã động, FIRST15 bỏ qua
    if ($coupon !== null) {
        coupon_mark_used($coupon);
    }

    // Gửi email xác nhận đặt hàng — không để lỗi gửi mail làm hỏng response đặt hàng
    $mailSent = sendOrderPlacedEmail($orderId);
    if (!$mailSent) {
        error_log('[place-order] Không gửi được email xác nhận cho đơn #' . $orderId);
    }

    echo json_encode([
        'ok'        => true,
        'order_id'  => $orderId,
        'tong_tien' => $tongTien,
        'mail_sent' => $mailSent,
    ], JSON_UNESCAPED_UNICODE);

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('api/place-order.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Không lưu được đơn hàng. Vui lòng thử lại hoặc liên hệ hỗ trợ.']);
}