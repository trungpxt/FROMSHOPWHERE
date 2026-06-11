<?php
/**
 * vnpay-config.php — Cấu hình VNPay Sandbox
 * Thông tin từ: https://sandbox.vnpayment.vn/devreg/
 */

// ── Thông tin merchant (lấy từ tài khoản Sandbox VNPay) ──
define('VNP_TMN_CODE',   'DEMO1234');          // Terminal ID (thay bằng mã của bạn)
define('VNP_HASH_SECRET','RAOEXHYVSDDIIENYWSLDIIZTANXUXZFJ'); // Secret Key (thay bằng key của bạn)
define('VNP_URL',        'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNP_RETURN_URL',  SITE_URL . '/vnpay-return.php');
define('VNP_API_URL',    'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction');

/**
 * Tạo chữ ký HMAC-SHA512
 */
function vnpay_hash(string $data): string {
    return strtoupper(hash_hmac('sha512', $data, VNP_HASH_SECRET));
}

/**
 * Tạo URL thanh toán VNPay
 * @param int    $orderId   Mã đơn hàng
 * @param int    $amount    Số tiền (VNĐ, không có thập phân)
 * @param string $orderInfo Mô tả đơn hàng
 * @param string $ipAddr    IP khách hàng
 * @return string URL chuyển đến VNPay
 */
function vnpay_create_payment_url(int $orderId, int $amount, string $orderInfo, string $ipAddr): string {
    $params = [
        'vnp_Version'    => '2.1.0',
        'vnp_Command'    => 'pay',
        'vnp_TmnCode'    => VNP_TMN_CODE,
        'vnp_Amount'     => $amount * 100,           // VNPay yêu cầu nhân 100
        'vnp_CurrCode'   => 'VND',
        'vnp_TxnRef'     => $orderId . '_' . time(), // Mã giao dịch duy nhất
        'vnp_OrderInfo'  => $orderInfo,
        'vnp_OrderType'  => 'other',
        'vnp_Locale'     => 'vn',
        'vnp_ReturnUrl'  => VNP_RETURN_URL,
        'vnp_IpAddr'     => $ipAddr,
        'vnp_CreateDate' => date('YmdHis'),
        'vnp_ExpireDate' => date('YmdHis', strtotime('+15 minutes')),
    ];

    ksort($params);

    $hashData = '';
    $query    = '';
    foreach ($params as $key => $value) {
        $hashData .= ($hashData ? '&' : '') . urlencode($key) . '=' . urlencode($value);
        $query    .= ($query    ? '&' : '') . urlencode($key) . '=' . urlencode($value);
    }

    $secureHash = vnpay_hash($hashData);
    return VNP_URL . '?' . $query . '&vnp_SecureHash=' . $secureHash;
}

/**
 * Xác minh chữ ký callback từ VNPay
 * @param array $params Tham số từ $_GET
 * @return bool
 */
function vnpay_verify_signature(array $params): bool {
    $secureHash = $params['vnp_SecureHash'] ?? '';
    unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
    ksort($params);

    $hashData = '';
    foreach ($params as $key => $value) {
        if (str_starts_with($key, 'vnp_')) {
            $hashData .= ($hashData ? '&' : '') . urlencode($key) . '=' . urlencode($value);
        }
    }

    return strtoupper($secureHash) === vnpay_hash($hashData);
}
