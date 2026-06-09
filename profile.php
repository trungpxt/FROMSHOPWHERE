<?php
require_once __DIR__ . '/config.php';
startSession();
if (!isLoggedIn()) redirect(SITE_URL . '/login.php?redirect=' . urlencode(SITE_URL . '/profile.php'));

$msg = ''; $error = '';
$user = currentUser();

// Lấy thông tin đầy đủ từ DB
$u = db()->prepare("SELECT * FROM users WHERE id=:id");
$u->execute([':id' => $user['id']]);
$u = $u->fetch();

// Lấy danh sách đơn hàng
$ordersStmt = db()->prepare("
    SELECT o.*
    FROM orders o
    WHERE o.nguoi_dung_id = :id
    ORDER BY o.ngay_dat DESC
    LIMIT 10
");
$ordersStmt->execute([':id' => $user['id']]);
$orders = $ordersStmt->fetchAll();

// Lấy chi tiết sản phẩm từng đơn (tên + ảnh + số lượng)
$orderItemsByOrder = [];
if (!empty($orders)) {
    $ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $itemsStmt = db()->prepare("
        SELECT
            oi.don_hang_id,
            oi.so_luong,
            oi.don_gia,
            p.ten_san_pham,
            p.hinh_anh
        FROM order_items oi
        JOIN products p ON p.id = oi.san_pham_id
        WHERE oi.don_hang_id IN ($placeholders)
        ORDER BY oi.id ASC
    ");
    $itemsStmt->execute($ids);
    foreach ($itemsStmt->fetchAll() as $row) {
        $orderItemsByOrder[$row['don_hang_id']][] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

if ($action === 'update_info') {
    $ten = trim($_POST['ho_ten'] ?? '');
    $sdt = trim($_POST['so_dien_thoai'] ?? '');
    $dia = trim($_POST['dia_chi'] ?? '');

    if (!$ten) {
        $error = 'Họ tên không được để trống.';
    } elseif ($sdt !== '' && !isValidVnPhonePhp($sdt)) {
        // SĐT để trống vẫn cho lưu; có nhập thì phải đúng format
        $error = 'Số điện thoại không đúng (10 số, bắt đầu 0).';
    } else {
        $sdtLuu = $sdt !== '' ? normalizePhonePhp($sdt) : null;
        db()->prepare("UPDATE users SET ho_ten=:t, so_dien_thoai=:s, dia_chi=:d WHERE id=:id")
           ->execute([':t' => $ten, ':s' => $sdtLuu, ':d' => $dia, ':id' => $user['id']]);
        $_SESSION['user_name'] = $ten;
        $msg = '✓ Đã cập nhật thông tin.';
        $u['ho_ten'] = $ten;
        $u['so_dien_thoai'] = $sdtLuu;
        $u['dia_chi'] = $dia;
    }
}
    

    if ($action === 'change_pass') {
        $old  = $_POST['old_pass']  ?? '';
        $new  = $_POST['new_pass']  ?? '';
        $new2 = $_POST['new_pass2'] ?? '';
        if (!$old || !$new) { $error = 'Vui lòng điền đầy đủ.'; }
        elseif (!password_verify($old, $u['mat_khau'])) { $error = 'Mật khẩu cũ không đúng.'; }
        elseif (strlen($new) < 6) { $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.'; }
        elseif ($new !== $new2)   { $error = 'Mật khẩu nhập lại không khớp.'; }
        else {
            db()->prepare("UPDATE users SET mat_khau=:p WHERE id=:id")
               ->execute([':p'=>password_hash($new,PASSWORD_BCRYPT),':id'=>$user['id']]);
            $msg = '✓ Đã đổi mật khẩu thành công.';
        }
    }
}

$currentPage = '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tài khoản — FROMSHOPWHERE</title>
<link rel="stylesheet" href="style.css">
<style>
.profile-wrap{max-width:860px;margin:0 auto;padding:40px 24px}
.profile-grid{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start}
.profile-side{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:24px;text-align:center;position:sticky;top:80px}
.profile-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--green-600),var(--green-800));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:var(--lime,#C8FF00);margin:0 auto 14px;font-family:'Space Grotesk',sans-serif}
.profile-name{font-size:16px;font-weight:700;margin-bottom:4px}
.profile-email{font-size:12px;color:var(--text-muted);margin-bottom:16px}
.profile-role{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;background:var(--green-50);color:var(--green-700,#065E34)}
.profile-tabs{display:flex;gap:2px;margin-bottom:20px;background:var(--bg-alt);border-radius:10px;padding:4px}
.ptab{flex:1;text-align:center;padding:9px;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;color:var(--text-muted);border:none;background:none;font-family:'Inter',sans-serif;transition:all .2s}
.ptab.on{background:var(--card-bg);color:var(--text);box-shadow:0 1px 4px rgba(0,0,0,.1)}
.tab-content{display:none}.tab-content.on{display:block}
.info-box{background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:16px}
.info-box h3{font-size:15px;font-weight:700;margin:0 0 18px;font-family:'Space Grotesk',sans-serif}
.msg-ok{background:#D1FAE5;color:#065F46;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;border:1px solid #6EE7B7}
.msg-err{background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px}
table.ord-tbl{width:100%;border-collapse:collapse;font-size:13px}
.ord-tbl th{padding:9px 12px;background:var(--bg-alt);color:var(--text-muted);text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700}
.ord-tbl td{padding:10px 12px;border-bottom:1px solid var(--border)}
.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700}
.b-cho{background:#FEF3C7;color:#92400E}.b-tt{background:#D1FAE5;color:#065F46}.b-hoan{background:#DBEAFE;color:#1E40AF}.b-huy{background:#FEE2E2;color:#991B1B}
@media(max-width:600px){.profile-grid{grid-template-columns:1fr}.profile-side{position:static}}
</style>
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

<div class="page-header">
  <div class="page-header-inner">
    <h1>Tài khoản của tôi</h1>
    <p>Quản lý thông tin và lịch sử đơn hàng</p>
  </div>
</div>

<div class="profile-wrap">
  <?php if($msg):?><div class="msg-ok"><?= $msg ?></div><?php endif;?>
  <?php if($error):?><div class="msg-err">⚠ <?= e($error) ?></div><?php endif;?>

  <div class="profile-grid">
    <!-- Sidebar -->
    <div class="profile-side">
      <div class="profile-avatar"><?= strtoupper(mb_substr($u['ho_ten'],0,1)) ?></div>
      <div class="profile-name"><?= e($u['ho_ten']) ?></div>
      <div class="profile-email"><?= e($u['email']) ?></div>
      <div class="profile-role"><?= $u['vai_tro']==='admin' ? '⚙️ Admin' : '👤 Khách hàng' ?></div>
      <?php if($u['vai_tro']==='admin'): ?>
      <a href="<?= SITE_URL ?>/admin/" class="btn-primary" style="display:block;margin-top:14px;font-size:13px;text-align:center">⚙️ Vào trang Admin</a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/logout.php" style="display:block;margin-top:10px;color:var(--text-muted);font-size:13px;text-decoration:none">🚪 Đăng xuất</a>
    </div>

    <!-- Main -->
    <div>
      <div class="profile-tabs">
        <button class="ptab on" onclick="showTab('info',this)">👤 Thông tin</button>
        <button class="ptab" onclick="showTab('pass',this)">🔒 Đổi mật khẩu</button>
        <button class="ptab" onclick="showTab('orders',this)">🛒 Đơn hàng</button>
      </div>

      <!-- Tab: Thông tin -->
      <div class="tab-content on" id="tab-info">
        <div class="info-box">
          <h3>Thông tin cá nhân</h3>
          <form method="POST">
            <input type="hidden" name="action" value="update_info">
            <div class="form-group">
              <label class="form-label">Họ và tên</label>
              <input class="form-input" type="text" name="ho_ten" required value="<?= e($u['ho_ten']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Email <span style="color:var(--text-muted);font-weight:400">(không thể đổi)</span></label>
              <input class="form-input" type="email" value="<?= e($u['email']) ?>" disabled style="opacity:.6">
            </div>
            <div class="form-group">
              <label class="form-label">Số điện thoại</label>
              <input class="form-input" type="tel" name="so_dien_thoai" id="profilePhone"
                     placeholder="0901234567" inputmode="numeric" maxlength="10"
                     pattern="0(3|5|7|8|9)[0-9]{8}"
                     title="Số điện thoại VN: 10 số, đầu 03/05/07/08/09"
                     value="<?= e($u['so_dien_thoai']??'') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Địa chỉ</label>
              <textarea class="form-input" name="dia_chi" rows="2" style="resize:vertical"><?= e($u['dia_chi']??'') ?></textarea>
            </div>
            <button type="submit" class="btn-submit" style="width:auto;padding:11px 28px">💾 Lưu thay đổi</button>
          </form>
        </div>
      </div>

      <!-- Tab: Đổi mật khẩu -->
      <div class="tab-content" id="tab-pass">
        <div class="info-box">
          <h3>Đổi mật khẩu</h3>
          <form method="POST">
            <input type="hidden" name="action" value="change_pass">
            <div class="form-group">
              <label class="form-label">Mật khẩu hiện tại</label>
              <input class="form-input" type="password" name="old_pass" required placeholder="••••••••">
            </div>
            <div class="form-group">
              <label class="form-label">Mật khẩu mới</label>
              <input class="form-input" type="password" name="new_pass" required placeholder="Tối thiểu 6 ký tự">
            </div>
            <div class="form-group">
              <label class="form-label">Nhập lại mật khẩu mới</label>
              <input class="form-input" type="password" name="new_pass2" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-submit" style="width:auto;padding:11px 28px">🔒 Đổi mật khẩu</button>
          </form>
        </div>
      </div>

      <!-- Tab: Đơn hàng -->
      <div class="tab-content" id="tab-orders">
        <div class="info-box">
          <h3>Lịch sử đơn hàng</h3>
          <?php if(empty($orders)): ?>
            <div style="text-align:center;padding:32px 0;color:var(--text-muted)">
              <div style="font-size:40px;margin-bottom:12px">🛒</div>
              <p>Chưa có đơn hàng nào.</p>
              <a class="btn-primary" href="<?= SITE_URL ?>/products.php" style="display:inline-block;margin-top:12px;font-size:13px">Mua ngay →</a>
            </div>
          <?php else: ?>
          <table class="ord-tbl">
            <thead><tr><th>#</th><th>Sản phẩm</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày đặt</th></tr></thead>
            <tbody>
            <?php foreach($orders as $o):
              [$cls,$lbl]=match($o['trang_thai']){
                'da_thanh_toan'=>['b-tt','Đã thanh toán'],
                'hoan_thanh'   =>['b-hoan','Hoàn thành'],
                'huy'          =>['b-huy','Đã huỷ'],
                default        =>['b-cho','Chờ xử lý'],
              };
            ?>
            <tr>
              <td style="font-weight:700;color:var(--green-600,#0A8A4C)">#<?= $o['id']?></td>
<td>
  <?php
    $items = $orderItemsByOrder[$o['id']] ?? [];
    if (empty($items)):
  ?>
    <span style="color:var(--text-muted);font-size:13px">—</span>
  <?php else: ?>
    <div class="ord-products">
      <?php foreach ($items as $it):
        $imgPath = $it['hinh_anh'] ?? '';
        if ($imgPath === '') {
            $imgUrl = SITE_URL . '/images/default.jpg';
        } else {
            $path = str_contains($imgPath, '/') ? $imgPath : 'products/' . $imgPath;
            $imgUrl = SITE_URL . '/images/' . ltrim($path, '/');
        }
        $lineTotal = (float)$it['don_gia'] * (int)$it['so_luong'];
      ?>
      <div class="ord-product-row">
        <img src="<?= e($imgUrl) ?>" alt="<?= e($it['ten_san_pham']) ?>"
             width="48" height="48"
             onerror="this.src='<?= SITE_URL ?>/images/default.jpg'">
        <div>
          <div class="ord-product-name"><?= e($it['ten_san_pham']) ?></div>
          <div class="ord-product-meta">×<?= (int)$it['so_luong'] ?> · <?= fmtVND($lineTotal) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</td>
              <td style="font-weight:700;color:var(--green-600,#0A8A4C)"><?= fmtVND($o['tong_tien'])?></td>
              <td><span class="badge <?= $cls?>"><?= $lbl?></span></td>
              <td style="color:var(--text-muted)"><?= date('d/m/Y H:i',strtotime($o['ngay_dat']))?></td>
            </tr>
            <?php endforeach;?>
            </tbody>
          </table>
          <?php endif;?>
        </div>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="footer-inner">
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> FROMSHOPWHERE. Bảo lưu mọi quyền.</p>
      <div class="pay-icons">
        <div class="pay-badge">VISA</div><div class="pay-badge">MC</div>
        <div class="pay-badge">MOMO</div><div class="pay-badge">ZALO</div>
      </div>
    </div>
  </div>
</footer>

<script src="shared.js"></script>
<script>
function showTab(name, btn) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('on'));
  document.querySelectorAll('.ptab').forEach(b => b.classList.remove('on'));
  document.getElementById('tab-' + name).classList.add('on');
  btn.classList.add('on');
}
document.addEventListener('DOMContentLoaded', () => {
  restoreTheme(); updateCartBadge(); syncCartPanel();
  bindVnPhoneInput(document.getElementById('profilePhone'));
  <?php if($msg && str_contains($msg,'mật khẩu')): ?>
  showTab('pass', document.querySelectorAll('.ptab')[1]);
  <?php endif; ?>
  const params = new URLSearchParams(window.location.search);
if (params.get('tab') === 'orders') {
  const ordersTab = document.querySelectorAll('.ptab')[2];
  if (ordersTab) showTab('orders', ordersTab);
}
});

</script>
</body>
</html>
