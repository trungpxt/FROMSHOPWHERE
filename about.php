<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'about';
try {
    $totalProductsAbout = (int) db()->query("SELECT COUNT(*) FROM products WHERE trang_thai != 'an'")->fetchColumn();
} catch (Exception $e) {
    $totalProductsAbout = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giới Thiệu — FROMSHOPWHERE</title>
<meta name="description" content="FROMSHOPWHERE — nền tảng mua bán phần mềm bản quyền uy tín tại Việt Nam. Tìm hiểu về sứ mệnh, cam kết chất lượng và đội ngũ của chúng tôi.">
<link rel="canonical" href="<?= SITE_URL ?>/about.php">
<meta property="og:type" content="website">
<meta property="og:title" content="Giới Thiệu — FROMSHOPWHERE">
<meta property="og:description" content="Nền tảng mua bán phần mềm bản quyền uy tín, giá tốt, giao key nhanh, bảo hành 1 đổi 1.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/about.php">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/policy.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">🏢 Về chúng tôi</span></div>
    <h1>Giới thiệu về FROMSHOPWHERE</h1>
    <p>Mang phần mềm bản quyền đến gần hơn với mọi người dùng Việt Nam</p>
  </div>
</div>

<div class="section">
  <div class="policy-wrap">

    <div class="policy-stats">
      <div class="policy-stat"><strong><?= $totalProductsAbout > 0 ? $totalProductsAbout . '+' : '—' ?></strong><span>Phần mềm bản quyền</span></div>
      <div class="policy-stat"><strong>24/7</strong><span>Giao key tự động</span></div>
      <div class="policy-stat"><strong>1 đổi 1</strong><span>Chính sách bảo hành</span></div>
      <div class="policy-stat"><strong>100%</strong><span>Key chính hãng</span></div>
    </div>

    <div class="founder-note">
      <div class="founder-avatar">👋</div>
      <div class="founder-note-body">
        <p>"Hồi còn là sinh viên, mình cũng từng dùng phần mềm crack suốt vì tiền đâu ra mà mua bản quyền — cho đến khi máy dính mã độc, mất sạch đồ án làm cả tháng trời. Từ đó mình luôn tự hỏi: tại sao phần mềm chính hãng ở Việt Nam lại đắt đến vậy, trong khi vẫn có những nguồn key hợp pháp giá tốt hơn nhiều?"</p>
        <p>"FROMSHOPWHERE ra đời từ câu hỏi đó. Không phải để bán cho thật nhiều, mà để không ai phải đánh đổi giữa 'có tiền' và 'an toàn' như mình từng phải chọn."</p>
        <p>"Cảm ơn bạn đã ghé qua và tin tưởng bọn mình 🙏"</p>
        <div class="founder-sign">— Đội ngũ sáng lập FROMSHOPWHERE</div>
      </div>
    </div>


    <h2>Chúng tôi là ai?</h2>
    <p>FROMSHOPWHERE là nền tảng thương mại điện tử chuyên cung cấp license key bản quyền cho các phần mềm phổ biến nhất hiện nay: hệ điều hành Windows, bộ Office, các công cụ thiết kế đồ hoạ và dựng video của Adobe, phần mềm diệt virus hàng đầu thế giới cùng nhiều tiện ích khác.</p>

    <h2>Chúng tôi phục vụ ai?</h2>
    <p>Chủ yếu là học sinh, sinh viên và người mới đi làm — những người cần phần mềm bản quyền để học tập, làm đồ án, đi làm nhưng ngân sách còn hạn chế. Đây cũng chính là lý do giá tại FROMSHOPWHERE luôn được giữ ở mức dễ tiếp cận nhất có thể.</p>

    <h2>Tại sao giá tại FROMSHOPWHERE lại tốt hơn?</h2>
    <ul class="policy-list">
      <li><strong>Nguồn Key hợp pháp:</strong> Key bản quyền dạng OEM và Volume Licensing hợp pháp theo quy định, được phép tái sử dụng và chuyển nhượng.</li>
      <li><strong>Vận hành tinh gọn:</strong> Là cửa hàng trực tuyến, chúng tôi không phải gánh chi phí mặt bằng, kho bãi hay nhân sự mặt tiền.</li>
      <li><strong>Giao hàng số 100%:</strong> Sản phẩm được gửi qua email nên không tốn chi phí đóng gói, vận chuyển.</li>
    </ul>

    <h2>Cam kết của chúng tôi</h2>
    <div class="policy-cards">
      <div class="policy-card">
        <div class="policy-card-icon">✅</div>
        <h3>Chính hãng 100%</h3>
        <p>Mọi key bán ra đều được kích hoạt trực tuyến trực tiếp với máy chủ xác thực của nhà sản xuất.</p>
      </div>
      <div class="policy-card">
        <div class="policy-card-icon">🛡️</div>
        <h3>Bảo hành 1 đổi 1</h3>
        <p>Đổi key mới ngay lập tức nếu phát sinh lỗi kích hoạt không do lỗi từ phía khách hàng.</p>
      </div>
      <div class="policy-card">
        <div class="policy-card-icon">⚡</div>
        <h3>Nhận key tức thì</h3>
        <p>Hệ thống tự động gửi key qua email chỉ trong vài giây đến vài phút, hoạt động 24/7 kể cả ngày lễ.</p>
      </div>
      <div class="policy-card">
        <div class="policy-card-icon">🤝</div>
        <h3>Hỗ trợ tận tâm</h3>
        <p>Đội ngũ kỹ thuật sẵn sàng hỗ trợ cài đặt, kích hoạt từ xa hoàn toàn miễn phí.</p>
      </div>
    </div>

    <div class="policy-cta">
      <div>
        <strong>Sẵn sàng trải nghiệm?</strong>
        <p>Khám phá kho phần mềm bản quyền giá tốt ngay hôm nay.</p>
      </div>
      <a class="btn-detail" href="products.php">Xem sản phẩm →</a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
