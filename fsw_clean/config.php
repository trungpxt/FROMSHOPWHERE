<?php
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
define('SITE_URL',   'http://localhost/FROMSHOPWHERE');
define('IMG_URL',    SITE_URL . '/images');

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

/* ── Session ── */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
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
