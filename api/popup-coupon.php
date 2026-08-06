<?php
/**
 * api/popup-coupon.php — Tạo và trả về 1 mã giảm giá ngẫu nhiên (5–20%)
 * để hiển thị trong popup khi khách vừa vào web (1 lần/phiên truy cập).
 * Việc giới hạn "1 lần/phiên" được xử lý ở phía trình duyệt (sessionStorage)
 * trong assets/js/coupon-popup.js; ở đây chỉ thêm 1 lớp chặn spam bằng PHP session
 * phòng khi JS bị tắt/gọi lại nhiều lần.
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

// Đã phát mã trong phiên này rồi thì trả lại đúng mã cũ, không tạo mã mới
if (!empty($_SESSION['popup_coupon_code'])) {
    echo json_encode([
        'ok'           => true,
        'code'         => $_SESSION['popup_coupon_code'],
        'percent'      => $_SESSION['popup_coupon_percent'],
        'expires_at'   => $_SESSION['popup_coupon_expires'],
        'is_returning' => $_SESSION['popup_is_returning'] ?? false,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $userId = isLoggedIn() ? (int)$_SESSION['user_id'] : null;

    // Khách đã từng đặt đơn thành công chưa — tránh chào "Chào mừng bạn đến..."
    // với khách quen (trải nghiệm kỳ lạ nếu đã mua 10 lần vẫn bị coi là mới)
    $isReturning = false;
    if ($userId) {
        $chk = db()->prepare(
            "SELECT 1 FROM orders WHERE nguoi_dung_id = :uid AND trang_thai IN ('da_thanh_toan','hoan_thanh') LIMIT 1"
        );
        $chk->execute([':uid' => $userId]);
        $isReturning = (bool) $chk->fetch();
    }

    $c = coupon_create($userId, 'popup', 7);

    $_SESSION['popup_coupon_code']    = $c['code'];
    $_SESSION['popup_coupon_percent'] = $c['percent'];
    $_SESSION['popup_coupon_expires'] = $c['expires_at'];
    $_SESSION['popup_is_returning']   = $isReturning;

    echo json_encode(['ok' => true, 'is_returning' => $isReturning] + $c, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[PopupCoupon] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Không thể tạo mã giảm giá lúc này']);
}
