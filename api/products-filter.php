<?php
/* ══════════════════════════════════════════════════════════════════
   api/products-filter.php — Lọc/sắp xếp/tìm kiếm/PHÂN TRANG sản phẩm
   cho trang products.php mà KHÔNG cần tải lại trang (AJAX).

   GET  ?cat=<ten_danh_muc>&sort=asc|desc|new|pop&q=<từ khoá>&page=<n>

   Trả: { ok:true, html: "<article>...</article>...", pagination: "<nav>...",
          total: int, page: int, totalPages: int, empty: bool, q: string }

   HTML trả về được render bằng renderProductCard()/renderPagination() y hệt
   phía server-side, nên giao diện luôn đồng nhất — front-end chỉ cần thay
   nội dung .products + #productsPagination, không cần render lại ở JS.
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/product-card.php';
require_once __DIR__ . '/../includes/pagination.php';

header('Content-Type: application/json; charset=utf-8');

$filterCat = trim($_GET['cat'] ?? 'all');
$sort      = $_GET['sort'] ?? 'pop';
$searchQ   = trim($_GET['q'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
// Khoảng giá do khách tự nhập
$priceMin = (isset($_GET['pmin']) && $_GET['pmin'] !== '') ? max(0, (float)$_GET['pmin']) : null;
$priceMax = (isset($_GET['pmax']) && $_GET['pmax'] !== '') ? max(0, (float)$_GET['pmax']) : null;
if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
    [$priceMin, $priceMax] = [$priceMax, $priceMin];
}

try {
    $where = "WHERE p.trang_thai != 'an'";
    $params = [];

    if ($filterCat !== 'all' && $filterCat !== '') {
        $where .= " AND c.ten_danh_muc = :cat";
        $params[':cat'] = $filterCat;
    }
    if ($searchQ !== '') {
        $where .= " AND (p.ten_san_pham LIKE :q1 OR c.ten_danh_muc LIKE :q2)";
        $params[':q1'] = '%' . $searchQ . '%';
        $params[':q2'] = '%' . $searchQ . '%';
    }
    if ($priceMin !== null) { $where .= " AND p.gia_ban >= :pmin"; $params[':pmin'] = $priceMin; }
    if ($priceMax !== null) { $where .= " AND p.gia_ban <= :pmax"; $params[':pmax'] = $priceMax; }

    $orderMap = [
        'asc'  => 'p.gia_ban ASC',
        'desc' => 'p.gia_ban DESC',
        'new'  => 'p.id DESC',
        'pop'  => 'p.la_moi DESC, p.id DESC',
    ];
    $orderSql = ' ORDER BY ' . ($orderMap[$sort] ?? $orderMap['pop']);

    $countStmt = db()->prepare(
        "SELECT COUNT(*) FROM products p JOIN categories c ON p.danh_muc_id = c.id $where"
    );
    $countStmt->execute($params);
    $totalCount = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalCount / PRODUCTS_PAGE_SIZE));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * PRODUCTS_PAGE_SIZE;

    $sql = "SELECT p.*, c.ten_danh_muc,
                   COALESCE(r.avg_rating, 0) AS avg_rating,
                   COALESCE(r.rating_count, 0) AS rating_count
            FROM products p
            JOIN categories c ON p.danh_muc_id = c.id
            LEFT JOIN (
                SELECT product_id, ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS rating_count
                FROM product_reviews
                WHERE rating IS NOT NULL
                GROUP BY product_id
            ) r ON r.product_id = p.id
            $where
            $orderSql
            LIMIT :lim OFFSET :off";
    $stmt = db()->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', PRODUCTS_PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $dbProducts = $stmt->fetchAll();

    ob_start();
    if (empty($dbProducts)) {
        if ($searchQ !== '') {
            echo '<p class="empty-grid-msg">Không tìm thấy sản phẩm phù hợp với "' . e($searchQ) . '".</p>';
        } else {
            echo '<p class="empty-grid-msg">Không có sản phẩm nào trong danh mục này.</p>';
        }
    } else {
        foreach ($dbProducts as $p) renderProductCard($p, 'grid');
    }
    $html = ob_get_clean();

    // Build URL cho từng trang, giữ nguyên cat/sort/q/pmin/pmax hiện tại
    $pageUrlFn = function (int $p) use ($filterCat, $sort, $searchQ, $priceMin, $priceMax) {
        $qs = array_filter([
            'cat'  => $filterCat !== 'all' ? $filterCat : null,
            'sort' => $sort !== 'pop' ? $sort : null,
            'q'    => $searchQ !== '' ? $searchQ : null,
            'pmin' => $priceMin !== null ? (int)$priceMin : null,
            'pmax' => $priceMax !== null ? (int)$priceMax : null,
            'page' => $p > 1 ? $p : null,
        ], fn($v) => $v !== null && $v !== '');
        return 'products.php' . ($qs ? '?' . http_build_query($qs) : '');
    };
    ob_start();
    renderPagination($page, $totalPages, $pageUrlFn);
    $paginationHtml = ob_get_clean();

    echo json_encode([
        'ok'         => true,
        'html'       => $html,
        'pagination' => $paginationHtml,
        'total'      => $totalCount,
        'page'       => $page,
        'totalPages' => $totalPages,
        'empty'      => count($dbProducts) === 0,
        'q'          => $searchQ,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/products-filter.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Không tải được danh sách sản phẩm.']);
}
