<?php
/**
 * Gửi email qua Gmail SMTP (PHPMailer).
 */
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

if (!defined('MAIL_FROM')) {
    define('MAIL_FROM',      '');
    define('MAIL_FROM_NAME', 'FROMSHOPWHERE');
    define('MAIL_PASSWORD',  '');
    define('MAIL_HOST',      'smtp.gmail.com');
    define('MAIL_PORT',      587);
}
if (!defined('VERIFY_TOKEN_HOURS')) {
    define('VERIFY_TOKEN_HOURS', 24);
}

if (!function_exists('createMailer')) {
function createMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_FROM;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = 0;
    $mail->Timeout    = 8;   // giây — không để 1 lượt mua hàng bị treo cả phút nếu SMTP chậm/lỗi
    $mail->SMTPKeepAlive = false;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    return $mail;
}
}

function sendVerificationEmail(string $toEmail, string $toName, string $token): bool
{
    $link = SITE_URL . '/verify-email.php?token=' . urlencode($token);
    $hours = VERIFY_TOKEN_HOURS;
    $year = date('Y');

    $body = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 0">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
        <tr>
          <td style="background:#3B2FA0;padding:28px 40px;text-align:center">
            <h1 style="margin:0;color:#fff;font-size:22px">FROMSHOPWHERE</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 40px 24px">
            <h2 style="margin:0 0 16px;font-size:18px;color:#111">Xác nhận tài khoản</h2>
            <p style="margin:0 0 12px;color:#444;line-height:1.6">Xin chào <strong>{$toName}</strong>,</p>
            <p style="margin:0 0 24px;color:#444;line-height:1.6">
              Cảm ơn bạn đã đăng ký. Nhấn nút bên dưới để kích hoạt tài khoản — link có hiệu lực
              <strong>{$hours} giờ</strong>.
            </p>
            <table cellpadding="0" cellspacing="0" style="margin:0 auto 24px">
              <tr>
                <td style="background:#3B2FA0;border-radius:8px">
                  <a href="{$link}"
                     style="display:inline-block;padding:14px 36px;color:#fff;font-size:15px;
                            font-weight:700;text-decoration:none">Xác nhận email</a>
                </td>
              </tr>
            </table>
            <p style="margin:0 0 8px;color:#888;font-size:12px">Hoặc mở link:</p>
            <p style="margin:0;font-size:11px;word-break:break-all">
              <a href="{$link}" style="color:#3B2FA0">{$link}</a>
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f9fafb;padding:16px 40px;text-align:center;border-top:1px solid #e5e7eb">
            <p style="margin:0;color:#aaa;font-size:11px">© {$year} FROMSHOPWHERE</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    try {
        $mail = createMailer();
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = '[FROMSHOPWHERE] Xác nhận đăng ký tài khoản';
        $mail->Body    = $body;
        $mail->AltBody = "Xin chào $toName,\n\nXác nhận tài khoản tại:\n$link\n\n(hết hạn sau $hours giờ)\n\nFROMSHOPWHERE";
        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[VerifyEmail] PHPMailer: ' . $e->getMessage());
        return false;
    }
}

/**
 * Gửi email "Đặt hàng thành công" ngay sau khi đơn hàng được tạo
 * (gọi trong api/place-order.php sau khi commit transaction).
 */
function sendOrderPlacedEmail(int $orderId): bool
{
    try {
        $order = db()->prepare("
            SELECT o.*, u.ho_ten, u.email
            FROM orders o
            JOIN users u ON u.id = o.nguoi_dung_id
            WHERE o.id = :id
        ");
        $order->execute([':id' => $orderId]);
        $order = $order->fetch();
        if (!$order) return false;

        $items = db()->prepare("
            SELECT oi.*, p.ten_san_pham, p.phien_ban
            FROM order_items oi
            JOIN products p ON p.id = oi.san_pham_id
            WHERE oi.don_hang_id = :oid
        ");
        $items->execute([':oid' => $orderId]);
        $items = $items->fetchAll();

        $itemsHtml = '';
        foreach ($items as $it) {
            $itemsHtml .= "
            <div style='display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #f0f0f0;padding:10px 0'>
                <div>
                    <div style='font-size:14px;font-weight:700;color:#111'>" . e($it['ten_san_pham']) . "</div>
                    " . ($it['phien_ban'] ? "<div style='font-size:12px;color:#888;margin-top:2px'>Phiên bản: " . e($it['phien_ban']) . "</div>" : '') . "
                </div>
                <div style='font-size:13px;color:#555;white-space:nowrap;margin-left:12px'>" . fmtVND($it['don_gia']) . " × " . $it['so_luong'] . "</div>
            </div>";
        }

        $isVNPay = stripos($order['phuong_thuc_tt'], 'vnpay') !== false;
        $nextStepMsg = $isVNPay
            ? 'Vui lòng hoàn tất thanh toán qua VNPay. Bạn sẽ nhận thêm email xác nhận ngay khi thanh toán thành công.'
            : 'Vui lòng chuyển khoản theo mã QR đã hiển thị. Sau khi chúng tôi xác nhận thanh toán, license key sẽ được gửi tới email này.';

        $mail = createMailer();
        $mail->addAddress($order['email'], $order['ho_ten']);
        $mail->isHTML(true);
        $mail->Subject = "🛒 Xác nhận đặt hàng #$orderId — FROMSHOPWHERE";
        $mail->Body = "
        <div style='font-family:\"Be Vietnam Pro\",sans-serif;max-width:580px;margin:0 auto;background:#fff'>
          <div style='background:linear-gradient(135deg,#16123F,#3B2FA0);padding:28px 32px;border-radius:14px 14px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:22px;font-weight:800'>🛒 Đặt hàng thành công!</h1>
            <p style='color:rgba(225,252,246,.7);font-size:13px;margin:8px 0 0'>Cảm ơn bạn đã mua sắm tại FROMSHOPWHERE</p>
          </div>
          <div style='padding:28px 32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 14px 14px'>
            <p style='font-size:15px;color:#333;margin-top:0'>Xin chào <strong>" . e($order['ho_ten']) . "</strong>,</p>
            <p style='color:#555;font-size:14px;line-height:1.7'>Chúng tôi đã nhận được đơn hàng <strong>#$orderId</strong> của bạn với nội dung sau:</p>

            $itemsHtml

            <div style='background:#f8fafb;border-radius:10px;padding:16px;margin-top:16px'>
              <div style='display:flex;justify-content:space-between;font-size:13px;color:#555;margin-bottom:6px'>
                <span>Phương thức thanh toán</span><span>" . e($order['phuong_thuc_tt']) . "</span>
              </div>
              <div style='border-top:1px solid #e5e7eb;margin-top:8px;padding-top:10px;display:flex;justify-content:space-between'>
                <span style='font-weight:700;color:#111'>Tổng tiền</span>
                <span style='font-size:18px;font-weight:800;color:#3B2FA0'>" . fmtVND($order['tong_tien']) . "</span>
              </div>
            </div>

            <p style='font-size:13px;color:#555;line-height:1.7;margin-top:18px'>$nextStepMsg</p>

            <div style='text-align:center;margin-top:22px'>
              <a href='" . SITE_URL . "/profile.php?tab=orders' style='background:#3B2FA0;color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700;display:inline-block'>
                📋 Xem đơn hàng của tôi
              </a>
            </div>

            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'>
            <p style='font-size:13px;color:#888;margin:0;line-height:1.7'>
              Nếu bạn không thực hiện đơn hàng này, vui lòng liên hệ ngay
              <a href='" . SITE_URL . "/contact.php' style='color:#3B2FA0;font-weight:600'>trang hỗ trợ</a>.
            </p>
            <p style='font-size:12px;color:#aaa;margin-top:16px'>© " . date('Y') . " FROMSHOPWHERE</p>
          </div>
        </div>";
        $mail->AltBody = "Xin chào {$order['ho_ten']},\n\nChúng tôi đã nhận được đơn hàng #$orderId của bạn.\nTổng tiền: " . fmtVND($order['tong_tien']) . "\n\n$nextStepMsg\n\nFROMSHOPWHERE";

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[OrderPlaced] PHPMailer: ' . $e->getMessage());
        return false;
    } catch (Throwable $e) {
        error_log('[OrderPlaced] Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Gửi email "Nhắc đánh giá sản phẩm" cho 1 đơn hàng đã hoàn thành
 * (gọi bởi cron/send-review-reminders.php, vài ngày sau khi đơn chuyển "hoàn thành").
 * Liệt kê từng sản phẩm trong đơn kèm link thẳng tới tab đánh giá của sản phẩm đó.
 */
function sendReviewReminderEmail(int $orderId): bool
{
    try {
        $order = db()->prepare("
            SELECT o.*, u.ho_ten, u.email
            FROM orders o
            JOIN users u ON u.id = o.nguoi_dung_id
            WHERE o.id = :id
        ");
        $order->execute([':id' => $orderId]);
        $order = $order->fetch();
        if (!$order) return false;

        $items = db()->prepare("
            SELECT DISTINCT oi.san_pham_id, p.ten_san_pham, p.hinh_anh
            FROM order_items oi
            JOIN products p ON p.id = oi.san_pham_id
            WHERE oi.don_hang_id = :oid
        ");
        $items->execute([':oid' => $orderId]);
        $items = $items->fetchAll();
        if (empty($items)) return false;

        $itemsHtml = '';
        foreach ($items as $it) {
            $reviewLink = SITE_URL . '/product-demo.php?id=' . (int)$it['san_pham_id'] . '#tabReviews';
            $itemsHtml .= "
            <div style='display:flex;justify-content:space-between;align-items:center;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;margin-bottom:10px'>
                <div style='font-size:14px;font-weight:700;color:#111'>" . e($it['ten_san_pham']) . "</div>
                <a href='{$reviewLink}' style='background:#3B2FA0;color:#fff;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap;margin-left:12px'>⭐ Đánh giá ngay</a>
            </div>";
        }

        $mail = createMailer();
        $mail->addAddress($order['email'], $order['ho_ten']);
        $mail->isHTML(true);
        $mail->Subject = "⭐ Bạn thấy sản phẩm thế nào? — Đơn hàng #$orderId FROMSHOPWHERE";
        $mail->Body = "
        <div style='font-family:\"Be Vietnam Pro\",sans-serif;max-width:580px;margin:0 auto;background:#fff'>
          <div style='background:linear-gradient(135deg,#16123F,#3B2FA0);padding:28px 32px;border-radius:14px 14px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:22px;font-weight:800'>⭐ Sản phẩm dùng có ổn không?</h1>
            <p style='color:rgba(225,252,246,.7);font-size:13px;margin:8px 0 0'>Vài dòng đánh giá từ bạn giúp ích rất nhiều cho cộng đồng</p>
          </div>
          <div style='padding:28px 32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 14px 14px'>
            <p style='font-size:15px;color:#333;margin-top:0'>Xin chào <strong>" . e($order['ho_ten']) . "</strong>,</p>
            <p style='color:#555;font-size:14px;line-height:1.7'>Đơn hàng <strong>#$orderId</strong> của bạn đã hoàn thành được vài ngày. Bạn dành 1 phút chia sẻ trải nghiệm về (các) sản phẩm dưới đây được không?</p>

            $itemsHtml

            <p style='font-size:12px;color:#888;margin-top:20px;line-height:1.7'>Chỉ mất khoảng 1 phút — đánh giá của bạn giúp những khách hàng khác chọn được sản phẩm phù hợp hơn.</p>

            <hr style='border:none;border-top:1px solid #f0f0f0;margin:24px 0'>
            <p style='font-size:12px;color:#aaa;margin:0'>Nếu bạn đã đánh giá rồi, cứ bỏ qua email này nhé. Cảm ơn bạn đã ủng hộ FROMSHOPWHERE!</p>
            <p style='font-size:12px;color:#aaa;margin-top:16px'>© " . date('Y') . " FROMSHOPWHERE</p>
          </div>
        </div>";
        $mail->AltBody = "Xin chào {$order['ho_ten']},\n\nĐơn hàng #$orderId của bạn đã hoàn thành. Bạn hãy dành ít phút đánh giá sản phẩm đã mua tại " . SITE_URL . "/profile.php?tab=orders\n\nCảm ơn bạn!\nFROMSHOPWHERE";

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[ReviewReminder] PHPMailer: ' . $e->getMessage());
        return false;
    } catch (Throwable $e) {
        error_log('[ReviewReminder] Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Gửi email mã giảm giá cá nhân hoá cho 1 user (dùng bởi coupon_send_email_batch()
 * trong includes/coupon-lib.php — gọi định kỳ mỗi ~4 tiếng hoặc khi admin bấm gửi test).
 */
function sendCouponEmail(string $toEmail, string $toName, string $code, int $percent, string $expiresAt): bool
{
    try {
        $expiresLabel = date('d/m/Y H:i', strtotime($expiresAt));
        $mail = createMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = "🎁 Tặng bạn mã giảm giá {$percent}% — FROMSHOPWHERE";
        $mail->Body = "
        <div style='font-family:\"Be Vietnam Pro\",sans-serif;max-width:560px;margin:0 auto;background:#fff'>
          <div style='background:linear-gradient(135deg,#16123F,#3B2FA0);padding:28px 32px;border-radius:14px 14px 0 0;text-align:center'>
            <h1 style='color:#fff;margin:0;font-size:22px;font-weight:800'>🎁 Quà tặng dành cho bạn!</h1>
          </div>
          <div style='padding:32px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 14px 14px'>
            <p style='font-size:15px;color:#333;margin-top:0'>Xin chào <strong>" . e($toName) . "</strong>,</p>
            <p style='color:#555;font-size:14px;line-height:1.7'>FROMSHOPWHERE gửi tặng bạn một mã giảm giá <strong>{$percent}%</strong> cho đơn hàng tiếp theo:</p>
            <div style='background:#f8fafb;border:2px dashed #3B2FA0;border-radius:12px;padding:18px;text-align:center;margin:20px 0'>
              <div style='font-size:22px;font-weight:800;letter-spacing:2px;color:#3B2FA0'>{$code}</div>
              <div style='font-size:12px;color:#888;margin-top:6px'>Giảm {$percent}% · Có hiệu lực đến {$expiresLabel}</div>
            </div>
            <div style='text-align:center;margin-top:22px'>
              <a href='" . SITE_URL . "/products.php' style='background:#3B2FA0;color:#fff;padding:12px 28px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700;display:inline-block'>
                🛍️ Mua sắm ngay
              </a>
            </div>
            <p style='font-size:12px;color:#aaa;margin-top:24px;line-height:1.6'>Nhập mã trên vào ô \"Mã giảm giá\" ở trang thanh toán. Mã chỉ dùng được 1 lần và chỉ áp dụng cho tài khoản này.</p>
            <p style='font-size:12px;color:#aaa;margin-top:8px'>© " . date('Y') . " FROMSHOPWHERE</p>
          </div>
        </div>";
        $mail->AltBody = "Xin chào $toName,\n\nFROMSHOPWHERE tặng bạn mã giảm giá {$percent}%: {$code}\nCó hiệu lực đến {$expiresLabel}.\n\nMua sắm ngay tại: " . SITE_URL . "/products.php";
        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[CouponEmail] PHPMailer: ' . $e->getMessage());
        return false;
    } catch (Throwable $e) {
        error_log('[CouponEmail] Error: ' . $e->getMessage());
        return false;
    }
}
