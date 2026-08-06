<?php
/**
 * api/validate-coupon.php — Kiểm tra mã giảm giá khi khách bấm "Áp dụng" ở trang
 * thanh toán. Dùng chung cho cả mã tĩnh cũ (FIRST15) lẫn mã ngẫu nhiên mới
 * (popup / email / admin_test) được lưu trong bảng coupons.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/coupon-lib.php';

header('Content-Type: application/json; charset=utf-8');
startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Chỉ chấp nhận POST']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$code = trim($data['code'] ?? '');

$userId = isLoggedIn() ? (int)$_SESSION['user_id'] : null;
$result = coupon_validate($code, $userId);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
