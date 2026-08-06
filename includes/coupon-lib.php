<?php
/**
 * coupon-lib.php — Thư viện dùng chung cho hệ thống mã giảm giá tự động
 * (popup khi vào web + email định kỳ mỗi ~4 tiếng).
 *
 * Cần require config.php trước khi dùng (đã có hàm db()).
 */

/** Sinh 1 mã ngẫu nhiên dạng FSW-XXXXXX (chữ hoa + số, dễ đọc, khó nhầm 0/O, 1/I) */
function coupon_generate_code(int $len = 6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // bỏ 0,O,1,I cho dễ đọc
    $code = 'FSW-';
    for ($i = 0; $i < $len; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Tạo 1 mã giảm giá ngẫu nhiên (5–20%) và lưu vào DB.
 * $nguon: 'popup' | 'email' | 'admin_test' | 'manual'
 * $userId: gán riêng cho 1 user (email cá nhân hoá) — để null nếu là mã công khai (popup)
 * $expireDays: số ngày hết hạn kể từ lúc tạo
 */
function coupon_create(?int $userId = null, string $nguon = 'popup', int $expireDays = 7, ?int $fixedPercent = null): array {
    $pdo = db();
    $percent = $fixedPercent ?? random_int(5, 20);
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));

    // Thử tối đa 10 lần để tránh trùng mã (rất hiếm khi trùng)
    for ($i = 0; $i < 10; $i++) {
        $code = coupon_generate_code();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO coupons (ma_code, phan_tram_giam, nguon, nguoi_dung_id, ngay_het_han)
                VALUES (:code, :percent, :nguon, :uid, :exp)
            ");
            $stmt->execute([
                ':code'    => $code,
                ':percent' => $percent,
                ':nguon'   => $nguon,
                ':uid'     => $userId,
                ':exp'     => $expiresAt,
            ]);
            return ['code' => $code, 'percent' => $percent, 'expires_at' => $expiresAt];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) continue; // trùng mã — thử lại
            throw $e;
        }
    }
    throw new RuntimeException('Không thể tạo mã giảm giá (đụng trùng liên tục)');
}

/** Tài khoản này đã từng dùng bất kỳ mã giảm giá nào trước đây chưa (mỗi tài khoản chỉ được dùng 1 lần) */
function coupon_user_already_used(?int $userId): bool {
    if (!$userId) return false;
    $stmt = db()->prepare("
        SELECT COUNT(*) FROM orders
        WHERE nguoi_dung_id = :uid AND ma_giam_gia IS NOT NULL AND trang_thai <> 'huy'
    ");
    $stmt->execute([':uid' => $userId]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Kiểm tra 1 mã giảm giá có hợp lệ để áp dụng không (dùng khi checkout).
 * Trả về ['ok'=>true,'percent'=>int] hoặc ['ok'=>false,'error'=>string]
 * $userId: id của user đang checkout (null nếu khách chưa đăng nhập)
 */
function coupon_validate(string $code, ?int $userId): array {
    $code = strtoupper(trim($code));
    if ($code === '') return ['ok' => false, 'error' => 'Vui lòng nhập mã giảm giá'];

    // Mỗi tài khoản chỉ được dùng mã giảm giá (bất kỳ mã nào) đúng 1 lần
    if (coupon_user_already_used($userId)) {
        return ['ok' => false, 'error' => 'Tài khoản của bạn đã dùng mã giảm giá rồi — mỗi tài khoản chỉ được dùng 1 lần'];
    }

    // Mã tĩnh cũ (giữ tương thích ngược)
    if ($code === 'FIRST15') {
        return ['ok' => true, 'percent' => 15];
    }

    $stmt = db()->prepare("SELECT * FROM coupons WHERE ma_code = :code LIMIT 1");
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch();

    if (!$row) return ['ok' => false, 'error' => 'Mã giảm giá không tồn tại'];
    if ((int)$row['da_su_dung'] === 1) return ['ok' => false, 'error' => 'Mã giảm giá đã được sử dụng'];
    if (strtotime($row['ngay_het_han']) < time()) return ['ok' => false, 'error' => 'Mã giảm giá đã hết hạn'];
    if ($row['nguoi_dung_id'] !== null && (int)$row['nguoi_dung_id'] !== (int)$userId) {
        return ['ok' => false, 'error' => 'Mã giảm giá này không áp dụng cho tài khoản của bạn'];
    }

    return ['ok' => true, 'percent' => (int)$row['phan_tram_giam']];
}

/** Đánh dấu 1 mã (dynamic, không phải FIRST15) là đã dùng — gọi sau khi đặt hàng thành công */
function coupon_mark_used(string $code): void {
    $code = strtoupper(trim($code));
    if ($code === 'FIRST15' || $code === '') return;
    $stmt = db()->prepare("UPDATE coupons SET da_su_dung = 1 WHERE ma_code = :code");
    $stmt->execute([':code' => $code]);
}

/** Thời điểm lần gửi email hàng loạt gần nhất (mọi loại: cron hoặc admin_test), null nếu chưa từng gửi */
function coupon_last_cron_run(): ?string {
    $row = db()->query("SELECT sent_at FROM coupon_email_log ORDER BY sent_at DESC LIMIT 1")->fetch();
    return $row ? $row['sent_at'] : null;
}

/** Thời điểm lần chạy CRON (loại 'cron') gần nhất — dùng riêng để tự giới hạn 4 tiếng/lần trong cron/send-coupon-emails.php */
function coupon_last_true_cron_run(): ?string {
    $row = db()->query("SELECT sent_at FROM coupon_email_log WHERE loai='cron' ORDER BY sent_at DESC LIMIT 1")->fetch();
    return $row ? $row['sent_at'] : null;
}

/**
 * Gửi mã giảm giá qua email cho TẤT CẢ user đã đăng ký tài khoản.
 * Mỗi user nhận 1 mã riêng (gắn nguoi_dung_id) để không bị chia sẻ lung tung.
 * $loai: 'cron' (chạy định kỳ, có tự giới hạn) | 'admin_test' (admin bấm gửi tay, luôn gửi ngay)
 */
function coupon_send_email_batch(string $loai = 'cron'): array {
    require_once __DIR__ . '/mail.php';
    $pdo = db();
    $users = $pdo->query("SELECT id, ho_ten, email FROM users")->fetchAll();

    // 'cron' không phải giá trị hợp lệ của cột nguon (enum chỉ có popup/email/admin_test/manual)
    // nên map: cron -> 'email', admin_test -> 'admin_test' (giữ đúng nhãn hiển thị ở trang admin)
    $nguon = ($loai === 'admin_test') ? 'admin_test' : 'email';

    $sent = 0;
    $failed = 0;
    foreach ($users as $u) {
        if (empty($u['email'])) continue;
        try {
            $c = coupon_create((int)$u['id'], $nguon, 7);
            $ok = sendCouponEmail($u['email'], $u['ho_ten'], $c['code'], $c['percent'], $c['expires_at']);
            $ok ? $sent++ : $failed++;
        } catch (Throwable $e) {
            error_log('[CouponBatch] ' . $e->getMessage());
            $failed++;
        }
    }

    $log = $pdo->prepare("INSERT INTO coupon_email_log (so_luong_gui, loai) VALUES (:n, :loai)");
    $log->execute([':n' => $sent, ':loai' => $loai]);

    return ['sent' => $sent, 'failed' => $failed, 'total' => count($users)];
}
