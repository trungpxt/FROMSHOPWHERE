<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'contact';
$_user = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Liên hệ — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<!-- ══════════════════════════════════════ -->
<!--  PAGE HEADER                          -->
<!-- ══════════════════════════════════════ -->
<div class="page-header">
  <div class="page-header-inner">
    <h1>Liên hệ</h1>
    <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7</p>
  </div>
</div>

<!-- ══════════════════════════════════════ -->
<!--  CONTACT                              -->
<!-- ══════════════════════════════════════ -->
<div class="section">
  <div class="contact-grid">

    <!-- Form -->
    <div class="checkout-box">
      <h3>Gửi tin nhắn cho chúng tôi</h3>
      <div class="form-group">
        <label class="form-label">Họ và tên</label>
        <input class="form-input" type="text" placeholder="Nguyễn Văn A" id="contactName">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" placeholder="email@example.com" id="contactEmail">
      </div>
      <div class="form-group">
        <label class="form-label">Chủ đề</label>
        <select class="form-input" style="cursor:pointer" id="contactSubject">
          <option>Hỗ trợ kỹ thuật</option>
          <option>Tư vấn sản phẩm</option>
          <option>Khiếu nại đơn hàng</option>
          <option>Hợp tác kinh doanh</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label class="form-label">Nội dung</label>
        <textarea class="form-input" rows="5" placeholder="Nội dung tin nhắn của bạn..." style="resize:vertical" id="contactMsg"></textarea>
      </div>
      <button class="btn-submit" onclick="sendContact()">Gửi tin nhắn</button>
    </div>

    <!-- Info -->
    <div>
      <div class="contact-info-card">
        <div class="ci-icon" style="background:#E1F5EE">📧</div>
        <div>
          <div class="ci-label">Email hỗ trợ</div>
          <div class="ci-val">support@fromshopwhere.com</div>
          <div class="ci-note">Phản hồi trong 2 giờ</div>
        </div>
      </div>
      <div class="contact-info-card">
        <div class="ci-icon" style="background:#E6F1FB">📱</div>
        <div>
          <div class="ci-label">Hotline miễn phí</div>
          <div class="ci-val">1900 1234</div>
          <div class="ci-note">Thứ 2–7, 8:00–22:00</div>
        </div>
      </div>
      <div class="contact-info-card">
        <div class="ci-icon" style="background:#FAEEDA">💬</div>
        <div>
          <div class="ci-label">Zalo OA</div>
          <div class="ci-val">FROMSHOPWHERE Official</div>
          <div class="ci-note">Chat trực tiếp 24/7</div>
        </div>
      </div>
      <div class="contact-cta">
        <h4>⚡ Hỗ trợ nhanh nhất</h4>
        <p>Nhắn tin qua Zalo hoặc Facebook Messenger để được hỗ trợ nhanh nhất trong giờ hành chính. Đội ngũ kỹ thuật của chúng tôi luôn sẵn sàng.</p>
      </div>
    </div>

  </div>
</div>

<!-- ══════════════════════════════════════ -->
<!--  FOOTER                               -->
<!-- ══════════════════════════════════════ -->
<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <div style="margin-bottom:12px">
          <img src="images/logo.png" alt="FROMSHOPWHERE" style="height:50px;width:auto;object-fit:contain;filter:brightness(1.1) drop-shadow(0 0 4px rgba(0,0,0,.4))">
        </div>
        <p>Nền tảng mua bán phần mềm bản quyền uy tín hàng đầu Việt Nam.</p>
        <div class="social-links">
          <a class="social-link" href="#">f</a>
          <a class="social-link" href="#">in</a>
          <a class="social-link" href="#">yt</a>
          <a class="social-link" href="#">tk</a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Sản phẩm</h4>
        <ul>
          <li><a href="products.php">Thiết kế đồ hoạ</a></li>
          <li><a href="products.php">Văn phòng</a></li>
          <li><a href="products.php">Chỉnh sửa video</a></li>
          <li><a href="products.php">Bảo mật</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Hỗ trợ</h4>
        <ul>
          <li><a href="blog.php">Hướng dẫn cài đặt</a></li>
          <li><a href="contact.php">Liên hệ hỗ trợ</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Công ty</h4>
        <ul>
          <li><a href="#">Giới thiệu</a></li>
          <li><a href="blog.php">Blog</a></li>
          <li><a href="#">Điều khoản dịch vụ</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div>
        <div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div>
        <div class="pay-badge">ZALO</div>
        <div class="pay-badge">ATM</div>
      </div>
    </div>
  </div>
</footer>

<script src="shared.js"></script>
<script>
  function sendContact() {
    const name = document.getElementById('contactName').value.trim();
    const email = document.getElementById('contactEmail').value.trim();
    const msg = document.getElementById('contactMsg').value.trim();
    if (!name || !email || !msg) {
      showToast('⚠ Vui lòng điền đầy đủ thông tin!');
      return;
    }
    showToast('✓ Đã gửi tin nhắn thành công!');
    document.getElementById('contactName').value = '';
    document.getElementById('contactEmail').value = '';
    document.getElementById('contactMsg').value = '';
  }

  document.addEventListener('DOMContentLoaded', () => {
    restoreTheme();
    updateCartBadge();
    updateLoginBtn();
    syncCartPanel();
  });
</script>
</body>
</html>
