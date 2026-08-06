<?php
/* ══════════════════════════════════════════════════════════════════
   api/newsletter-subscribe.php — Đăng ký nhận email ưu đãi/khuyến mãi

   Tham khảo mô hình phổ biến ở các sàn bán key phần mềm quốc tế
   (Kinguin...): khách để lại email đổi lấy mã giảm giá, shop có thêm
   kênh remarketing riêng. Trả về mã giảm giá cố định làm phần thưởng
   đăng ký (không phải mã cá nhân hoá, chỉ để khuyến khích để lại email).

   Nhận: { email: string }
   Trả:  { ok:true, already:bool }
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

/* Rate limit nhẹ chống spam */
$_SESSION['nl_hits'] = $_SESSION['nl_hits'] ?? [];
$now = time();
$_SESSION['nl_hits'] = array_values(array_filter($_SESSION['nl_hits'], fn($t) => $t > $now - 600));
if (count($_SESSION['nl_hits']) >= 5) {
    respond(429, ['ok' => false, 'error' => 'Bạn thao tác quá nhanh, thử lại sau ít phút.']);
}
$_SESSION['nl_hits'][] = $now;

$data  = json_decode(file_get_contents('php://input'), true);
$email = trim((string)($data['email'] ?? ''));

if ($email === '' || !isValidEmailPhp($email)) {
    respond(400, ['ok' => false, 'error' => 'Email không hợp lệ']);
}

try {
    db()->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        email         VARCHAR(190) NOT NULL UNIQUE,
        subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $exists = db()->prepare("SELECT id FROM newsletter_subscribers WHERE email = :e");
    $exists->execute([':e' => $email]);
    $already = (bool) $exists->fetch();

    if (!$already) {
        db()->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:e)")
            ->execute([':e' => $email]);
    }
} catch (Exception $e) {
    error_log('api/newsletter-subscribe.php error: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'Lỗi lưu dữ liệu, vui lòng thử lại.']);
}

respond(200, ['ok' => true, 'already' => $already]);
