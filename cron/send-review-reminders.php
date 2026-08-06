<?php
/**
 * cron/send-review-reminders.php
 * Gửi email "Nhắc đánh giá sản phẩm" cho các đơn hàng đã hoàn thành đủ
 * REVIEW_REMINDER_DELAY_DAYS ngày (mặc định 3 ngày — xem config.php) và
 * chưa được nhắc lần nào.
 *
 * Lịch nhắc được tạo tự động khi admin chuyển đơn sang "hoàn thành"
 * (xem includes/review-reminder-lib.php::reviewReminderSchedule(),
 * gọi từ admin/orders.php). File này chỉ có nhiệm vụ QUÉT & GỬI những
 * lịch đã tới hạn — gọi thường xuyên hơn không sao, không sợ gửi trùng
 * (mỗi đơn chỉ có đúng 1 dòng lịch, đã gửi thì chuyển trạng thái "da_gui").
 *
 * Cách dùng: nhờ 1 dịch vụ cron (cron của hosting, hoặc cron-job.org miễn phí)
 * gọi URL này định kỳ (khuyên mỗi 1-2 tiếng):
 *
 *   https://your-domain.com/cron/send-review-reminders.php?token=fsw-coupon-cron-2026-doi-chuoi-nay
 *
 * Ví dụ cron trên cPanel (chạy mỗi giờ):
 *   0 * * * * curl -s "https://your-domain.com/cron/send-review-reminders.php?token=..." >/dev/null 2>&1
 *
 * Dùng chung CRON_SECRET với cron/send-coupon-emails.php (đổi trong config.php
 * khi lên hosting thật).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/review-reminder-lib.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_GET['token'] ?? '';
if (!hash_equals(CRON_SECRET, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sai token']);
    exit;
}

$result = reviewReminderSendDueBatch();
echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE);
