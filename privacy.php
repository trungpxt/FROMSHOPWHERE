<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'privacy';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chính Sách Bảo Mật — FROMSHOPWHERE</title>
<meta name="description" content="Chính sách bảo mật thông tin khách hàng tại FROMSHOPWHERE: dữ liệu thu thập, mục đích sử dụng và cam kết bảo vệ quyền riêng tư.">
<link rel="canonical" href="<?= SITE_URL ?>/privacy.php">
<meta property="og:type" content="website">
<meta property="og:title" content="Chính Sách Bảo Mật — FROMSHOPWHERE">
<meta property="og:description" content="Cam kết bảo vệ thông tin cá nhân và quyền riêng tư của khách hàng.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/privacy.php">
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
    <h1>Chính sách bảo mật</h1>
    <p>Cập nhật lần cuối: <?= date('d/m/Y') ?></p>
  </div>
</div>

<div class="section">
  <div class="policy-shell">

    <nav class="policy-toc">
      <a href="#thu-thap">1. Thông tin thu thập</a>
      <a href="#muc-dich">2. Mục đích sử dụng</a>
      <a href="#luu-tru">3. Lưu trữ &amp; bảo vệ dữ liệu</a>
      <a href="#chia-se">4. Chia sẻ thông tin</a>
      <a href="#quyen">5. Quyền của bạn</a>
      <a href="#lien-he">6. Liên hệ</a>
    </nav>

    <div class="policy-wrap">

    <h2 id="thu-thap">1. Thông tin chúng tôi thu thập</h2>
    <p>Khi bạn tạo tài khoản, đặt hàng hoặc liên hệ hỗ trợ, chúng tôi có thể thu thập: họ tên, địa chỉ email, số điện thoại, địa chỉ (nếu bạn cung cấp) và lịch sử đơn hàng. Chúng tôi không yêu cầu các thông tin nhạy cảm như số căn cước hay tài khoản ngân hàng.</p>

    <h2 id="muc-dich">2. Mục đích sử dụng thông tin</h2>
    <ul class="policy-list">
      <li>Xử lý đơn hàng và gửi license key qua email.</li>
      <li>Xác thực tài khoản, khôi phục mật khẩu khi cần.</li>
      <li>Gửi thông báo liên quan đến đơn hàng, mã giảm giá (nếu bạn đăng ký nhận).</li>
      <li>Hỗ trợ kỹ thuật và giải quyết khiếu nại, bảo hành.</li>
    </ul>

    <h2 id="luu-tru">3. Lưu trữ & bảo vệ dữ liệu</h2>
    <p>Mật khẩu tài khoản được mã hoá một chiều (hashing) trước khi lưu trữ, chúng tôi không bao giờ lưu mật khẩu dạng văn bản thuần. Dữ liệu được lưu trữ trên hệ thống có kiểm soát truy cập, chỉ nhân sự được phân quyền mới có thể truy xuất khi cần xử lý đơn hàng hoặc hỗ trợ khách hàng.</p>

    <h2 id="chia-se">4. Chia sẻ thông tin với bên thứ ba</h2>
    <p>Chúng tôi không bán hoặc cho thuê thông tin cá nhân của bạn cho bất kỳ bên thứ ba nào vì mục đích quảng cáo. Thông tin chỉ được chia sẻ với các đối tác thanh toán (VNPay, cổng ví điện tử...) ở mức cần thiết để hoàn tất giao dịch, hoặc khi có yêu cầu hợp pháp từ cơ quan chức năng.</p>

    <h2 id="quyen">5. Quyền của bạn</h2>
    <p>Bạn có quyền truy cập, chỉnh sửa thông tin cá nhân của mình bất kỳ lúc nào tại mục "Tài khoản". Nếu muốn yêu cầu xoá tài khoản hoặc dữ liệu cá nhân, vui lòng liên hệ đội hỗ trợ — chúng tôi sẽ xử lý trong thời gian sớm nhất, trừ các dữ liệu cần lưu trữ theo quy định pháp luật (ví dụ hoá đơn, chứng từ giao dịch).</p>

    <h2 id="lien-he">6. Liên hệ về quyền riêng tư</h2>
    <p>Nếu có bất kỳ thắc mắc nào về cách chúng tôi xử lý dữ liệu cá nhân, vui lòng liên hệ qua trang <a href="contact.php">Liên hệ</a> hoặc email <strong>support@fromshopwhere.com</strong>.</p>

    <div class="policy-cta">
      <div>
        <strong>Cần hỗ trợ thêm về quyền riêng tư?</strong>
        <p>Chúng tôi sẵn sàng giải đáp mọi thắc mắc của bạn.</p>
      </div>
      <a class="btn-detail" href="contact.php">Liên hệ hỗ trợ →</a>
    </div>

  </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
