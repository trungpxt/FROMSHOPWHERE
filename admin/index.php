<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

try {
  $total_orders   = (int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
  $total_users    = (int)db()->query("SELECT COUNT(*) FROM users WHERE vai_tro='khach'")->fetchColumn();
  $total_products = (int)db()->query("SELECT COUNT(*) FROM products WHERE trang_thai='hien'")->fetchColumn();
  $total_posts    = (int)db()->query("SELECT COUNT(*) FROM posts WHERE trang_thai='da_dang'")->fetchColumn();
  $revenue        = (float)db()->query("SELECT COALESCE(SUM(tong_tien),0) FROM orders WHERE trang_thai IN ('da_thanh_toan','hoan_thanh')")->fetchColumn();
  $pending        = (int)db()->query("SELECT COUNT(*) FROM orders WHERE trang_thai='cho_xu_ly'")->fetchColumn();
  $recent_orders  = db()->query("SELECT o.*,u.ho_ten,u.email FROM orders o JOIN users u ON u.id=o.nguoi_dung_id ORDER BY o.ngay_dat DESC LIMIT 8")->fetchAll();
  $recent_users   = db()->query("SELECT * FROM users ORDER BY ngay_tao DESC LIMIT 5")->fetchAll();
} catch(Exception $e) {
  $total_orders=0;$total_users=0;$total_products=0;$total_posts=0;$revenue=0;$pending=0;$recent_orders=[];$recent_users=[];
}

$admPageTitle = 'Dashboard — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel = 'Dashboard';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <?php include __DIR__ . '/../includes/admin-topbar.php'; ?>

    <div class="adm-content">

      <div class="adm-welcome" style="margin-bottom:22px">
        <h1>Xin chào, <?= e($_SESSION['user_name'] ?? 'Admin') ?> 👋</h1>
        <p>Đây là tổng quan hoạt động của FROMSHOPWHERE hôm nay.</p>
      </div>

      <!-- STATS -->
      <div class="stats-grid stats-grid-4" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-icon si-green">💰</div>
          <div>
            <div class="stat-num"><?= fmtVND($revenue) ?></div>
            <div class="stat-lbl">Doanh thu</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-blue">🛒</div>
          <div>
            <div class="stat-num"><?= $total_orders ?></div>
            <div class="stat-lbl">Đơn hàng</div>
          </div>
          <?php if($pending > 0): ?>
          <span class="stat-delta sd-down" style="margin-left:auto"><?= $pending ?> chờ</span>
          <?php endif; ?>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-purple">👥</div>
          <div>
            <div class="stat-num"><?= $total_users ?></div>
            <div class="stat-lbl">Khách hàng</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-yellow">📦</div>
          <div>
            <div class="stat-num"><?= $total_products ?></div>
            <div class="stat-lbl">Sản phẩm</div>
          </div>
        </div>
      </div>

      <!-- TWO COLUMN -->
      <div class="adm-dash-grid">

        <!-- RECENT ORDERS -->
        <div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <span style="font-size:14px;font-weight:800">🛒 Đơn hàng gần đây</span>
            <a href="<?= SITE_URL ?>/admin/orders.php" style="font-size:12px;color:var(--green);font-weight:700">Xem tất cả →</a>
          </div>
          <div class="table-card">
            <table>
              <thead>
                <tr><th>#</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày đặt</th></tr>
              </thead>
              <tbody>
              <?php if(empty($recent_orders)): ?>
                <tr><td colspan="5" class="table-empty"><span class="te-icon">🛒</span>Chưa có đơn hàng nào.</td></tr>
              <?php endif; ?>
              <?php foreach($recent_orders as $o):
                [$cls,$lbl] = match($o['trang_thai']){
                  'da_thanh_toan'=>['b-green','Đã TT'],
                  'hoan_thanh'  =>['b-blue','Hoàn thành'],
                  'huy'         =>['b-red','Huỷ'],
                  default       =>['b-yellow','Chờ xử lý']
                };
              ?>
              <tr>
                <td class="mono text-muted2">#<?= $o['id'] ?></td>
                <td>
                  <div class="order-name"><?= e($o['ho_ten']) ?></div>
                  <div class="order-email"><?= e($o['email']) ?></div>
                </td>
                <td class="price-main"><?= fmtVND($o['tong_tien']) ?></td>
                <td><span class="badge <?= $cls ?>"><span class="badge-dot"></span><?= $lbl ?></span></td>
                <td class="text-muted2 mono"><?= date('d/m/Y',strtotime($o['ngay_dat'])) ?></td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div style="display:flex;flex-direction:column;gap:16px">

          <!-- Quick links -->
          <div class="table-card" style="padding:16px">
            <div style="font-size:13px;font-weight:800;margin-bottom:12px">⚡ Thao tác nhanh</div>
            <div style="display:flex;flex-direction:column;gap:6px">
              <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn-secondary" style="justify-content:flex-start;font-size:13px">📦 Thêm sản phẩm</a>
              <a href="<?= SITE_URL ?>/admin/posts.php" class="btn btn-primary" style="justify-content:flex-start;font-size:13px">✍️ Viết bài mới</a>
              <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-secondary" style="justify-content:flex-start;font-size:13px">🛒 Quản lý đơn hàng</a>
              <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-secondary" style="justify-content:flex-start;font-size:13px">👥 Danh sách người dùng</a>
            </div>
          </div>

          <!-- Recent users -->
          <div class="table-card" style="padding:16px">
            <div style="font-size:13px;font-weight:800;margin-bottom:12px">👥 Thành viên mới</div>
            <?php $ucolors=['#065E34','#185FA5','#534AB7','#A32D2D','#BA7517'];
            foreach($recent_users as $u):
              $uc = $ucolors[$u['id'] % count($ucolors)];
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--bg-2)">
              <div class="user-av" style="background:<?= $uc ?>"><?= strtoupper(mb_substr($u['ho_ten'],0,1)) ?></div>
              <div>
                <div style="font-size:13px;font-weight:700"><?= e($u['ho_ten']) ?></div>
                <div class="mono text-muted2"><?= e($u['email']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Post stats -->
          <div class="stat-card">
            <div class="stat-icon si-lime">✍️</div>
            <div>
              <div class="stat-num"><?= $total_posts ?></div>
              <div class="stat-lbl">Bài viết</div>
            </div>
            <a href="<?= SITE_URL ?>/admin/posts.php" style="margin-left:auto;font-size:12px;color:var(--green);font-weight:700">Xem →</a>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>
</body>
</html>