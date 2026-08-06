<?php
/* ══════════════════════════════════════════════════════════════════
   api/chatbot-feedback.php — Ghi nhận đánh giá 👍/👎 của khách cho
   từng câu trả lời chatbot.

   Khác với chatbot_missed (chỉ ghi khi bot KHÔNG nhận diện được gì),
   bảng này ghi cả trường hợp bot đã trả lời (khớp được FAQ/sản phẩm)
   nhưng khách thấy CHƯA ĐÚNG Ý — một góc mù quan trọng mà log kia
   không thấy được. Xem kết quả tại admin/chatbot-log.php.

   Nhận: { message: string, reply: string, rating: 'tot'|'chua_tot' }
   Trả:  { ok:true }
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Chỉ chấp nhận POST']);
}

startSession();

/* Rate limit nhẹ dùng chung session key với chatbot.php để tránh spam */
$_SESSION['cb_fb_hits'] = $_SESSION['cb_fb_hits'] ?? [];
$now = time();
$_SESSION['cb_fb_hits'] = array_values(array_filter($_SESSION['cb_fb_hits'], fn($t) => $t > $now - 600));
if (count($_SESSION['cb_fb_hits']) >= 60) {
    respond(429, ['ok' => false, 'error' => 'Quá nhiều yêu cầu']);
}
$_SESSION['cb_fb_hits'][] = $now;

$data    = json_decode(file_get_contents('php://input'), true);
$message = trim((string)($data['message'] ?? ''));
$reply   = trim((string)($data['reply'] ?? ''));
$rating  = ($data['rating'] ?? '') === 'tot' ? 'tot' : 'chua_tot';

if ($message === '' || $reply === '') {
    respond(400, ['ok' => false, 'error' => 'Thiếu dữ liệu']);
}

try {
    db()->exec("CREATE TABLE IF NOT EXISTS chatbot_feedback (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        cau_hoi   VARCHAR(800) NOT NULL,
        tra_loi   TEXT NOT NULL,
        danh_gia  ENUM('tot','chua_tot') NOT NULL,
        thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db()->prepare("INSERT INTO chatbot_feedback (cau_hoi, tra_loi, danh_gia) VALUES (:c, :t, :d)")
        ->execute([':c' => mb_substr($message, 0, 800), ':t' => mb_substr($reply, 0, 2000), ':d' => $rating]);
} catch (Exception $e) {
    error_log('api/chatbot-feedback.php error: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'Lỗi lưu dữ liệu']);
}

respond(200, ['ok' => true]);
