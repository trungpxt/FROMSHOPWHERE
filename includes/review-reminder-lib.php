<?php
/**
 * review-reminder-lib.php — Thư viện nhắc khách đánh giá sản phẩm sau khi mua.
 *
 * Luồng hoạt động:
 *   1) Khi admin chuyển đơn hàng sang "hoàn thành" (admin/orders.php),
 *      reviewReminderSchedule() được gọi để đặt lịch gửi email nhắc đánh giá
 *      sau REVIEW_REMINDER_DELAY_DAYS ngày (mặc định 3 ngày — đủ thời gian
 *      để khách cài đặt & dùng thử phần mềm trước khi được hỏi cảm nhận).
 *   2) cron/send-review-reminders.php chạy định kỳ (khuyên mỗi 1-2 tiếng),
 *      tìm các lịch đã tới hạn và còn ở trạng thái "cho_gui" rồi gửi email.
 *
 * Cần require config.php + includes/mail.php trước khi dùng.
 */

/**
 * Đặt lịch gửi email nhắc đánh giá cho 1 đơn hàng.
 * Gọi ngay sau khi đơn chuyển sang "hoàn thành".
 * An toàn khi gọi nhiều lần cho cùng 1 đơn (UNIQUE KEY don_hang_id — ghi đè lịch cũ).
 */
function reviewReminderSchedule(int $orderId, int $userId): void {
    if ($orderId <= 0 || $userId <= 0) return;
    $delayDays = defined('REVIEW_REMINDER_DELAY_DAYS') ? REVIEW_REMINDER_DELAY_DAYS : 3;
    $scheduledAt = date('Y-m-d H:i:s', strtotime("+{$delayDays} days"));

    try {
        db()->prepare("
            INSERT INTO review_reminders (don_hang_id, nguoi_dung_id, scheduled_at, trang_thai)
            VALUES (:oid, :uid, :sched, 'cho_gui')
            ON DUPLICATE KEY UPDATE
                scheduled_at = VALUES(scheduled_at),
                trang_thai   = 'cho_gui',
                sent_at      = NULL
        ")->execute([':oid' => $orderId, ':uid' => $userId, ':sched' => $scheduledAt]);
    } catch (Throwable $e) {
        error_log('[ReviewReminder] schedule error: ' . $e->getMessage());
    }
}

/**
 * Huỷ lịch nhắc đánh giá của 1 đơn (vd. nếu đơn bị chuyển ngược về trạng thái khác).
 */
function reviewReminderCancel(int $orderId): void {
    try {
        db()->prepare("
            UPDATE review_reminders SET trang_thai = 'huy'
            WHERE don_hang_id = :oid AND trang_thai = 'cho_gui'
        ")->execute([':oid' => $orderId]);
    } catch (Throwable $e) {
        error_log('[ReviewReminder] cancel error: ' . $e->getMessage());
    }
}

/**
 * Gửi tất cả các email nhắc đánh giá đã tới hạn (scheduled_at <= NOW())
 * và còn ở trạng thái "cho_gui". Dùng bởi cron/send-review-reminders.php.
 * Trả về ['sent'=>int, 'failed'=>int, 'total'=>int]
 */
function reviewReminderSendDueBatch(): array {
    require_once __DIR__ . '/mail.php';
    require_once __DIR__ . '/notify.php';
    $pdo = db();

    $due = $pdo->prepare("
        SELECT id, don_hang_id, nguoi_dung_id
        FROM review_reminders
        WHERE trang_thai = 'cho_gui' AND scheduled_at <= NOW()
        ORDER BY scheduled_at ASC
        LIMIT 200
    ");
    $due->execute();
    $rows = $due->fetchAll();

    $sent = 0;
    $failed = 0;
    foreach ($rows as $row) {
        try {
            $ok = sendReviewReminderEmail((int)$row['don_hang_id']);
            if ($ok) {
                $pdo->prepare("
                    UPDATE review_reminders SET trang_thai='da_gui', sent_at=NOW() WHERE id=:id
                ")->execute([':id' => $row['id']]);

                createNotification(
                    (int)$row['nguoi_dung_id'],
                    'danh_gia',
                    'Đánh giá đơn hàng #' . $row['don_hang_id'],
                    'Bạn dùng sản phẩm ổn chứ? Dành ít phút đánh giá để giúp khách hàng khác nhé.',
                    SITE_URL . '/profile.php?tab=orders'
                );
                $sent++;
            } else {
                // Không đánh dấu đã gửi -> lần cron sau sẽ thử lại
                $failed++;
            }
        } catch (Throwable $e) {
            error_log('[ReviewReminderBatch] ' . $e->getMessage());
            $failed++;
        }
    }

    return ['sent' => $sent, 'failed' => $failed, 'total' => count($rows)];
}
