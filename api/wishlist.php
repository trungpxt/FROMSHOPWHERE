<?php
/* ══════════════════════════════════════════════════════════════════
   api/wishlist.php — Danh sách sản phẩm yêu thích của người dùng

   GET  ?action=list        -> { ok:true, ids:[1,4,9] }              (id đăng nhập)
   POST action=toggle { product_id }
        -> { ok:true, in_wishlist:bool, count:int }
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
startSession();

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/* Tạo bảng nếu chưa có (giữ nguyên style tự khởi tạo bảng như contact_messages) */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS wishlist (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        product_id  INT NOT NULL,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_product (user_id, product_id),
        INDEX idx_user (user_id),
        CONSTRAINT fk_wl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_wl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

if (!isLoggedIn()) {
    respond(401, ['ok' => false, 'error' => 'Vui lòng đăng nhập để dùng danh sách yêu thích.']);
}
$userId = (int)$_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    if ($action === 'list') {
        $ids = db()->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $ids->execute([$userId]);
        respond(200, ['ok' => true, 'ids' => array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN))]);
    }
    respond(400, ['ok' => false, 'error' => 'Hành động không hợp lệ.']);
}

if ($method === 'POST') {
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($productId <= 0) respond(400, ['ok' => false, 'error' => 'Thiếu product_id.']);

    if ($action === 'toggle') {
        $chk = db()->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $chk->execute([$userId, $productId]);
        $existing = $chk->fetch();

        if ($existing) {
            db()->prepare("DELETE FROM wishlist WHERE id = ?")->execute([$existing['id']]);
            $inWishlist = false;
        } else {
            /* Kiểm tra sản phẩm tồn tại */
            $p = db()->prepare("SELECT id FROM products WHERE id = ?");
            $p->execute([$productId]);
            if (!$p->fetch()) respond(404, ['ok' => false, 'error' => 'Sản phẩm không tồn tại.']);

            db()->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)")
                ->execute([$userId, $productId]);
            $inWishlist = true;
        }

        $cs = db()->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $cs->execute([$userId]);
        $count = (int)$cs->fetchColumn();

        respond(200, ['ok' => true, 'in_wishlist' => $inWishlist, 'count' => $count]);
    }

    if ($action === 'remove') {
        db()->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?")
            ->execute([$userId, $productId]);
        $count = (int)db()->query("SELECT COUNT(*) FROM wishlist WHERE user_id = $userId")->fetchColumn();
        respond(200, ['ok' => true, 'count' => $count]);
    }

    respond(400, ['ok' => false, 'error' => 'Hành động không hợp lệ.']);
}

respond(405, ['ok' => false, 'error' => 'Phương thức không được hỗ trợ.']);
