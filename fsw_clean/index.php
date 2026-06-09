<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'home';
$_user = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FROMSHOPWHERE — Phần Mềm Bản Quyền</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include __DIR__ . '/includes/nav.php'; ?>
    <!-- ══════════════════════════════════════ -->
    <!--  HERO                                 -->
    <!-- ══════════════════════════════════════ -->
    <center id="BG-hero">
        <section class="hero">
            <center>
                <div class="hero-inner">
                    <div class="hero-text">
                        <div class="hero-img"><img src="images/logo.png" alt=""></div>
                        <h1 class="hero-title">Phần mềm xịn<br><em>giá không xịn</em></h1>
                        <div class="hero-actions">
                            <a class="btn-primary" href="products.php">Mua ngay →</a>
                            <a class="btn-ghost" href="products.php">Xem tất cả</a>
                        </div>
                        <br>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <div class="num">500+</div>
                                <div class="lbl">Phần mềm</div>
                            </div>
                            <div class="hero-stat">
                                <div class="num">12K+</div>
                                <div class="lbl">Khách hàng</div>
                            </div>
                            <div class="hero-stat">
                                <div class="num">4.9★</div>
                                <div class="lbl">Đánh giá</div>
                            </div>
                            <div class="hero-stat">
                                <div class="num">24/7</div>
                                <div class="lbl">Hỗ trợ</div>
                            </div>
                        </div>
                    </div>
            </center>
    </center>
    <!-- <div class="hero-visual">
        <div class="hero-card" onclick="addToCart(1)">
            <div class="hc-icon" style="overflow:hidden;padding:0;background:#E1F5EE">
                <img src="images/products/photoshop-2025.jpg" alt="Photoshop 2025" style="width:100%;height:100%;object-fit:cover;border-radius:10px">
            </div>
            <div class="hc-name">Photoshop 2025</div>
            <div class="hc-price">350.000đ</div>
            <div class="hc-tag">Thiết kế</div>
        </div>
        <div class="hero-card" onclick="addToCart(5)">
            <div class="hc-icon" style="overflow:hidden;padding:0;background:#E6F1FB">
                <img src="images/products/office-365.jpg" alt="Office 365" style="width:100%;height:100%;object-fit:cover;border-radius:10px">
            </div>
            <div class="hc-name">Office 365</div>
            <div class="hc-price">280.000đ</div>
            <div class="hc-tag">Văn phòng</div>
        </div>
        <div class="hero-card" onclick="addToCart(8)">
            <div class="hc-icon" style="overflow:hidden;padding:0;background:#FAEEDA">
                <img src="images/products/filmora-13.jpg" alt="Filmora 13" style="width:100%;height:100%;object-fit:cover;border-radius:10px">
            </div>
            <div class="hc-name">Filmora 13</div>
            <div class="hc-price">199.000đ</div>
            <div class="hc-tag">Video</div>
        </div>
        <div class="hero-card" onclick="addToCart(7)">
            <div class="hc-icon" style="overflow:hidden;padding:0;background:#FCEBEB">
                <img src="images/kaspersky.jpg" alt="Kaspersky Total" style="width:100%;height:100%;object-fit:cover;border-radius:10px">
            </div>
            <div class="hc-name">Kaspersky Total</div>
            <div class="hc-price">220.000đ</div>
            <div class="hc-tag">Bảo mật</div>
        </div>
        </div>
        </div> -->
    </section>
    <!-- ══════════════════════════════════════ -->
    <!--  FEATURED PRODUCTS                    -->
    <!-- ══════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <div>
                <div class="eyebrow">Được mua nhiều nhất</div>
                <h2 class="section-title">Sản phẩm <span>nổi bật</span></h2>
            </div>
            <a class="view-all" href="products.php">Xem tất cả →</a>
        </div>
        <div class="cats" id="homeCats">
            <div class="cat-pill active" onclick="filterHome(this,'all')">Tất cả</div>
            <div class="cat-pill" onclick="filterHome(this,'Thiết kế')">🎨 Thiết kế</div>
            <div class="cat-pill" onclick="filterHome(this,'Văn phòng')">📄 Văn phòng</div>
            <div class="cat-pill" onclick="filterHome(this,'Video')">🎬 Video</div>
            <div class="cat-pill" onclick="filterHome(this,'Bảo mật')">🔒 Bảo mật</div>
        </div>
        <!-- demo product -->
         <?php
// 1. Nhúng file cấu hình kết nối CSDL
require_once __DIR__ . '/config.php';

// 2. Viết câu lệnh SQL lấy sản phẩm hiển thị kèm tên danh mục
// Chỉ lấy các sản phẩm có trạng thái là 'hien'
$sql = "SELECT p.*, c.ten_danh_muc 
        FROM products p 
        JOIN categories c ON p.danh_muc_id = c.id 
        WHERE p.trang_thai = 'hien' 
        ORDER BY p.id DESC 
        LIMIT 8"; // Giới hạn 8 sản phẩm nổi bật giống như script js của bạn

try {
$stmt = db()->prepare("
                    SELECT p.*, c.ten_danh_muc 
                    FROM products p 
                    JOIN categories c ON p.danh_muc_id = c.id 
                    WHERE p.trang_thai = 'hien' 
                    ORDER BY p.id DESC 
                    LIMIT 8
                ");
                $stmt->execute();
                $products = $stmt->fetchAll();
            } catch (PDOException $ex) {
                $products = [];
            }
?>

<div class="products" id="productGrid">
    <?php if (empty($products)): ?>
        <p style="color:var(--text-muted); font-size:14px; grid-column:1/-1; padding:32px 0;">
            Không có sản phẩm nào được tìm thấy.
        </p>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="hero-card" onclick="addToCart(<?php echo $product['id']; ?>)">
                <div class="hc-icon" style="overflow:hidden; padding:0; background:#E1F5EE">
                    <?php 
                        $imagePath = !empty($product['hinh_anh']) ? "images/products/" . $product['hinh_anh'] : "images/default.jpg"; 
                    ?>
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                         alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>" 
                         style="width:100%; height:100%; object-fit:cover; border-radius:10px">
                </div>
                
                <div class="hc-name">
                    <?php echo htmlspecialchars($product['ten_san_pham']); ?>
                    <?php if ($product['la_moi'] == 1): ?>
                        <span style="background: red; color: white; font-size: 10px; padding: 2px 5px; border-radius: 3px; margin-left: 5px;">MỚI</span>
                    <?php endif; ?>
                </div>
                
                <div class="hc-price">
                    <?php echo number_format($product['gia_ban'], 0, ',', '.'); ?>đ
                    <?php if (!empty($product['gia_goc']) && $product['gia_goc'] > $product['gia_ban']): ?>
                        <span style="text-decoration: line-through; color: #aaa; font-size: 12px; margin-left: 8px;">
                            <?php echo number_format($product['gia_goc'], 0, ',', '.'); ?>đ
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="hc-tag"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
    </div>
    <!-- ══════════════════════════════════════ -->
    <!--  WHY US                               -->
    <!-- ══════════════════════════════════════ -->
    <section class="features-bg">
        <div class="section">
            <div class="section-header">
                <div>
                    <div class="eyebrow">Cam kết của chúng tôi</div>
                    <h2 class="section-title">Tại sao chọn <span>FROMSHOPWHERE?</span></h2>
                </div>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#E1F5EE">🔑</div>
                    <h3>Bản quyền chính hãng</h3>
                    <p>Tất cả phần mềm đều là license key chính hãng, đảm bảo hoạt động ổn định và được cập nhật đầy đủ.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background:#E6F1FB">⚡</div>
                    <h3>Giao hàng tức thì</h3>
                    <p>License key được gửi ngay qua email sau khi thanh toán thành công, không cần chờ đợi.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background:#EEEDFE">💰</div>
                    <h3>Giá tốt nhất</h3>
                    <p>Cam kết giá thấp nhất thị trường. Nếu bạn tìm được giá tốt hơn, chúng tôi sẽ hoàn tiền chênh lệch.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background:#FAEEDA">🎧</div>
                    <h3>Hỗ trợ 24/7</h3>
                    <p>Đội ngũ hỗ trợ kỹ thuật sẵn sàng giải đáp mọi thắc mắc và hỗ trợ cài đặt cho bạn.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- ══════════════════════════════════════ -->
    <!--  TESTIMONIALS                         -->
    <!-- ══════════════════════════════════════ -->
    <div class="section">
        <div class="section-header">
            <div>
                <div class="eyebrow">Phản hồi khách hàng</div>
                <h2 class="section-title">Khách hàng <span>nói gì?</span></h2>
            </div>
        </div>
        <div class="testi-grid">
            <div class="testi-card">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-text">Mua Photoshop với giá rẻ hơn nhiều so với mua trực tiếp từ Adobe. Key hoạt động tốt, giao ngay sau khi chuyển khoản!</p>
                <div class="testi-author">
                    <div class="testi-avatar" style="background:#E1F5EE;color:#0F6E56">NA</div>
                    <div>
                        <div class="testi-name">Nguyễn Anh</div>
                        <div class="testi-role">Graphic Designer · TP.HCM</div>
                    </div>
                </div>
            </div>
            <div class="testi-card">
                <div class="testi-stars">★★★★★</div>
                <p class="testi-text">Đã mua Office 365 lần thứ 3. Luôn nhận được key trong vài phút. Shop uy tín, hỗ trợ nhiệt tình!</p>
                <div class="testi-author">
                    <div class="testi-avatar" style="background:#E6F1FB;color:#185FA5">TL</div>
                    <div>
                        <div class="testi-name">Trần Linh</div>
                        <div class="testi-role">Kế toán · Hà Nội</div>
                    </div>
                </div>
            </div>
            <div class="testi-card">
                <div class="testi-stars">★★★★☆</div>
                <p class="testi-text">Giá rẻ, nhiều lựa chọn, giao diện dễ dùng. Mình mua Filmora và Kaspersky đều ok. Sẽ tiếp tục ủng hộ!</p>
                <div class="testi-author">
                    <div class="testi-avatar" style="background:#EEEDFE;color:#534AB7">MK</div>
                    <div>
                        <div class="testi-name">Minh Khoa</div>
                        <div class="testi-role">Content Creator · Đà Nẵng</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ══════════════════════════════════════ -->
    <!--  BANNER CTA                           -->
    <!-- ══════════════════════════════════════ -->
    <div class="banner-strip">
        <div class="banner-inner">
            <div>
                <div class="banner-eyebrow">Ưu đãi đặc biệt</div>
                <h2>Giảm thêm 15% cho đơn đầu tiên</h2>
                <p>Nhập mã <b>FIRST15</b> khi thanh toán để nhận ưu đãi ngay hôm nay</p>
            </div>
            <div class="banner-actions">
                <a class="btn-primary" href="products.php">Mua ngay</a>
                <a class="btn-ghost" href="login.php">Đăng ký tài khoản</a>
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
                        <li><a href="contact.php">Câu hỏi thường gặp</a></li>
                        <li><a href="contact.php">Chính sách đổi trả</a></li>
                        <li><a href="contact.php">Liên hệ hỗ trợ</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Công ty</h4>
                    <ul>
                        <li><a href="#">Giới thiệu</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="contact.php">Hợp tác</a></li>
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
const SITE_URL = 'http://localhost/FROMSHOPWHERE';

function filterHome(el, cat) {
    document.querySelectorAll('#homeCats .cat-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    const grid = document.getElementById('productGrid');
    grid.style.opacity = '0.4';
    fetch(`${SITE_URL}/api/products.php?cat=${encodeURIComponent(cat === 'all' ? '' : cat)}&limit=8`)
        .then(r => r.json())
        .then(res => {
            const list = Array.isArray(res) ? res : (res.data || []);
            if (list.length === 0) {
                grid.innerHTML = '<p style="color:var(--text-muted);font-size:14px;grid-column:1/-1;padding:32px 0">Không có sản phẩm.</p>';
            } else {
                grid.innerHTML = list.map(p => renderCard(p, SITE_URL)).join('');
            }
            grid.style.opacity = '1';
        })
        .catch(() => { grid.style.opacity = '1'; });
}

function filterByCat(el, slug) {
    document.querySelectorAll('#homeCats .cat-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    filterHome(el, slug || 'all');
}

document.addEventListener('DOMContentLoaded', () => {
    restoreTheme();
    updateCartBadge();
    syncCartPanel();
    // Load sản phẩm mặc định
    filterHome(document.querySelector('#homeCats .cat-pill'), 'all');
});
    </script>
</body>

</html>
