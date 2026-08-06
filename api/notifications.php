<?php
/* ══════════════════════════════════════════════════════════════════
   api/notifications.php — Thông báo cho chuông trên thanh điều hướng

   GET  -> { ok:true, unread: int, items: [ {id, loai, tieu_de, noi_dung,
             link, da_doc, created_at} ... ] }  (20 thông báo gần nhất)

   POST action=mark_read { id }     -> đánh dấu 1 thông báo đã đọc
   POST action=mark_all_read        -> đánh dấu tất cả đã đọc
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
startSession();

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn()) {
    respond(401, ['ok' => false, 'error' => 'Chưa đăng nhập']);
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = db()->prepare(
            "SELECT id, loai, tieu_de, noi_dung, link, da_doc, created_at
             FROM notifications WHERE user_id=:uid
             ORDER BY created_at DESC LIMIT 20"
        );
        $stmt->execute([':uid' => $userId]);
        $items = $stmt->fetchAll();

        $unreadStmt = db()->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id=:uid AND da_doc=0"
        );
        $unreadStmt->execute([':uid' => $userId]);
        $unread = (int)$unreadStmt->fetchColumn();

        respond(200, ['ok' => true, 'unread' => $unread, 'items' => $items]);
    } catch (Exception $e) {
        error_log('api/notifications.php GET error: ' . $e->getMessage());
        respond(500, ['ok' => false, 'error' => 'Lỗi tải thông báo']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';

    if ($action === 'mark_read') {
        $id = (int)($data['id'] ?? 0);
        try {
            db()->prepare("UPDATE notifications SET da_doc=1 WHERE id=:id AND user_id=:uid")
               ->execute([':id' => $id, ':uid' => $userId]);
            respond(200, ['ok' => true]);
        } catch (Exception $e) {
            respond(500, ['ok' => false, 'error' => 'Lỗi cập nhật']);
        }
    }

    if ($action === 'mark_all_read') {
        try {
            db()->prepare("UPDATE notifications SET da_doc=1 WHERE user_id=:uid AND da_doc=0")
               ->execute([':uid' => $userId]);
            respond(200, ['ok' => true]);
        } catch (Exception $e) {
            respond(500, ['ok' => false, 'error' => 'Lỗi cập nhật']);
        }
    }

    respond(400, ['ok' => false, 'error' => 'Hành động không hợp lệ']);
}

respond(405, ['ok' => false, 'error' => 'Method không được hỗ trợ']);
