<?php
/**
 * api/admin-send-coupon-test.php — Admin bấm nút "Gửi ngay để test" trong
 * admin/coupons.php. Gửi thật, ngay lập tức, cho TẤT CẢ user đã đăng ký —
 * không bị giới hạn 4 tiếng như cron (để admin dễ kiểm tra flow hoạt động).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/coupon-lib.php';

header('Content-Type: application/json; charset=utf-8');
startSession();

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Chỉ admin mới được dùng chức năng này']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Chỉ chấp nhận POST']);
    exit;
}

csrfCheck();

try {
    $result = coupon_send_email_batch('admin_test');
    echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[AdminCouponTest] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Gửi thất bại: ' . $e->getMessage()]);
}
