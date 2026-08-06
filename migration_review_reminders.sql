-- ═══════════════════════════════════════════════════════════════
-- migration_review_reminders.sql
-- Chạy file này nếu DB của bạn đã tồn tại (không muốn import lại
-- fromshopwhere-full.sql). Bảng này cũng đã được gộp sẵn vào cuối
-- fromshopwhere-full.sql cho các lượt cài đặt mới.
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `review_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `don_hang_id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `trang_thai` enum('cho_gui','da_gui','huy') NOT NULL DEFAULT 'cho_gui',
  PRIMARY KEY (`id`),
  UNIQUE KEY `don_hang_id` (`don_hang_id`),
  KEY `idx_due` (`trang_thai`,`scheduled_at`),
  CONSTRAINT `review_reminders_order_fk` FOREIGN KEY (`don_hang_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `review_reminders_user_fk` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
