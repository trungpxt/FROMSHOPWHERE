<?php
/* ══════════════════════════════════════════════════════════════
   includes/notify.php — Helper tạo thông báo cho khách hàng
   Dùng ở: admin/contacts.php (trả lời liên hệ), admin/orders.php
   (xác nhận đơn hàng), api/reviews.php (có người phản hồi bình luận)
═══════════════════════════════════════════════════════════════ */

/**
 * Tạo 1 thông báo mới cho user.
 * @param int    $userId  ID người nhận thông báo
 * @param string $loai    'lien_he' | 'don_hang' | 'danh_gia'
 * @param string $tieuDe  Tiêu đề ngắn gọn
 * @param string|null $noiDung Nội dung chi tiết (tuỳ chọn)
 * @param string|null $link    Đường dẫn khi bấm vào thông báo (tuỳ chọn)
 */
function createNotification(int $userId, string $loai, string $tieuDe, ?string $noiDung = null, ?string $link = null): void {
    if ($userId <= 0) return; // không có tài khoản để nhận thông báo -> bỏ qua
    try {
        db()->prepare(
            "INSERT INTO notifications (user_id, loai, tieu_de, noi_dung, link) VALUES (?,?,?,?,?)"
        )->execute([$userId, $loai, $tieuDe, $noiDung, $link]);
    } catch (Exception $e) {
        error_log('createNotification error: ' . $e->getMessage());
    }
}
