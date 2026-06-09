<?php
/* api/products.php — JSON API cho filter sản phẩm */
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$ten   = trim($_GET['ten']   ?? '');  // tên danh mục tiếng Việt (từ homepage)
$slug  = trim($_GET['cat']   ?? '');  // slug danh mục (từ products page)
$q     = trim($_GET['q']     ?? '');  // từ khoá tìm kiếm
$limit = min((int)($_GET['limit'] ?? 8), 50);
$sort  = $_GET['sort'] ?? 'newest';

$where  = ["p.trang_thai = 'hien'"];
$params = [];

/* Filter theo tên danh mục (homepage dùng) */
if ($ten) {
    $where[]          = 'c.ten_danh_muc = :ten';
    $params[':ten']   = $ten;
}
/* Filter theo slug danh mục */
if ($slug) {
    $where[]          = 'c.slug = :slug';
    $params[':slug']  = $slug;
}
/* Tìm kiếm */
if ($q) {
    $where[]       = '(p.ten_san_pham LIKE :q OR p.mo_ta LIKE :q)';
    $params[':q']  = "%$q%";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderSQL = match($sort) {
    'price_asc'  => 'ORDER BY p.gia_ban ASC',
    'price_desc' => 'ORDER BY p.gia_ban DESC',
    'name'       => 'ORDER BY p.ten_san_pham ASC',
    default      => 'ORDER BY p.id DESC',
};

try {
    $sql = "
        SELECT p.*, c.ten_danh_muc, c.slug AS cat_slug
        FROM products p
        JOIN categories c ON c.id = p.danh_muc_id
        $whereSQL
        $orderSQL
        LIMIT :lim
    ";
    $stmt = db()->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();

    /* Tổng số */
    $cStmt = db()->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.danh_muc_id $whereSQL");
    foreach ($params as $k => $v) $cStmt->bindValue($k, $v);
    $cStmt->execute();
    $total = (int)$cStmt->fetchColumn();

    echo json_encode([
        'data'  => $products,
        'total' => $total,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'data' => []]);
}
