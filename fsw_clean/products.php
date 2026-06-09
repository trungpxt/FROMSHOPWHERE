<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'products';
$_user = currentUser();

// Lấy TOÀN BỘ sản phẩm hiển thị kèm tên danh mục từ CSDL
try {
    $stmt = db()->prepare("
        SELECT p.*, c.ten_danh_muc 
        FROM products p 
        JOIN categories c ON p.danh_muc_id = c.id 
        WHERE p.trang_thai = 'hien' 
        ORDER BY p.id DESC
    ");
    $stmt->execute();
    $dbProducts = $stmt->fetchAll();
} catch (PDOException $ex) {
    $dbProducts = [];
}

// Chuyển đổi mảng dữ liệu PHP thành mảng JSON để nhúng vào JavaScript
$jsProducts = [];
foreach ($dbProducts as $p) {
    $jsProducts[] = [
        'id' => (int)$p['id'],
        'name' => $p['ten_san_pham'],
        'cat' => $p['ten_danh_muc'],
        'price' => (float)$p['gia_ban'],
        'oldPrice' => $p['gia_goc'] ? (float)$p['gia_goc'] : null,
        'image' => !empty($p['hinh_anh']) ? "images/products/" . $p['hinh_anh'] : "images/default.jpg",
        'isNew' => (int)$p['la_moi']
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sản phẩm — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">

<style>
  /* Cấu hình lưới hiển thị ô sản phẩm to và dễ nhìn */
  .products {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)) !important;
    gap: 24px !important;
  }

  .hero-card {
    padding: 20px !important;
    min-height: 360px !important; 
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer; /* Thêm con trỏ dạng bàn tay để người dùng biết có thể click */
  }

  .hero-card .hc-icon {
    height: 180px !important; 
    width: 100% !important;
    margin-bottom: 16px !important;
  }

  .hero-card .hc-name {
    font-size: 18px !important; 
    font-weight: 600 !important;
    line-height: 1.4 !important;
    margin-bottom: 10px !important;
  }

  .hero-card .hc-price {
    font-size: 18px !important;
    font-weight: 700 !important;
    margin-bottom: 10px !important;
  }

  .hero-card .hc-tag {
    font-size: 13px !important;
    padding: 4px 10px !important;
  }
</style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <h1>Tất cả sản phẩm</h1>
    <p>500+ phần mềm bản quyền với giá tốt nhất thị trường</p>
  </div>
</div>

<div class="section">
  <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap">

    <div class="sidebar">
      <div class="sidebar-title">Danh mục</div>
      <div class="sidebar-cats" id="sidebarCats">
        <div class="cat-pill active" onclick="filterProducts(this,'all')">Tất cả</div>
        <div class="cat-pill" onclick="filterProducts(this,'Thiết kế')">🎨 Thiết kế</div>
        <div class="cat-pill" onclick="filterProducts(this,'Văn phòng')">📄 Văn phòng</div>
        <div class="cat-pill" onclick="filterProducts(this,'Video')">🎬 Video</div>
        <div class="cat-pill" onclick="filterProducts(this,'Bảo mật')">🔒 Bảo mật</div>
      </div>

      <div style="border-top:1px solid var(--border);margin-top:16px;padding-top:16px">
        <div class="sidebar-title">Sắp xếp theo</div>
        <select id="sortSelect" class="sort-select" onchange="sortProducts()">
          <option value="pop">Phổ biến nhất</option>
          <option value="asc">Giá tăng dần</option>
          <option value="desc">Giá giảm dần</option>
          <option value="new">Mới nhất</option>
        </select>
      </div>
    </div>

    <div style="flex:1;min-width:0">
      <div class="products" id="productGrid">
          </div>
    </div>

  </div>
</div>

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
  const ALL_PRODUCTS = <?php echo json_encode($jsProducts); ?>;
  let currentCat = 'all';

  function fmtVND(num) {
      return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
  }

  // Thay đổi hàm xử lý click: Gọi sang hàm viewProduct thay vì addToCart
  function viewProduct(id) {
      window.location.href = `product-demo.php?id=${id}`;
  }

  function renderGrid(targetId, list) {
    const grid = document.getElementById(targetId);
    if (!grid) return;
    
    if (list.length === 0) {
        grid.innerHTML = `<p style="color:var(--text-muted); font-size:14px; grid-column:1/-1; padding:32px 0;">Không có sản phẩm nào được tìm thấy.</p>`;
        return;
    }

    grid.innerHTML = list.map(p => {
        const newBadge = p.isNew === 1 ? `<span style="background: red; color: white; font-size: 10px; padding: 2px 5px; border-radius: 3px; margin-left: 5px;">MỚI</span>` : '';
        const oldPriceHtml = (p.oldPrice && p.oldPrice > p.price) ? `<span style="text-decoration: line-through; color: #aaa; font-size: 12px; margin-left: 8px;">${fmtVND(p.oldPrice)}</span>` : '';
        
        // CẬP NHẬT TẠI ĐÂY: Thay đổi thuộc tính onclick thành viewProduct(id)
        return `
            <div class="hero-card" onclick="viewProduct(${p.id})">
                <div class="hc-icon" style="overflow:hidden; padding:0; background:#E1F5EE">
                    <img src="${p.image}" alt="${p.name}" style="width:100%; height:100%; object-fit:cover; border-radius:10px">
                </div>
                <div class="hc-name">
                    ${p.name} ${newBadge}
                </div>
                <div class="hc-price">
                    ${fmtVND(p.price)} ${oldPriceHtml}
                </div>
                <div class="hc-tag">${p.cat}</div>
            </div>
        `;
    }).join('');
  }

  function renderAllProducts(cat) {
    currentCat = cat;
    const list = cat === 'all' ? ALL_PRODUCTS : ALL_PRODUCTS.filter(p => p.cat === cat);
    renderGrid('productGrid', list);
  }

  function filterProducts(el, cat) {
    document.querySelectorAll('#sidebarCats .cat-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    renderAllProducts(cat);
    const searchInput = document.getElementById('searchInput');
    if(searchInput) searchInput.value = '';
  }

  function sortProducts() {
    const val = document.getElementById('sortSelect').value;
    let list = currentCat === 'all' ? [...ALL_PRODUCTS] : ALL_PRODUCTS.filter(p => p.cat === currentCat);
    
    if (val === 'asc') {
        list.sort((a,b) => a.price - b.price);
    } else if (val === 'desc') {
        list.sort((a,b) => b.price - a.price);
    } else if (val === 'new') {
        list.sort((a,b) => b.id - a.id);
    }
    renderGrid('productGrid', list);
  }

  function searchProducts() {
    const searchInput = document.getElementById('searchInput');
    if(!searchInput) return;
    const q = searchInput.value.toLowerCase().trim();
    if (!q) { renderAllProducts(currentCat); return; }
    const list = ALL_PRODUCTS.filter(p =>
      p.name.toLowerCase().includes(q) || p.cat.toLowerCase().includes(q)
    );
    renderGrid('productGrid', list);
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (typeof restoreTheme === 'function') restoreTheme();
    if (typeof updateCartBadge === 'function') updateCartBadge();
    if (typeof updateLoginBtn === 'function') updateLoginBtn();
    if (typeof syncCartPanel === 'function') syncCartPanel();
    renderAllProducts('all');
  });
</script>
</body>
</html>