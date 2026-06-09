<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'products';
$_user = currentUser();

// 1. Nhận ID sản phẩm từ URL quả trang products.php gửi sang
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

// 2. Truy vấn lấy thông tin chi tiết sản phẩm hiện tại
try {
    $stmt = db()->prepare("
        SELECT p.*, c.ten_danh_muc 
        FROM products p 
        JOIN categories c ON p.danh_muc_id = c.id 
        WHERE p.id = :id AND p.trang_thai = 'hien'
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        header('Location: products.php');
        exit;
    }
} catch (PDOException $ex) {
    die("Lỗi CSDL: " . $ex->getMessage());
}

// 3. Lấy toàn bộ sản phẩm để làm mục "Sản phẩm liên quan"
try {
    $stmtAll = db()->prepare("
        SELECT p.*, c.ten_danh_muc 
        FROM products p 
        JOIN categories c ON p.danh_muc_id = c.id 
        WHERE p.trang_thai = 'hien'
    ");
    $stmtAll->execute();
    $dbAllProducts = $stmtAll->fetchAll();
} catch (PDOException $ex) {
    $dbAllProducts = [];
}

$jsAllProducts = [];
foreach ($dbAllProducts as $p) {
    $jsAllProducts[] = [
        'id' => (int)$p['id'],
        'name' => $p['ten_san_pham'],
        'cat' => $p['ten_danh_muc'],
        'price' => (float)$p['gia_ban'],
        'oldPrice' => $p['gia_goc'] ? (float)$p['gia_goc'] : null,
        'image' => !empty($p['hinh_anh']) ? "images/" . $p['hinh_anh'] : "images/default.jpg",
        'isNew' => (int)$p['la_moi']
    ];
}

function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

$productImage = !empty($product['hinh_anh']) ? "images/" . $product['hinh_anh'] : "images/default.jpg";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($product['ten_san_pham']); ?> — Chi tiết sản phẩm</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php
/* ── inline nav ── */
if (!defined('SITE_URL')) require_once __DIR__ . '/config.php';
startSession();
$_user        = currentUser();
$_currentPage = $currentPage ?? '';
?>
<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<!-- ── CART OVERLAY ── -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBackdrop(event)">
  <div class="cart-panel">
    <div class="cart-header">
      <h3>Giỏ hàng</h3>
      <button class="close-btn" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
      <div style="text-align:center;padding:48px 0">
        <div style="font-size:40px;margin-bottom:12px">🛒</div>
        <p style="color:var(--text-muted);font-size:14px">Giỏ hàng trống</p>
      </div>
    </div>
    <div class="cart-footer">
      <div class="cart-total">
        <span class="ct-label">Tổng cộng</span>
        <span class="ct-value" id="cartTotal">0đ</span>
      </div>
      <button class="btn-checkout" onclick="window.location.href='<?= SITE_URL ?>/checkout.php'">Tiến hành thanh toán →</button>
    </div>
  </div>
</div>

<!-- ══ NAV ══ -->
<nav>
  <div class="nav-inner">
    <a class="logo" href="<?= SITE_URL ?>/index.php">
      <img src="<?= SITE_URL ?>/images/logo.png" alt="FROMSHOPWHERE"
           style="height:44px;width:auto;object-fit:contain;filter:drop-shadow(0 0 6px rgba(0,0,0,.3))">
    </a>

    <ul class="nav-links">
      <li><a href="<?= SITE_URL ?>/index.php"    <?= $_currentPage==='home'     ?'class="active"':'' ?>>Trang chủ</a></li>
      <li><a href="<?= SITE_URL ?>/products.php" <?= $_currentPage==='products' ?'class="active"':'' ?>>Sản phẩm</a></li>
      <li><a href="<?= SITE_URL ?>/blog.php"     <?= $_currentPage==='blog'     ?'class="active"':'' ?>>Blog</a></li>
      <li><a href="<?= SITE_URL ?>/contact.php"  <?= $_currentPage==='contact'  ?'class="active"':'' ?>>Liên hệ</a></li>
    </ul>

    <div class="nav-right">
      <div class="search-wrap">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input class="search-box" type="search" placeholder="Tìm phần mềm..."
               onkeydown="if(event.key==='Enter')window.location.href='<?= SITE_URL ?>/products.php?q='+encodeURIComponent(this.value)">
      </div>

      <button class="theme-toggle" onclick="toggleTheme()" title="Chuyển sáng/tối" aria-label="Theme">
        <div class="theme-knob" id="themeKnob">☀️</div>
      </button>

      <div class="cart-btn" onclick="toggleCart()">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 01-8 0"/>
        </svg>
        <span class="cart-badge" id="cartCount">0</span>
      </div>

      <?php if ($_user): ?>
        <div style="position:relative">
          <button class="btn-login"
                  onclick="document.getElementById('userMenu').classList.toggle('open')"
                  style="cursor:pointer;display:flex;align-items:center;gap:6px">
            <span style="font-size:16px">👤</span>
            <?= e($_user['ho_ten']) ?> <span style="font-size:10px;opacity:.7">▾</span>
          </button>
          <div id="userMenu" class="user-dropdown">
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/">⚙️ Quản trị Admin</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/profile.php">👤 Tài khoản</a>
            <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
          </div>
        </div>
      <?php else: ?>
        <a class="btn-login" href="<?= SITE_URL ?>/login.php">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<style>
.user-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:6px;min-width:170px;box-shadow:0 8px 32px rgba(0,0,0,.2);z-index:300;display:none;flex-direction:column;gap:2px}
.user-dropdown.open{display:flex}
.user-dropdown a{padding:9px 13px;border-radius:8px;text-decoration:none;color:var(--text);font-size:13px;font-weight:500;transition:background .12s}
.user-dropdown a:hover{background:var(--bg-alt);color:var(--green-600,#0A8A4C)}
</style>
<script>
document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});
</script>

<div class="breadcrumb">
  <a href="index.php">Trang chủ</a>
  <span>/</span>
  <a href="products.php">Sản phẩm</a>
  <span>/</span>
  <a href="products.php"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></a>
  <span>/</span>
  <span style="color:var(--text)"><?php echo htmlspecialchars($product['ten_san_pham']); ?></span>
</div>

<div class="pd-wrap">
  <div class="pd-grid">
    
    <div class="pd-left">
      <div class="pd-gallery">
        <img class="pd-main-img" src="<?php echo htmlspecialchars($productImage); ?>" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>">
      </div>
    </div>

    <div class="pd-right">
      <div class="pd-cat"><?php echo htmlspecialchars($product['ten_danh_muc']); ?></div>
      <h2 class="pd-title"><?php echo htmlspecialchars($product['ten_san_pham']); ?></h2>
      
      <div class="pd-meta">
        <div class="pd-stars">★★★★★ 5.0</div>
        <div>•</div>
        <div>Đã bán 1.2k+</div>
        <div>•</div>
        <div>Mã SP: FSW-<?php echo $product['id']; ?></div>
      </div>

      <div class="pd-price-box">
        <span class="pd-price"><?php echo formatVND($product['gia_ban']); ?></span>
        <?php if (!empty($product['gia_goc']) && $product['gia_goc'] > $product['gia_ban']): ?>
          <span class="pd-old-price"><?php echo formatVND($product['gia_goc']); ?></span>
        <?php endif; ?>
      </div>

      <div class="pd-desc">
        <?php echo nl2br(htmlspecialchars($product['mo_ta'] ?? 'Sản phẩm phần mềm kích hoạt bản quyền chính hãng, hỗ trợ cập nhật và bảo hành trọn đời tại FROMSHOPWHERE.')); ?>
      </div>

      <div class="pd-actions">
        <button class="btn-buy-now" 
    onclick="buyNow(
        <?= (int)$product['id'] ?>, 
        '<?= addslashes($product['ten_san_pham']) ?>', 
        <?= (float)$product['gia_ban'] ?>, 
        '<?= addslashes($product['hinh_anh']) ?>'
    )">
    Mua ngay
</button>
        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>,'<?php echo addslashes($product['ten_san_pham']); ?>',<?php echo (float)$product['gia_ban']; ?>,'<?php echo addslashes($product['hinh_anh'] ?? ''); ?>')">Thêm giỏ hàng</button>
      </div>

      <div class="pd-features">
        <div class="pd-feat-item">
          <span class="pd-feat-icon">✓</span>
          <span>Giao hàng tự động trong 5 giây qua Email</span>
        </div>
        <div class="pd-feat-item">
          <span class="pd-feat-icon">✓</span>
          <span>Key chính hãng 100% bảo hành trọn đời ổn định</span>
        </div>
        <div class="pd-feat-item">
          <span class="pd-feat-icon">✓</span>
          <span>Hỗ trợ kỹ thuật 24/7 Ultraview/Teamview miễn phí</span>
        </div>
      </div>
    </div>

  </div>

  <div class="pd-tabs-nav">
    <button class="pd-tab-btn active" onclick="switchTab(this,'desc')">Chi tiết sản phẩm</button>
    <button class="pd-tab-btn" onclick="switchTab(this,'reviews')">Đánh giá khách hàng (3)</button>
  </div>

  <div id="tabDesc" class="pd-tab-content">
     <p><?php echo nl2br(htmlspecialchars($product['mo_ta'] ?? 'Không có thông tin mô tả chi tiết thêm cho sản phẩm này.')); ?></p>
  </div>

  <div id="tabReviews" class="pd-tab-content" style="display:none">
     <div id="reviewGrid"></div>
  </div>

  <div class="pd-related">
    <h3>Sản phẩm liên quan</h3>
    <div class="products" id="relatedGrid"></div>
  </div>
</div>

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
  const ALL_PRODUCTS = <?php echo json_encode($jsAllProducts); ?>;
  
  const CURRENT_PRODUCT = {
      id: <?php echo (int)$product['id']; ?>,
      name: <?php echo json_encode($product['ten_san_pham']); ?>,
      cat: <?php echo json_encode($product['ten_danh_muc']); ?>
  };

  function fmtVND(num) {
    return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
  }

  function switchTab(btn, tab) {
    document.querySelectorAll('.pd-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if(tab==='desc') {
      document.getElementById('tabDesc').style.display='block';
      document.getElementById('tabReviews').style.display='none';
    } else {
      document.getElementById('tabDesc').style.display='none';
      document.getElementById('tabReviews').style.display='block';
    }
  }

  function renderGrid(targetId, list) {
    const grid = document.getElementById(targetId);
    if(!grid) return;
    const siteUrl = '<?= SITE_URL ?>';
    grid.innerHTML = list.map(p => {
      const discount = (p.oldPrice && p.oldPrice > p.price) ? Math.round((1 - p.price/p.oldPrice)*100) : 0;
      const discBadge = discount > 0 ? `<span style="background:#D92B2B;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:20px;margin-left:5px">-${discount}%</span>` : '';
      const oldPriceHtml = (p.oldPrice && p.oldPrice > p.price) ? `<span style="text-decoration:line-through;color:#aaa;font-size:12px;margin-left:6px">${fmtVND(p.oldPrice)}</span>` : '';
      const newTag = p.isNew ? `<span style="background:red;color:#fff;font-size:10px;padding:2px 6px;border-radius:3px;margin-left:5px;font-weight:700">MỚI</span>` : '';
      const nameSafe = (p.name||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
      const imgSafe  = (p.image||'').replace('images/', '').replace(/'/g,"\\'");
      return `
      <div class="hero-card prod-card-wrap" style="display:flex;flex-direction:column;cursor:default">
        <div class="hc-icon" style="cursor:pointer;background:#E1F5EE"
             onclick="window.location.href='product-demo.php?id=${p.id}'">
          <img src="${p.image}" alt="${p.name}" onerror="this.src='images/default.jpg'">
        </div>
        <div class="hc-name" style="cursor:pointer;margin-top:10px"
             onclick="window.location.href='product-demo.php?id=${p.id}'">${p.name}${newTag}</div>
        <div class="hc-price" style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;margin-bottom:8px">
          ${fmtVND(p.price)}${oldPriceHtml}${discBadge}
        </div>
        <div class="hc-tag" style="margin-bottom:10px">${p.cat}</div>
        <div style="display:flex;gap:8px;margin-top:auto">
          <button class="btn-atc" onclick="addToCart(${p.id},'${nameSafe}',${p.price},'${imgSafe}')" title="Thêm vào giỏ">🛒 Thêm vào giỏ</button>
          <button class="btn-detail" onclick="window.location.href='product-demo.php?id=${p.id}'" title="Chi tiết">Chi tiết</button>
        </div>
      </div>`;
    }).join('');
  }

  function buildReviews() {
    const fake = [
      { name: "Nguyễn Văn A", role: "Nhà thiết kế đồ họa", stars: 5, date: "Hôm qua", text: "Sản phẩm dùng cực kì mượt mà, kích hoạt bản quyền nhanh trong 5 giây đúng cam kết!" },
      { name: "Trần Thị B", role: "Kế toán viên", stars: 5, date: "3 ngày trước", text: "Giá rất rẻ so với thị trường, nhân viên hỗ trợ cài đặt qua Ultraview siêu nhiệt tình." },
      { name: "Lê Minh C", role: "Developer", stars: 4, date: "1 tuần trước", text: "Đã mua lần thứ 2 tại shop, cực kì uy tín và an tâm." }
    ];
    const grid = document.getElementById('reviewGrid');
    if(!grid) return;
    grid.innerHTML = fake.map(r => `
      <div class="pd-rv-card">
        <div class="pd-rv-header">
          <div class="pd-rv-user">
            <div class="pd-rv-avatar">${r.name[0]}</div>
            <div>
              <div class="pd-rv-name">${r.name}</div>
              <div class="pd-rv-role">${r.role}</div>
            </div>
          </div>
          <div style="text-align:right">
            <div class="pd-rv-stars">${'★'.repeat(r.stars)}${'☆'.repeat(5-r.stars)}</div>
            <div class="pd-rv-date">${r.date}</div>
          </div>
        </div>
        <div class="pd-rv-body">${r.text}</div>
        <div class="pd-rv-verified">✓ Đã mua sản phẩm này</div>
      </div>
    `).join('');
  }

  function buildRelated() {
    const related = ALL_PRODUCTS
      .filter(x => x.id !== CURRENT_PRODUCT.id && x.cat === CURRENT_PRODUCT.cat)
      .slice(0, 4);
    if(related.length === 0) {
      document.querySelector('.pd-related').style.display = 'none';
      return;
    }
    renderGrid('relatedGrid', related);
  }

  document.addEventListener('DOMContentLoaded', () => {
    if(typeof restoreTheme === 'function') restoreTheme();
    if(typeof updateCartBadge === 'function') updateCartBadge();
    if(typeof updateLoginBtn === 'function') updateLoginBtn();
    if(typeof syncCartPanel === 'function') syncCartPanel();
    
    buildReviews();
    buildRelated();
  });
</script>
</body>
</html>