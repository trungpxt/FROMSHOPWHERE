-- ── MIGRATION: Xác nhận email khi đăng ký ─────────────────────────────
-- Chạy 1 lần trong phpMyAdmin hoặc: mysql -u root FROMSHOPWHERE < add_email_verification.sql

ALTER TABLE `users`
  ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = đã xác nhận qua email' AFTER `vai_tro`;

-- Tài khoản cũ (đã dùng được) coi như đã xác nhận
UPDATE `users` SET `email_verified` = 1;

CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `email`      VARCHAR(150) NOT NULL,
  `token`      VARCHAR(64)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Token xác nhận email đăng ký (hết hạn sau 24 giờ)';
