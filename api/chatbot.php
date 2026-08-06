<?php
/* ══════════════════════════════════════════════════════════════════
   api/chatbot.php — Backend chatbot FROMSHOPWHERE (tự viết, KHÔNG gọi AI ngoài)

   Bản nâng cấp so với bản rule-based đầu tiên:
   - So khớp từ khoá theo RANH GIỚI TỪ (word-boundary) thay vì substring thô
     -> tránh lỗi khớp nhầm kiểu từ khoá "hi" khớp nhầm vào giữa chữ "chỉnh"
     (chinh -> chứa "hi").
   - Chịu được lỗi gõ nhẹ (fuzzy, Levenshtein) cho từ khoá đơn dài >= 5 ký tự,
     vd "thnah toan" vẫn nhận ra "thanh toan".
   - Nhận diện sản phẩm theo TRỌNG SỐ TỪ (không cần khách gõ đúng y hệt tên
     đầy đủ) — vd "win 11 pro gia sao" vẫn khớp được "Windows 11 Pro".
   - Thêm nhận diện lọc theo khoảng giá / rẻ nhất / đắt nhất / hàng mới.
   - Nhớ NGỮ CẢNH 1 lượt hội thoại gần nhất (sản phẩm vừa nhắc tới) để trả
     lời đúng cho câu hỏi nối tiếp kiểu "vậy giá bao nhiêu?", "còn hàng không?".
   - Bộ câu hỏi/trả lời (FAQ) giờ lưu trong bảng chatbot_faq thay vì hard-code
     trong file này — admin tự thêm/sửa/xoá qua admin/chatbot-faq.php, không
     cần sửa code mỗi lần muốn dạy thêm chatbot trả lời câu mới.
   - Câu hỏi bot KHÔNG hiểu được tự ghi log vào chatbot_missed để admin xem
     lại và biết nên bổ sung FAQ nào (xem admin/chatbot-log.php).

   Thứ tự ưu tiên xử lý 1 câu hỏi:
   1. Khớp sản phẩm cụ thể theo tên (trọng số từ)
   2. Lọc theo khoảng giá / rẻ nhất / đắt nhất / hàng mới
   3. Khớp danh mục sản phẩm
   4. Nhận diện ý định (intent) theo điểm số từ khoá
   5. Câu hỏi nối tiếp dựa trên ngữ cảnh lượt trước (chỉ khi 4 bước trên
      đều không khớp — tránh lấn át các ý định rõ ràng hơn)
   6. Trả lời mặc định + gợi ý liên hệ nhân viên

   Nhận: { message: string, history: [...] }  (history không dùng, giữ lại
          tham số để không phải sửa front-end assets/js/chatbot.js)
   Trả:  { ok:true, reply: string }
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Chỉ chấp nhận POST']);
}

startSession();

/* ── Rate limit đơn giản theo session: tối đa 30 tin nhắn / 10 phút ── */
$_SESSION['cb_hits'] = $_SESSION['cb_hits'] ?? [];
$now = time();
$_SESSION['cb_hits'] = array_values(array_filter(
    $_SESSION['cb_hits'],
    fn($t) => $t > $now - 600
));
if (count($_SESSION['cb_hits']) >= 30) {
    respond(429, ['ok' => false, 'error' => 'Bạn gửi tin nhắn quá nhanh, vui lòng thử lại sau ít phút.']);
}

/* ── Đọc & validate input ── */
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$message = trim((string)($data['message'] ?? ''));

if ($message === '') {
    respond(400, ['ok' => false, 'error' => 'Tin nhắn trống']);
}
if (mb_strlen($message) > 800) {
    respond(400, ['ok' => false, 'error' => 'Tin nhắn quá dài']);
}

$_SESSION['cb_hits'][] = $now;

/* ── Tự tạo bảng lưu câu hỏi bot CHƯA HIỂU (để admin xem & bổ sung từ khoá
      sau này — xem admin/chatbot-log.php) ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS chatbot_missed (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        cau_hoi       VARCHAR(800) NOT NULL,
        cau_hoi_chuan VARCHAR(500) NOT NULL,
        thoi_gian     DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chuan (cau_hoi_chuan(191))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/* ── Bảng FAQ của chatbot — admin có thể tự thêm/sửa/xoá qua
      admin/chatbot-faq.php mà KHÔNG cần sửa code. Nếu bảng vừa được tạo
      (rỗng), tự seed sẵn bộ câu hỏi/trả lời mặc định để chatbot vẫn hoạt
      động ngay từ đầu. ── */
try {
    db()->exec("CREATE TABLE IF NOT EXISTS chatbot_faq (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        tu_khoa    VARCHAR(600) NOT NULL COMMENT 'các từ khoá/cụm từ, cách nhau bởi dấu phẩy',
        tra_loi    TEXT NOT NULL,
        kich_hoat  TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $faqCount = (int) db()->query("SELECT COUNT(*) FROM chatbot_faq")->fetchColumn();
    if ($faqCount === 0) {
        $seed = [
            ['xin chao, chao ban, chao shop, hello, alo',
             'Xin chào! Mình là trợ lý ảo của FROMSHOPWHERE 👋 Bạn cần hỗ trợ gì hôm nay? (giao hàng, thanh toán, bảo hành, cách mua hàng, tài khoản...)'],
            ['cam on, thank you, thanks, tks',
             'Không có gì, rất vui được hỗ trợ bạn 😊 Còn câu hỏi nào khác không?'],
            ['giao hang, giao key, nhan key, nhan duoc key, gui key, bao lau nhan duoc, thoi gian giao, khi nao nhan duoc, chua thay email',
             "Sau khi thanh toán thành công, hệ thống tự động gửi license key qua email bạn đăng ký, thường chỉ trong vài giây đến vài phút, không cần chờ nhân viên duyệt thủ công.\nNếu chưa thấy email, bạn kiểm tra thêm mục Spam/Quảng cáo, hoặc xem lại key trong tab \"Đơn hàng\" ở trang Tài khoản."],
            ['thanh toan, phuong thuc thanh toan, vnpay, momo, zalopay, chuyen khoan, visa, mastercard, the tin dung, vietqr, quet ma qr',
             'FROMSHOPWHERE hỗ trợ thanh toán qua VNPay, thẻ Visa/Mastercard, MoMo, ZaloPay, VietQR và chuyển khoản ngân hàng. Bạn chọn phương thức phù hợp ngay ở trang thanh toán sau khi đặt hàng.'],
            ['ma giam gia, khuyen mai, giam gia, coupon, sale, voucher, uu dai',
             'Bạn có thể nhập mã giảm giá ở ô "Mã giảm giá" ngay trang thanh toán. Nếu chưa có mã, thử mã FIRST15 (giảm 15% cho đơn đầu tiên) hoặc liên hệ đội hỗ trợ để biết chương trình khuyến mãi mới nhất.'],
            ['trang thai don, don hang toi, kiem tra don, don hang cua toi, theo doi don hang',
             "Đơn hàng có các trạng thái: \"Chờ xử lý\" (mới đặt) → \"Đã thanh toán\" → \"Hoàn thành\" (đã giao key) — hoặc \"Đã hủy\" nếu đơn bị hủy.\nBạn xem chi tiết/trạng thái đơn hàng tại tab \"Đơn hàng\" trong trang Tài khoản."],
            ['bao hanh, doi tra, hoan tien, key loi, key die, key het han, refund, key bi thu hoi, khong dung duoc key',
             'Nếu license key gặp lỗi hoặc bị thu hồi không do lỗi từ phía bạn, bạn sẽ được cấp lại hoặc đổi key mới miễn phí. Gửi yêu cầu chi tiết tại trang Liên hệ, đội hỗ trợ sẽ xử lý nhanh nhất có thể.'],
            ['cach mua, huong dan mua, lam sao de mua, dat hang the nao, mua hang o dau',
             'Rất đơn giản: chọn phần mềm cần mua → bấm "Thêm vào giỏ" hoặc "Mua ngay" → điền thông tin và chọn phương thức thanh toán → hoàn tất đơn hàng. Key sẽ được gửi ngay qua email sau khi thanh toán thành công.'],
            ['cai dat, huong dan cai, cach kich hoat, nhap key, kich hoat ban quyen, activate, kich hoat the nao',
             'Sau khi nhận key qua email, bạn mở phần mềm tương ứng → chọn mục "Nhập mã bản quyền / Activate" → dán key vào là xong. Nếu cần, đội kỹ thuật hỗ trợ cài đặt từ xa miễn phí.'],
            ['dang nhap, tai khoan, quen mat khau, doi mat khau, dang ky, xac thuc email',
             'Bạn đăng nhập/đăng ký ở góc phải thanh điều hướng. Quên mật khẩu thì chọn "Quên mật khẩu" ngay trang đăng nhập để nhận link đặt lại qua email. Sau khi đăng ký cần xác thực email trước khi dùng đầy đủ tính năng.'],
            ['so sanh, compare',
             'Bạn có thể so sánh nhiều sản phẩm cạnh nhau (giá, tính năng...) ngay tại trang So sánh (compare.php) — chọn thêm sản phẩm muốn so sánh từ trang Sản phẩm nhé.'],
            ['bao mat thong tin, lo thong tin, chinh sach bao mat, an toan khong, co lua dao khong',
             'Thông tin thanh toán được xử lý qua cổng VNPay đạt chuẩn bảo mật, FROMSHOPWHERE không lưu trữ số thẻ của bạn. Thông tin cá nhân chỉ dùng để giao key và hỗ trợ, không chia sẻ cho bên thứ ba. Chi tiết xem tại trang Chính sách bảo mật.'],
            ['gap nhan vien, nguoi that, tu van vien, hotline, zalo, lien he, so dien thoai, noi chuyen voi ai do',
             "Bạn có thể liên hệ đội ngũ hỗ trợ qua:\n📧 support@fromshopwhere.com\n☎️ Hotline miễn phí: 1900 1234 (8:00–22:00 mỗi ngày)\n💬 Zalo OA: FROMSHOPWHERE Official\nHoặc để lại lời nhắn tại trang Liên hệ, đội ngũ phản hồi trong khoảng 2 giờ."],
            ['gia bao nhieu, bao nhieu tien, gia ca, gia the nao',
             'Giá phần mềm được niêm yết trực tiếp trên trang Sản phẩm, luôn kèm giá gốc để bạn dễ so sánh. Bạn cho mình biết tên phần mềm cụ thể, mình báo giá chính xác luôn nhé!'],
            ['fromshopwhere la gi, shop nay ban gi, ban phan mem that khong, co phai crack khong, key that khong, ban quyen that khong',
             'FROMSHOPWHERE là cửa hàng chuyên bán license key phần mềm bản quyền chính hãng (không phải crack) với giá tốt hơn mua trực tiếp từ hãng, nhờ nguồn key OEM/Volume Licensing hợp pháp. Danh mục hiện có: Thiết kế, Văn phòng, Video, Bảo mật.'],
        ];
        $ins = db()->prepare("INSERT INTO chatbot_faq (tu_khoa, tra_loi) VALUES (:k, :r)");
        foreach ($seed as [$kw, $reply]) {
            $ins->execute([':k' => $kw, ':r' => $reply]);
        }
    }
} catch (Exception $e) {}



/* ══════════════════════════════════════════════════════════════════
   TIỆN ÍCH XỬ LÝ CHUỖI
═══════════════════════════════════════════════════════════════════ */

/** Bỏ dấu tiếng Việt, về chữ thường */
function stripAccentsVi(string $str): string {
    $str = mb_strtolower($str, 'UTF-8');
    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a',
        'ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o',
        'ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d',
    ];
    return strtr($str, $map);
}

/** Chuẩn hoá: bỏ dấu, bỏ ký tự lạ, gộp khoảng trắng — dùng để so khớp */
function normalizeQuery(string $str): string {
    $s = stripAccentsVi($str);
    $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
    return preg_replace('/\s+/', ' ', trim($s));
}

/** Cụm từ ($phrase, có thể nhiều từ) có xuất hiện NGUYÊN VẸN trong $text
 *  (đã chuẩn hoá) hay không — khớp theo ranh giới từ, không khớp giữa chừng
 *  một từ khác (vd "hi" không được khớp nhầm vào trong "chinh"). */
function phraseInText(string $text, string $phrase): bool {
    $pattern = '/(?<![a-z0-9])' . preg_quote($phrase, '/') . '(?![a-z0-9])/';
    return (bool) preg_match($pattern, $text);
}

/** Chịu lỗi gõ nhẹ cho 1 từ đơn (không áp dụng cho cụm nhiều từ, và chỉ áp
 *  dụng với từ đủ dài để tránh khớp nhầm lung tung). */
function fuzzyWordInText(string $text, string $word): bool {
    if (mb_strlen($word) < 5) return false;
    foreach (preg_split('/\s+/', $text) as $w) {
        if ($w === '' || abs(strlen($w) - strlen($word)) > 2) continue;
        if (levenshtein($w, $word) <= 1) return true;
    }
    return false;
}

/** So khớp 1 từ khoá (đơn hoặc cụm) trong văn bản đã chuẩn hoá */
function keywordMatch(string $text, string $kw): bool {
    if (phraseInText($text, $kw)) return true;
    if (strpos($kw, ' ') === false) return fuzzyWordInText($text, $kw);
    return false;
}

function vnAmountToNumber(string $numStr, ?string $unit): ?float {
    $numStr = str_replace(',', '.', $numStr);
    $n = (float) $numStr;
    if ($unit === null) return $n >= 1000 ? $n : null; // số trần quá nhỏ -> mơ hồ, bỏ qua
    if (in_array($unit, ['k', 'nghin', 'ngan'], true)) return $n * 1000;
    if (in_array($unit, ['trieu', 'tr'], true)) return $n * 1000000;
    return $n;
}

$q = normalizeQuery($message);

/* ══════════════════════════════════════════════════════════════════
   LẤY DỮ LIỆU SẢN PHẨM/DANH MỤC TỪ DATABASE
═══════════════════════════════════════════════════════════════════ */
$products = [];
try {
    $stmt = db()->query(
        "SELECT p.id, p.ten_san_pham, p.mo_ta, p.gia_goc, p.gia_ban, p.trang_thai, p.la_moi,
                c.ten_danh_muc, c.id AS cat_id
         FROM products p LEFT JOIN categories c ON c.id = p.danh_muc_id
         WHERE p.trang_thai != 'an' ORDER BY p.id DESC"
    );
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('api/chatbot.php product query error: ' . $e->getMessage());
}

function productReplyText(array $p): string {
    $gia = fmtVND((float)$p['gia_ban']);
    $lines = [];
    if ($p['trang_thai'] === 'het_hang') {
        $lines[] = $p['ten_san_pham'] . ' hiện đang TẠM HẾT HÀNG, giá niêm yết ' . $gia . '.';
        $lines[] = 'Bạn để lại thông tin ở trang Liên hệ để được báo ngay khi có hàng trở lại nhé.';
    } else {
        $goc = (!empty($p['gia_goc']) && (float)$p['gia_goc'] > (float)$p['gia_ban'])
            ? ' (giá gốc ' . fmtVND((float)$p['gia_goc']) . ')' : '';
        $lines[] = $p['ten_san_pham'] . ': ' . $gia . $goc . '.';
        if (!empty($p['ten_danh_muc'])) $lines[] = 'Thuộc danh mục: ' . $p['ten_danh_muc'] . '.';
        if (!empty(trim((string)$p['mo_ta']))) {
            $mota = trim((string)$p['mo_ta']);
            if (mb_strlen($mota) > 200) $mota = mb_substr($mota, 0, 200) . '...';
            $lines[] = $mota;
        }
        $lines[] = 'Bạn có thể xem chi tiết và đặt mua trên trang Sản phẩm.';
    }
    return implode("\n", $lines);
}

/* ══════════════════════════════════════════════════════════════════
   BƯỚC 1: Khớp SẢN PHẨM cụ thể theo trọng số từ (không cần gõ đúng y hệt)
═══════════════════════════════════════════════════════════════════ */
function tokenizeName(string $name): array {
    $norm = normalizeQuery($name);
    return array_values(array_filter(explode(' ', $norm), fn($w) => $w !== ''));
}

$matchedProduct = null;
$bestScore = 0;
foreach ($products as $p) {
    $tokens = tokenizeName($p['ten_san_pham']);
    if (!$tokens) continue;

    $matchedCount = 0; $score = 0; $hasDistinctive = false;
    foreach ($tokens as $t) {
        $isDistinctive = mb_strlen($t) >= 4 || ctype_digit($t);
        if (phraseInText($q, $t)) {
            $matchedCount++;
            $score += ctype_digit($t) ? 4 : mb_strlen($t);
            if ($isDistinctive) $hasDistinctive = true;
        }
    }
    $ratio = $matchedCount / count($tokens);

    $ok = (count($tokens) === 1)
        ? ($ratio === 1.0 && mb_strlen($tokens[0]) >= 4)
        : ($ratio >= 0.6 && $hasDistinctive);

    if ($ok && $score > $bestScore) {
        $bestScore = $score;
        $matchedProduct = $p;
    }
}

if ($matchedProduct) {
    $_SESSION['cb_last_product'] = $matchedProduct['id'];
    respond(200, ['ok' => true, 'reply' => productReplyText($matchedProduct)]);
}

/* ══════════════════════════════════════════════════════════════════
   BƯỚC 2: Lọc theo khoảng giá / rẻ nhất / đắt nhất / hàng mới
═══════════════════════════════════════════════════════════════════ */
$visibleProducts = array_values(array_filter($products, fn($p) => $p['trang_thai'] !== 'het_hang'));

function formatProductList(array $list, string $intro): string {
    $lines = [$intro];
    foreach (array_slice($list, 0, 5) as $p) {
        $lines[] = '• ' . $p['ten_san_pham'] . ' — ' . fmtVND((float)$p['gia_ban']);
    }
    $lines[] = 'Xem đầy đủ tại trang Sản phẩm nhé.';
    return implode("\n", $lines);
}

if (preg_match('/\b(re nhat|thap nhat|gia re nhat)\b/', $q)) {
    $list = $visibleProducts;
    usort($list, fn($a, $b) => (float)$a['gia_ban'] <=> (float)$b['gia_ban']);
    if ($list) respond(200, ['ok' => true, 'reply' => formatProductList($list, 'Các sản phẩm giá tốt nhất hiện có:')]);
}

if (preg_match('/\b(dat nhat|mac nhat|cao nhat|cao cap nhat)\b/', $q)) {
    $list = $visibleProducts;
    usort($list, fn($a, $b) => (float)$b['gia_ban'] <=> (float)$a['gia_ban']);
    if ($list) respond(200, ['ok' => true, 'reply' => formatProductList($list, 'Các sản phẩm cao cấp nhất hiện có:')]);
}

if (preg_match('/\b(hang moi|moi ve|moi ra mat|san pham moi)\b/', $q)) {
    $list = array_values(array_filter($visibleProducts, fn($p) => !empty($p['la_moi'])));
    if ($list) respond(200, ['ok' => true, 'reply' => formatProductList($list, 'Các sản phẩm mới ra mắt gần đây:')]);
}

if (preg_match('/\b(ban chay|best seller|nhieu nguoi mua|pho bien nhat)\b/', $q)) {
    try {
        $rows = db()->query(
            "SELECT p.ten_san_pham, p.gia_ban, SUM(oi.so_luong) AS qty_sold
             FROM order_items oi
             JOIN orders o   ON o.id = oi.don_hang_id
             JOIN products p ON p.id = oi.san_pham_id
             WHERE o.trang_thai IN ('da_thanh_toan','hoan_thanh') AND p.trang_thai != 'an'
             GROUP BY p.id, p.ten_san_pham, p.gia_ban
             ORDER BY qty_sold DESC LIMIT 5"
        )->fetchAll();
    } catch (Exception $e) {
        $rows = [];
    }
    if ($rows) {
        $lines = ['Các sản phẩm bán chạy nhất hiện tại:'];
        foreach ($rows as $r) {
            $lines[] = '• ' . $r['ten_san_pham'] . ' — ' . fmtVND((float)$r['gia_ban']) . ' (đã bán ' . (int)$r['qty_sold'] . ')';
        }
        $lines[] = 'Xem thêm chi tiết tại trang Sản phẩm nhé.';
        respond(200, ['ok' => true, 'reply' => implode("\n", $lines)]);
    }
}


if (preg_match('/\b(duoi|toi da|khong qua)\b\D{0,15}(\d+(?:[.,]\d+)?)\s*(k|nghin|ngan|trieu|tr)?/', $q, $m)) {
    $max = vnAmountToNumber($m[2], $m[3] ?: null);
    if ($max !== null) {
        $list = array_values(array_filter($visibleProducts, fn($p) => (float)$p['gia_ban'] <= $max));
        usort($list, fn($a, $b) => (float)$a['gia_ban'] <=> (float)$b['gia_ban']);
        respond(200, ['ok' => true, 'reply' => $list
            ? formatProductList($list, 'Các sản phẩm giá dưới ' . fmtVND($max) . ':')
            : 'Hiện chưa có sản phẩm nào giá dưới ' . fmtVND($max) . '. Bạn xem toàn bộ giá tại trang Sản phẩm nhé.']);
    }
}

if (preg_match('/\b(tren|toi thieu|it nhat)\b\D{0,15}(\d+(?:[.,]\d+)?)\s*(k|nghin|ngan|trieu|tr)?/', $q, $m)) {
    $min = vnAmountToNumber($m[2], $m[3] ?: null);
    if ($min !== null) {
        $list = array_values(array_filter($visibleProducts, fn($p) => (float)$p['gia_ban'] >= $min));
        usort($list, fn($a, $b) => (float)$a['gia_ban'] <=> (float)$b['gia_ban']);
        respond(200, ['ok' => true, 'reply' => $list
            ? formatProductList($list, 'Các sản phẩm giá trên ' . fmtVND($min) . ':')
            : 'Hiện chưa có sản phẩm nào giá trên ' . fmtVND($min) . '. Bạn xem toàn bộ giá tại trang Sản phẩm nhé.']);
    }
}

/* ══════════════════════════════════════════════════════════════════
   BƯỚC 3: Khớp DANH MỤC sản phẩm
═══════════════════════════════════════════════════════════════════ */
$categoryKeywords = [
    'thiet ke'  => ['thiet ke', 'do hoa', 'design'],
    'van phong' => ['van phong', 'office', 'word excel', 'microsoft office'],
    'video'     => ['dung phim', 'chinh sua video', 'edit video', 'video'],
    'bao mat'   => ['bao mat', 'diet virus', 'antivirus', 'security'],
];

$matchedCatKeyword = null;
foreach ($categoryKeywords as $catLabel => $kws) {
    foreach ($kws as $kw) {
        if (phraseInText($q, $kw)) { $matchedCatKeyword = $catLabel; break 2; }
    }
}

$asksForProduct = (bool) preg_match('/\b(phan mem|san pham|co khong|co gi|gia|mua|goi y|nao|list|danh sach)\b/', $q);

if ($matchedCatKeyword && $asksForProduct) {
    $inCat = array_values(array_filter($products, function ($p) use ($matchedCatKeyword) {
        $catNorm = str_replace(' ', '', normalizeQuery((string)($p['ten_danh_muc'] ?? '')));
        return strpos($catNorm, str_replace(' ', '', $matchedCatKeyword)) !== false;
    }));
    if ($inCat) {
        usort($inCat, fn($a, $b) => (float)$a['gia_ban'] <=> (float)$b['gia_ban']);
        $lines = ['Bên mình đang có các phần mềm sau trong danh mục này:'];
        foreach (array_slice($inCat, 0, 5) as $p) {
            $tag = $p['trang_thai'] === 'het_hang' ? ' [Hết hàng]' : '';
            $lines[] = '• ' . $p['ten_san_pham'] . ' — ' . fmtVND((float)$p['gia_ban']) . $tag;
        }
        $lines[] = 'Xem đầy đủ và lọc theo nhu cầu tại trang Sản phẩm nhé.';
        $_SESSION['cb_last_category'] = $matchedCatKeyword;
        respond(200, ['ok' => true, 'reply' => implode("\n", $lines)]);
    }
}

/* ══════════════════════════════════════════════════════════════════
   BƯỚC 4: Nhận diện Ý ĐỊNH (intent) theo điểm số từ khoá
   Dữ liệu lấy từ bảng chatbot_faq — admin tự thêm/sửa/xoá qua
   admin/chatbot-faq.php, không cần đụng vào code này nữa.
═══════════════════════════════════════════════════════════════════ */
$bestReply = null;
$bestIntentScore = 0;
try {
    $faqRows = db()->query("SELECT tu_khoa, tra_loi FROM chatbot_faq WHERE kich_hoat=1")->fetchAll();
} catch (Exception $e) {
    $faqRows = [];
}
foreach ($faqRows as $row) {
    $score = 0;
    foreach (explode(',', $row['tu_khoa']) as $kwRaw) {
        $kw = normalizeQuery($kwRaw);
        if ($kw === '') continue;
        if (keywordMatch($q, $kw)) $score += strlen($kw);
    }
    if ($score > $bestIntentScore) {
        $bestIntentScore = $score;
        $bestReply = $row['tra_loi'];
    }
}

if ($bestReply !== null && $bestIntentScore >= 5) {
    respond(200, ['ok' => true, 'reply' => $bestReply]);
}

/* ══════════════════════════════════════════════════════════════════
   BƯỚC 5: Không khớp gì cả — thử xem có phải câu hỏi NỐI TIẾP dựa trên
   sản phẩm vừa nhắc ở lượt trước không (vd "vậy giá bao nhiêu?", "còn
   hàng không?" mà không nhắc lại tên sản phẩm). Chỉ áp dụng cho câu
   NGẮN để tránh khớp nhầm với các câu hỏi khác chứa từ chung chung.
═══════════════════════════════════════════════════════════════════ */
$wordCount = count(array_filter(explode(' ', $q)));
$looksLikeFollowUp = $wordCount <= 6 && (bool) preg_match(
    '/\b(vay gia|gia bao nhieu|bao nhieu tien|con hang khong|het hang chua|mua o dau|con khong|sao vay)\b/',
    $q
);
if ($looksLikeFollowUp && !empty($_SESSION['cb_last_product'])) {
    $lastId = (int) $_SESSION['cb_last_product'];
    foreach ($products as $p) {
        if ((int)$p['id'] === $lastId) {
            respond(200, ['ok' => true, 'reply' => productReplyText($p)]);
        }
    }
}

/* ══════════════════════════════════════════════════════════════════
   BƯỚC 6: Không nhận diện được -> ghi log để admin xem sau + trả lời
   mặc định kèm gợi ý liên hệ nhân viên
═══════════════════════════════════════════════════════════════════ */
try {
    db()->prepare("INSERT INTO chatbot_missed (cau_hoi, cau_hoi_chuan) VALUES (:raw, :norm)")
        ->execute([':raw' => mb_substr($message, 0, 800), ':norm' => mb_substr($q, 0, 500)]);
} catch (Exception $e) {}

respond(200, [
    'ok'    => true,
    'reply' => "Mình chưa chắc hiểu đúng ý bạn 🤔. Bạn có thể hỏi cụ thể hơn (tên phần mềm, giao hàng, thanh toán, bảo hành, cách mua hàng...) hoặc liên hệ trực tiếp đội ngũ hỗ trợ:\n📧 support@fromshopwhere.com\n☎️ Hotline: 1900 1234 (8:00–22:00)\n💬 Zalo OA: FROMSHOPWHERE Official",
]);
