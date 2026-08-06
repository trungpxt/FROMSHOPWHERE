<?php
/* ══════════════════════════════════════════════════════════════════
   api/reviews.php — Đánh giá sao + bình luận cho từng sản phẩm

   GET  ?product_id=ID
        -> { ok:true, avg: float, count: int, reviews: [ {..., replies:[...]} ] }

   POST action=add   { product_id, rating(1-5, bỏ qua nếu là reply), noi_dung, parent_id? }
   POST action=delete { id }   (chỉ admin)
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/notify.php';

header('Content-Type: application/json; charset=utf-8');
startSession();

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Lấy danh sách đánh giá + điểm trung bình ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if ($productId <= 0) respond(400, ['ok' => false, 'error' => 'Thiếu product_id']);

    try {
        // Điểm trung bình + số lượt đánh giá (chỉ tính bình luận gốc có sao)
        $avgStmt = db()->prepare(
            "SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS cnt
             FROM product_reviews WHERE product_id=:pid AND rating IS NOT NULL"
        );
        $avgStmt->execute([':pid' => $productId]);
        $avgRow = $avgStmt->fetch();

        // Toàn bộ bình luận + reply (kèm tên người dùng)
        $stmt = db()->prepare(
            "SELECT r.id, r.parent_id, r.rating, r.noi_dung, r.created_at, r.user_id,
                    u.ho_ten, u.vai_tro
             FROM product_reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :pid
             ORDER BY r.created_at ASC"
        );
        $stmt->execute([':pid' => $productId]);
        $rows = $stmt->fetchAll();

        // Gom nhóm: top-level trước, reply lồng vào theo parent_id
        $byId = [];
        foreach ($rows as $r) {
            $r['replies'] = [];
            $byId[$r['id']] = $r;
        }
        $topLevel = [];
        foreach ($byId as $id => $r) {
            if ($r['parent_id'] && isset($byId[$r['parent_id']])) {
                $byId[$r['parent_id']]['replies'][] = $r;
            }
        }
        foreach ($byId as $id => $r) {
            if (!$r['parent_id']) $topLevel[] = $r;
        }
        // Mới nhất lên đầu cho bình luận gốc
        usort($topLevel, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        respond(200, [
            'ok'      => true,
            'avg'     => $avgRow['avg_rating'] ? (float)$avgRow['avg_rating'] : 0,
            'count'   => (int)$avgRow['cnt'],
            'reviews' => array_values($topLevel),
            'is_admin' => isAdmin(),
            'user_id'  => $_SESSION['user_id'] ?? null,
        ]);
    } catch (Exception $e) {
        error_log('api/reviews.php GET error: ' . $e->getMessage());
        respond(500, ['ok' => false, 'error' => 'Lỗi tải đánh giá']);
    }
}

/* ── Thêm / Xoá đánh giá ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';

    if ($action === 'add') {
        if (!isLoggedIn()) respond(401, ['ok' => false, 'error' => 'Vui lòng đăng nhập để đánh giá.']);

        $productId = (int)($data['product_id'] ?? 0);
        $parentId  = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $noiDung   = trim((string)($data['noi_dung'] ?? ''));
        $rating    = $parentId ? null : (int)($data['rating'] ?? 0); // reply không có sao

        if ($productId <= 0) respond(400, ['ok' => false, 'error' => 'Thiếu sản phẩm']);
        if ($noiDung === '' || mb_strlen($noiDung) > 1000) {
            respond(400, ['ok' => false, 'error' => 'Nội dung không hợp lệ']);
        }
        if (!$parentId && ($rating < 1 || $rating > 5)) {
            respond(400, ['ok' => false, 'error' => 'Vui lòng chọn số sao từ 1-5']);
        }

        try {
            $userId = (int)$_SESSION['user_id'];

            db()->prepare(
                "INSERT INTO product_reviews (product_id, user_id, parent_id, rating, noi_dung)
                 VALUES (?,?,?,?,?)"
            )->execute([$productId, $userId, $parentId, $rating, $noiDung]);

            // Nếu là reply -> thông báo cho chủ bình luận gốc (nếu không phải tự trả lời mình)
            if ($parentId) {
                $parent = db()->prepare("SELECT user_id FROM product_reviews WHERE id=?");
                $parent->execute([$parentId]);
                $parentUserId = (int)($parent->fetchColumn() ?: 0);

                if ($parentUserId && $parentUserId !== $userId) {
                    $myName = $_SESSION['user_name'] ?? 'Một người dùng';
                    createNotification(
                        $parentUserId,
                        'danh_gia',
                        'Bình luận của bạn có phản hồi mới',
                        $myName . ' đã trả lời bình luận của bạn: "' . mb_substr($noiDung, 0, 80) . '"',
                        SITE_URL . '/product-demo.php?id=' . $productId . '#tabReviews'
                    );
                }
            }

            respond(200, ['ok' => true]);
        } catch (Exception $e) {
            error_log('api/reviews.php add error: ' . $e->getMessage());
            respond(500, ['ok' => false, 'error' => 'Không thể gửi đánh giá']);
        }
    }

    if ($action === 'delete') {
        if (!isAdmin()) respond(403, ['ok' => false, 'error' => 'Chỉ quản trị viên mới có quyền xoá']);
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) respond(400, ['ok' => false, 'error' => 'Thiếu id']);
        try {
            // Xoá cả các reply con nhờ FOREIGN KEY ... ON DELETE CASCADE
            db()->prepare("DELETE FROM product_reviews WHERE id=?")->execute([$id]);
            respond(200, ['ok' => true]);
        } catch (Exception $e) {
            error_log('api/reviews.php delete error: ' . $e->getMessage());
            respond(500, ['ok' => false, 'error' => 'Không thể xoá']);
        }
    }

    respond(400, ['ok' => false, 'error' => 'Hành động không hợp lệ']);
}

respond(405, ['ok' => false, 'error' => 'Method không được hỗ trợ']);
