<?php
/**
 * cron/send-coupon-emails.php
 * Gửi mã giảm giá qua email cho TẤT CẢ user đã đăng ký, mỗi ~4 tiếng/lần.
 *
 * Cách dùng: nhờ 1 dịch vụ cron (cron của hosting, hoặc cron-job.org miễn phí)
 * gọi URL này định kỳ (khuyên gọi mỗi 30-60 phút cho an toàn — file tự kiểm tra
 * và CHỈ gửi thật khi đã đủ 4 tiếng kể từ lần gửi trước, gọi thường xuyên hơn cũng
 * không sao, không sợ spam):
 *
 *   https://your-domain.com/cron/send-coupon-emails.php?token=fsw-coupon-cron-2026-doi-chuoi-nay
 *
 * Ví dụ cron trên cPanel (chạy mỗi giờ):
 *   0 * * * * curl -s "https://your-domain.com/cron/send-coupon-emails.php?token=..." >/dev/null 2>&1
 *
 * Nhớ đổi CRON_SECRET trong config.php thành 1 chuỗi khó đoán khi lên hosting thật.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/coupon-lib.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_GET['token'] ?? '';
if (!hash_equals(CRON_SECRET, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sai token']);
    exit;
}

$last = coupon_last_true_cron_run();
$hoursSince = $last ? (time() - strtotime($last)) / 3600 : 999;

if ($hoursSince < 4) {
    echo json_encode([
        'ok'      => true,
        'skipped' => true,
        'message' => 'Chưa đủ 4 tiếng kể từ lần gửi trước (' . round($hoursSince, 1) . ' giờ trước).',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = coupon_send_email_batch('cron');
echo json_encode(['ok' => true, 'skipped' => false] + $result, JSON_UNESCAPED_UNICODE);
