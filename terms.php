<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'terms';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Điều Khoản Dịch Vụ — FROMSHOPWHERE</title>
<meta name="description" content="Điều khoản dịch vụ, chính sách bảo hành và đổi trả khi mua phần mềm bản quyền tại FROMSHOPWHERE.">
<link rel="canonical" href="<?= SITE_URL ?>/terms.php">
<meta property="og:type" content="website">
<meta property="og:title" content="Điều Khoản Dịch Vụ — FROMSHOPWHERE">
<meta property="og:description" content="Điều khoản sử dụng, chính sách bảo hành 1 đổi 1 và hoàn tiền khi mua phần mềm bản quyền.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/terms.php">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/policy.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">📜 Pháp lý</span></div>
    <h1>Điều khoản dịch vụ</h1>
    <p>Cập nhật lần cuối: <?= date('d/m/Y') ?></p>
  </div>
</div>

<div class="section">
  <div class="policy-shell">

    <nav class="policy-toc">
      <a href="#pham-vi">1. Phạm vi áp dụng</a>
      <a href="#tai-khoan">2. Tài khoản người dùng</a>
      <a href="#dat-hang">3. Đặt hàng &amp; thanh toán</a>
      <a href="#doi-tra">4. Bảo hành &amp; đổi trả</a>
      <a href="#trach-nhiem">5. Giới hạn trách nhiệm</a>
      <a href="#thay-doi">6. Thay đổi điều khoản</a>
    </nav>

    <div class="policy-wrap">

    <h2 id="pham-vi">1. Phạm vi áp dụng</h2>
    <p>Điều khoản này áp dụng cho mọi giao dịch mua bán license key phần mềm thực hiện trên website FROMSHOPWHERE. Bằng việc đặt hàng, bạn xác nhận đã đọc, hiểu và đồng ý với các điều khoản dưới đây.</p>

    <h2 id="tai-khoan">2. Tài khoản người dùng</h2>
    <p>Bạn có trách nhiệm cung cấp thông tin chính xác khi đăng ký tài khoản và bảo mật thông tin đăng nhập của mình. FROMSHOPWHERE không chịu trách nhiệm với các thiệt hại phát sinh từ việc để lộ thông tin đăng nhập do lỗi của người dùng.</p>

    <h2 id="dat-hang">3. Đặt hàng & thanh toán</h2>
    <p>Đơn hàng được xác nhận và xử lý tự động ngay sau khi hệ thống nhận được thanh toán thành công. License key sẽ được gửi qua địa chỉ email bạn cung cấp khi đặt hàng — vui lòng kiểm tra kỹ thông tin trước khi hoàn tất thanh toán.</p>

    <h2 id="doi-tra">4. Chính sách bảo hành & đổi trả</h2>
    <ul class="policy-list">
      <li><strong>Bảo hành 1 đổi 1:</strong> Mọi key bán ra được đổi mới ngay lập tức nếu phát sinh lỗi trong quá trình kích hoạt không do lỗi từ phía khách hàng (ví dụ: key sai vùng, key bị nhà sản xuất thu hồi do lỗi hệ thống của chúng tôi).</li>
      <li><strong>Thời hạn bảo hành:</strong> Áp dụng trong suốt thời gian hiệu lực của gói phần mềm bạn đã mua (1 năm, vĩnh viễn...).</li>
      <li><strong>Hoàn tiền 100%:</strong> Áp dụng khi chúng tôi không có sản phẩm thay thế phù hợp hoặc sản phẩm giao không đúng như mô tả.</li>
      <li><strong>Trường hợp không được bảo hành:</strong> Key đã kích hoạt thành công và sử dụng bình thường; lỗi phát sinh do khách hàng tự ý can thiệp, chia sẻ hoặc bán lại key cho bên thứ ba.</li>
      <li><strong>Cách gửi yêu cầu:</strong> Vào mục "Tài khoản → Đơn hàng", chọn đơn cần bảo hành và bấm "Yêu cầu bảo hành", hoặc liên hệ trực tiếp qua trang <a href="contact.php">Liên hệ</a>.</li>
    </ul>

    <h2 id="trach-nhiem">5. Giới hạn trách nhiệm</h2>
    <p>FROMSHOPWHERE không chịu trách nhiệm với các thiệt hại gián tiếp phát sinh từ việc sử dụng phần mềm sai mục đích, hoặc từ các thay đổi chính sách một phía của nhà sản xuất phần mềm nằm ngoài khả năng kiểm soát của chúng tôi. Trong mọi trường hợp, trách nhiệm bồi thường (nếu có) không vượt quá giá trị đơn hàng liên quan.</p>

    <h2 id="thay-doi">6. Thay đổi điều khoản</h2>
    <p>Chúng tôi có thể cập nhật điều khoản này theo thời gian để phù hợp với quy định pháp luật hoặc thay đổi trong vận hành. Phiên bản mới nhất luôn được đăng tải tại trang này.</p>

    <div class="policy-cta">
      <div>
        <strong>Còn thắc mắc về chính sách?</strong>
        <p>Đội ngũ hỗ trợ luôn sẵn sàng giải đáp cho bạn.</p>
      </div>
      <a class="btn-detail" href="contact.php">Liên hệ hỗ trợ →</a>
    </div>

  </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
