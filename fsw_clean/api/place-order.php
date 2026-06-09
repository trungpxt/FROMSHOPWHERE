<?php
require_once __DIR__ . '/../config.php';

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

// Chuẩn hoá từng dòng
$lines = [];
foreach ($itemsIn as $row) {
    $pid = (int)($row['id'] ?? 0);
    $qty = max(1, (int)($row['qty'] ?? 1));
    $price = (float)($row['price'] ?? 0);
    if ($pid <= 0 || $price < 0) continue;
    $lines[] = ['id' => $pid, 'qty' => $qty, 'price' => $price];
}

if (count($lines) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Giỏ hàng trống']);
    exit;
}

$VALID_COUPONS = [
    'FIRST15' => 15,  // phần trăm giảm
];

$subtotal = 0;
foreach ($lines as $l) {
    $subtotal += $l['price'] * $l['qty'];
}

$tongTien = $subtotal;
$giamPhanTram = 0;

if ($coupon !== null) {
    if (!isset($VALID_COUPONS[$coupon])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Mã giảm giá không hợp lệ']);
        exit;
    }
        // FIRST15: chỉ đơn đầu tiên của user này
    if ($coupon === 'FIRST15') {
        $cnt = db()->prepare('SELECT COUNT(*) FROM orders WHERE nguoi_dung_id = ?');
        $cnt->execute([$userId]);
        if ((int)$cnt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode([
                'ok'    => false,
                'error' => 'Mã FIRST15 chỉ áp dụng cho đơn hàng đầu tiên',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $giamPhanTram = $VALID_COUPONS[$coupon];
    $tongTien = (int)round($subtotal * (100 - $giamPhanTram) / 100);
    $giamPhanTram = $VALID_COUPONS[$coupon];
    $tongTien = (int)round($subtotal * (100 - $giamPhanTram) / 100);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // Kiểm tra sản phẩm tồn tại
    $check = $pdo->prepare("SELECT id, gia_ban FROM products WHERE id = :id AND trang_thai = 'hien' LIMIT 1");
    foreach ($lines as $l) {
        $check->execute([':id' => $l['id']]);
        if (!$check->fetch()) {
            throw new RuntimeException('Sản phẩm #' . $l['id'] . ' không tồn tại hoặc đã ẩn');
        }
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

    echo json_encode([
        'ok'       => true,
        'order_id' => $orderId,
        'tong_tien'=> $tongTien,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Không lưu được đơn: ' . $e->getMessage()]);
}