<?php
/* Chỉ hiện lỗi PHP chi tiết khi chạy trên máy local (XAMPP) — khi lên hosting thật
   (domain thật, HTTP_HOST khác "localhost"/"127.0.0.1"), tự động ẨN lỗi để không lộ
   đường dẫn file/lỗi truy vấn DB cho khách. Vẫn ghi log lỗi vào file để bạn tự xem được. */
$__isLocalDev = empty($_SERVER['HTTP_HOST']) || in_array(
    strtok($_SERVER['HTTP_HOST'], ':'),
    ['localhost', '127.0.0.1'],
    true
);
error_reporting(E_ALL);
ini_set('display_errors', $__isLocalDev ? 1 : 0);
ini_set('log_errors', 1);
date_default_timezone_set('Asia/Ho_Chi_Minh');
/* ═══════════════════════════════════
   FROMSHOPWHERE — config.php
   Cấu hình kết nối MySQL + helpers
═══════════════════════════════════ */

define('DB_HOST',    'localhost');
define('DB_NAME',    'FROMSHOPWHERE');
define('DB_USER',    'root');   // ← Đổi nếu cần
define('DB_PASS',    '');       // ← Đổi nếu cần
define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_PRODUCT_DIR', __DIR__ . '/images/products/');
define('UPLOAD_BLOG_DIR',    __DIR__ . '/images/blog/');
define('UPLOAD_AVATAR_DIR',  __DIR__ . '/images/avatars/');
define('UPLOAD_BLOG_DOCS_DIR', __DIR__ . '/uploads/blog-docs/'); // file .docx gốc nhúng trong bài blog
// SITE_URL tự nhận diện theo máy/domain đang chạy (thay vì ghi cứng "localhost/FROMSHOPWHERE"),
// để khi copy project sang máy khác, domain khác, hoặc đổi tên thư mục thì ảnh/link không bị vỡ.
// Khi chạy qua cron (CLI, không có $_SERVER['HTTP_HOST']) thì dùng lại giá trị mặc định bên dưới.
if (!empty($_SERVER['HTTP_HOST'])) {
    $__scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443 ? 'https' : 'http';
    // Chuẩn hoá dấu "\" (Windows) -> "/" ở CẢ HAI vế rồi mới so khớp, và cắt dấu "/" thừa ở cuối
    // DOCUMENT_ROOT trước khi so sánh — thiếu bước này trên Windows/XAMPP sẽ so khớp sai,
    // khiến SITE_URL bị ghép ra cả đường dẫn ổ đĩa (vd "http://localhost:C:/xampp/htdocs/...")
    // làm mất luôn CSS/JS của toàn bộ trang (kể cả admin).
    $__docRoot  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    $__dir      = str_replace('\\', '/', __DIR__);
    $__basePath = ($__docRoot !== '' && strpos($__dir, $__docRoot) === 0)
        ? rtrim(substr($__dir, strlen($__docRoot)), '/')
        : ''; // không khớp được (vd cấu hình server lạ) -> coi như project nằm ở gốc domain
    define('SITE_URL', $__scheme . '://' . $_SERVER['HTTP_HOST'] . $__basePath);
} else {
    define('SITE_URL', 'http://localhost/FROMSHOPWHERE'); // ← Đổi nếu chạy cron trên domain thật
}
define('IMG_URL',         SITE_URL . '/images');
define('IMG_PRODUCT_URL', SITE_URL . '/images/products');
define('IMG_BLOG_URL',    SITE_URL . '/images/blog');
define('IMG_UI_URL',      SITE_URL . '/images/ui');
define('IMG_AVATAR_URL',  SITE_URL . '/images/avatars');
define('BLOG_DOCS_URL',   SITE_URL . '/uploads/blog-docs');

// Cache-busting cho CSS: version tự đổi theo thời gian sửa file style.css,
// giúp trình duyệt luôn tải bản CSS mới nhất — tránh lỗi hiển thị do cache cũ
// (ví dụ: lỗi hiện 2 logo sáng/tối cùng lúc ở header/footer).
define('CSS_VER', @filemtime(__DIR__ . '/assets/css/style.css') ?: time());

/* ── Cron mã giảm giá ──
   Token bí mật để bảo vệ cron/send-coupon-emails.php (tránh ai cũng gọi được URL
   là gửi email hàng loạt được). Bên dưới đã là 1 chuỗi ngẫu nhiên mạnh sẵn rồi,
   nhưng khi đưa lên hosting thật, vẫn nên tự tạo chuỗi MỚI của riêng bạn (đảm bảo
   người khác từng thấy code này không đoán/dùng lại được) — chạy trong terminal:
   php -r "echo bin2hex(random_bytes(24));" rồi dán kết quả thay vào bên dưới. */
define('CRON_SECRET', 'cbd4fc041acd491a9edcde721f3afbf97a29962e2bc7bae2');

/* ── Nhắc đánh giá sau khi mua (review reminder) ──
   Số ngày chờ sau khi đơn hàng chuyển "hoàn thành" trước khi gửi email nhắc khách
   đánh giá sản phẩm. Dùng chung token CRON_SECRET để bảo vệ cron/send-review-reminders.php. */
define('REVIEW_REMINDER_DELAY_DAYS', 3);

/* ── Cấu hình Gmail SMTP (dùng chung toàn site) ──
   ⚠️ App Password bên dưới trước đây bị lặp lại (hardcode) ở 3 file khác nhau
   (mail-config.php, includes/mail.php, forgot-password.php) — giờ gộp về DUY NHẤT
   1 chỗ ở đây, 3 file kia chỉ còn đọc lại qua defined('MAIL_FROM').
   ⚠️ QUAN TRỌNG: vì mật khẩu này đã từng nằm rải rác nhiều nơi và có thể đã bị lộ
   (ví dụ nếu từng đẩy code lên GitHub public, hoặc chia sẻ file zip cho người khác),
   nên vào https://myaccount.google.com/apppasswords THU HỒI App Password cũ và
   tạo App Password MỚI, rồi thay giá trị bên dưới trước khi đưa site lên hosting thật. */
define('MAIL_FROM',      'iddd83715@gmail.com');
define('MAIL_FROM_NAME', 'FROMSHOPWHERE');
define('MAIL_PASSWORD',  'cnyibljkifbpwcds');
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);

/* ── Chatbot ──
   Trước đây chatbot gọi Google Gemini API (cần API key + quota + mạng ngoài,
   và chất lượng trả lời không ổn định). Giờ api/chatbot.php đã tự viết bộ
   trả lời dựa trên luật (rule-based), chạy hoàn toàn trên server, dùng đúng
   dữ liệu sản phẩm/giá thật trong database — không cần cấu hình gì thêm ở đây. */

/* ── Kết nối PDO singleton ── */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

/* ── Session ──
   SameSite=Lax + HttpOnly chặn phần lớn tấn công CSRF/XSS-cookie-theft ngay
   ở tầng trình duyệt (bổ trợ cho lớp csrfCheck() ở trên, không thay thế).
   Secure tự bật khi site chạy HTTPS, tắt khi chạy local HTTP (XAMPP) để
   không làm hỏng đăng nhập trên localhost. */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    startSession();
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'      => $_SESSION['user_id'],
        'ho_ten'  => $_SESSION['user_name'],
        'email'   => $_SESSION['user_email'],
        'vai_tro' => $_SESSION['user_role'],
    ];
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/* ── Chống giả mạo yêu cầu (CSRF) cho các form admin ──
   csrfField() in ra 1 input ẩn kèm mã token; csrfCheck() xác minh token đó
   ở đầu mỗi khối xử lý POST. Ngăn trường hợp 1 trang web độc hại khác âm
   thầm gửi request xoá/sửa dữ liệu thay mặt admin đang đăng nhập. */
function csrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function csrfCheck(): void {
    startSession();
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}

/* ── Helpers ── */
function fmtVND(float $n): string {
    return number_format($n, 0, ',', '.') . 'đ';
}

function redirect(string $url): void {
    header("Location: $url"); exit;
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Chỉ giữ chữ số SĐT */
function normalizePhonePhp(string $raw): string {
    return preg_replace('/\D/', '', $raw);
}

function isValidEmailPhp(string $email): bool {
    return (bool) preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i', trim($email));
}

/** SĐT di động VN: 10 số, đầu 03/05/07/08/09 */
function isValidVnPhonePhp(string $phone): bool {
    $digits = normalizePhonePhp($phone);
    return (bool) preg_match('/^0(3|5|7|8|9)\d{8}$/', $digits);
}
