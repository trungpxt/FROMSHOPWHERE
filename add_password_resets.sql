-- ── MIGRATION: Thêm bảng password_resets ──────────────────────────────
-- Chạy file này 1 lần trong phpMyAdmin hoặc MySQL CLI
-- mysql -u root FROMSHOPWHERE < add_password_resets.sql
-- ───────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150) NOT NULL,
  `token`      VARCHAR(64)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Lưu token đặt lại mật khẩu (hết hạn sau 30 phút)';
