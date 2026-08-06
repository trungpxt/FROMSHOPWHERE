<?php
/* includes/footer.php — Footer dùng chung cho tất cả trang */
if (!defined('SITE_URL')) require_once __DIR__ . '/../config.php';
?>
<footer>
    <div class="footer-inner">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo-wrap">
                    <img src="images/ui/logo.png" alt="FROMSHOPWHERE" class="logo-img-footer logo-img-light">
                    <img src="images/ui/logo-dark.png" alt="FROMSHOPWHERE" class="logo-img-footer logo-img-dark">
                </div>
                <p>Nền tảng mua bán phần mềm bản quyền uy tín hàng đầu Việt Nam. Cam kết giá tốt, giao hàng nhanh và hỗ trợ tận tâm.</p>
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
                    <li><a href="products.php">Hệ điều hành</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Hỗ trợ</h4>
                <ul>
                    <li><a href="blog.php">Hướng dẫn cài đặt</a></li>
                    <li><a href="faq.php">Câu hỏi thường gặp</a></li>
                    <li><a href="terms.php#doi-tra">Chính sách đổi trả</a></li>
                    <li><a href="contact.php">Liên hệ hỗ trợ</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Công ty</h4>
                <ul>
                    <li><a href="about.php">Giới thiệu</a></li>
                    <li><a href="blog.php">Blog</a></li>
                    <li><a href="contact.php">Hợp tác</a></li>
                    <li><a href="privacy.php">Chính sách bảo mật</a></li>
                    <li><a href="terms.php">Điều khoản dịch vụ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
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
