<?php
require_once __DIR__ . '/coupon-lib.php';

/**
 * includes/referral.php — Hệ thống giới thiệu bạn bè (affiliate đơn giản)
 *
 * Cách hoạt động:
 * 1. Mỗi user có 1 mã giới thiệu riêng, sinh trực tiếp từ id (không cần cột DB mới)
 *    -> link giới thiệu: SITE_URL/login.php?ref=MÃ
 * 2. Khi đăng ký qua link đó, cột users.nguoi_gioi_thieu_id lưu lại ai đã giới thiệu.
 * 3. Khi đơn hàng ĐẦU TIÊN của người được giới thiệu chuyển sang đã thanh toán/hoàn thành,
 *    người giới thiệu được tặng 1 mã giảm giá 15% (dùng lại coupon_create() có sẵn).
 *    Bảng referral_rewards đảm bảo chỉ thưởng đúng 1 lần / người được giới thiệu.
 *
 * Cần require config.php + coupon-lib.php + notify.php trước khi dùng.
 */

const REFERRAL_REWARD_PERCENT     = 15;
const REFERRAL_WELCOME_PERCENT    = 10;
const REFERRAL_REWARD_EXPIRE_DAYS = 30;

/** Đảm bảo cột/bảng cần thiết đã tồn tại (idempotent, an toàn gọi nhiều lần). */
function referral_ensure_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $col = db()->query("SHOW COLUMNS FROM users LIKE 'nguoi_gioi_thieu_id'")->fetch();
        if (!$col) {
            db()->exec("ALTER TABLE users ADD COLUMN nguoi_gioi_thieu_id INT DEFAULT NULL AFTER vai_tro,
                        ADD CONSTRAINT fk_user_referrer FOREIGN KEY (nguoi_gioi_thieu_id) REFERENCES users(id) ON DELETE SET NULL");
        }
    } catch (Exception $e) {}
    try {
        db()->exec("CREATE TABLE IF NOT EXISTS referral_rewards (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id   INT NOT NULL,
            referred_id   INT NOT NULL UNIQUE,
            coupon_code   VARCHAR(20) NOT NULL,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_rr_referrer FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_rr_referred FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
}

/** Sinh mã giới thiệu ngắn, gọn, giải mã ngược được — không cần lưu DB riêng. */
function referral_code_for_user(int $userId): string {
    return 'F' . strtoupper(base_convert($userId, 10, 36));
}

/** Giải mã 1 mã giới thiệu về user id. Trả về null nếu sai định dạng. */
function referral_user_id_from_code(string $code): ?int {
    $code = strtoupper(trim($code));
    if ($code === '' || $code[0] !== 'F') return null;
    $n = base_convert(substr($code, 1), 36, 10);
    return ctype_digit((string)$n) && (int)$n > 0 ? (int)$n : null;
}

/** Gán người giới thiệu cho 1 user mới đăng ký (gọi ngay sau khi INSERT user thành công). */
function referral_attach(int $newUserId, ?string $refCode): void {
    if (!$refCode) return;
    referral_ensure_schema();
    $referrerId = referral_user_id_from_code($refCode);
    if (!$referrerId || $referrerId === $newUserId) return;

    $chk = db()->prepare("SELECT id FROM users WHERE id = ?");
    $chk->execute([$referrerId]);
    if (!$chk->fetch()) return; // mã không ứng với user thật nào

    db()->prepare("UPDATE users SET nguoi_gioi_thieu_id = ? WHERE id = ? AND nguoi_gioi_thieu_id IS NULL")
        ->execute([$referrerId, $newUserId]);

    // Tặng ngay mã chào mừng cho người mới (khuyến khích họ đăng ký qua link giới thiệu)
    try {
        coupon_create($newUserId, 'manual', REFERRAL_REWARD_EXPIRE_DAYS, REFERRAL_WELCOME_PERCENT);
    } catch (Exception $e) {
        error_log('[Referral] Welcome coupon error: ' . $e->getMessage());
    }
}

/**
 * Gọi khi 1 đơn hàng của $buyerId vừa chuyển sang da_thanh_toan/hoan_thanh.
 * Nếu đây là người được giới thiệu và chưa từng được thưởng -> tặng coupon cho người giới thiệu.
 */
function referral_maybe_reward(int $buyerId): void {
    referral_ensure_schema();
    try {
        $u = db()->prepare("SELECT nguoi_gioi_thieu_id FROM users WHERE id = ?");
        $u->execute([$buyerId]);
        $referrerId = $u->fetchColumn();
        if (!$referrerId) return;

        $exists = db()->prepare("SELECT id FROM referral_rewards WHERE referred_id = ?");
        $exists->execute([$buyerId]);
        if ($exists->fetch()) return; // đã thưởng rồi, không thưởng lại

        $coupon = coupon_create((int)$referrerId, 'manual', REFERRAL_REWARD_EXPIRE_DAYS, REFERRAL_REWARD_PERCENT);

        db()->prepare("INSERT INTO referral_rewards (referrer_id, referred_id, coupon_code) VALUES (?, ?, ?)")
            ->execute([(int)$referrerId, $buyerId, $coupon['code']]);

        if (function_exists('createNotification')) {
            createNotification(
                (int)$referrerId,
                'don_hang',
                '🎁 Bạn vừa nhận được mã giảm giá giới thiệu!',
                'Người bạn giới thiệu đã mua hàng thành công. Bạn được tặng mã ' . $coupon['code']
                    . ' giảm ' . REFERRAL_REWARD_PERCENT . '%, hạn dùng ' . REFERRAL_REWARD_EXPIRE_DAYS . ' ngày.',
                SITE_URL . '/profile.php'
            );
        }
    } catch (Exception $e) {
        error_log('[Referral] ' . $e->getMessage());
    }
}

/** Thống kê giới thiệu của 1 user, dùng cho tab "Giới thiệu bạn bè" trong profile.php */
function referral_stats(int $userId): array {
    referral_ensure_schema();
    $referred = db()->prepare(
        "SELECT u.id, u.ho_ten, u.email, u.ngay_tao,
                (rr.id IS NOT NULL) AS da_thuong, rr.coupon_code
         FROM users u
         LEFT JOIN referral_rewards rr ON rr.referred_id = u.id
         WHERE u.nguoi_gioi_thieu_id = ?
         ORDER BY u.ngay_tao DESC"
    );
    $referred->execute([$userId]);
    $list = $referred->fetchAll();

    return [
        'code'          => referral_code_for_user($userId),
        'link'          => SITE_URL . '/login.php?ref=' . referral_code_for_user($userId) . '#register',
        'total'         => count($list),
        'rewarded'      => count(array_filter($list, fn($r) => $r['da_thuong'])),
        'list'          => $list,
    ];
}
