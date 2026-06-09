<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

$ten   = trim($_GET['ten']  ?? '');
$slug  = trim($_GET['cat']  ?? '');
$q     = trim($_GET['q']    ?? '');
$limit = min((int)($_GET['limit'] ?? 8), 50);
$sort  = $_GET['sort'] ?? 'newest';

$where  = ["p.trang_thai = 'hien'"];
$params = [];

if ($ten)  { $where[] = 'c.ten_danh_muc = :ten';  $params[':ten']  = $ten; }
if ($slug) { $where[] = 'c.slug = :slug';          $params[':slug'] = $slug; }
if ($q)    { $where[] = '(p.ten_san_pham LIKE :q OR p.mo_ta LIKE :q)'; $params[':q'] = "%$q%"; }

$wSQL = 'WHERE ' . implode(' AND ', $where);
$oSQL = match($sort) {
    'price_asc'  => 'ORDER BY p.gia_ban ASC',
    'price_desc' => 'ORDER BY p.gia_ban DESC',
    default      => 'ORDER BY p.id DESC',
};

try {
    $stmt = db()->prepare("SELECT p.*, c.ten_danh_muc, c.slug AS cat_slug FROM products p JOIN categories c ON c.id=p.danh_muc_id $wSQL $oSQL LIMIT :lim");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();

    $cs = db()->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.danh_muc_id $wSQL");
    foreach ($params as $k => $v) $cs->bindValue($k, $v);
    $cs->execute();

    echo json_encode(['data' => $products, 'total' => (int)$cs->fetchColumn()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['data' => [], 'total' => 0, 'error' => $e->getMessage()]);
}
