<?php
/**
 * Helpers: tạo token & gửi email xác nhận đăng ký.
 */
require_once __DIR__ . '/mail.php';

function issueVerificationToken(int $userId, string $email): string
{
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (VERIFY_TOKEN_HOURS * 3600));

    $off = db()->prepare(
        "UPDATE email_verifications SET used = 1 WHERE user_id = :u AND used = 0"
    );
    $off->execute([':u' => $userId]);

    $ins = db()->prepare(
        "INSERT INTO email_verifications (user_id, email, token, expires_at)
         VALUES (:u, :e, :t, :x)"
    );
    $ins->execute([':u' => $userId, ':e' => $email, ':t' => $token, ':x' => $expires]);

    return $token;
}

function sendUserVerificationEmail(int $userId, string $email, string $name): bool
{
    $token = issueVerificationToken($userId, $email);
    return sendVerificationEmail($email, $name, $token);
}

function userEmailVerified(array $user): bool
{
    if (($user['vai_tro'] ?? '') === 'admin') {
        return true;
    }
    return !empty($user['email_verified']);
}
