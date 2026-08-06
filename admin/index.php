<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

try {
  $total_orders   = (int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn();
  $total_users    = (int)db()->query("SELECT COUNT(*) FROM users WHERE vai_tro='khach_hang'")->fetchColumn();
  $total_products = (int)db()->query("SELECT COUNT(*) FROM products WHERE trang_thai='hien'")->fetchColumn();
  $total_posts    = (int)db()->query("SELECT COUNT(*) FROM posts WHERE trang_thai='da_dang'")->fetchColumn();
  $revenue        = (float)db()->query("SELECT COALESCE(SUM(tong_tien),0) FROM orders WHERE trang_thai IN ('da_thanh_toan','hoan_thanh')")->fetchColumn();
  $pending        = (int)db()->query("SELECT COUNT(*) FROM orders WHERE trang_thai='cho_xu_ly'")->fetchColumn();
  $recent_orders  = db()->query("SELECT o.*,u.ho_ten,u.email FROM orders o JOIN users u ON u.id=o.nguoi_dung_id ORDER BY o.ngay_dat DESC LIMIT 8")->fetchAll();
  $recent_users   = db()->query("SELECT * FROM users ORDER BY ngay_tao DESC LIMIT 5")->fetchAll();

  /* Doanh thu 14 ngày gần nhất (đơn đã thanh toán/hoàn thành) */
  $revByDayRaw = db()->query(
    "SELECT DATE(ngay_dat) AS d, SUM(tong_tien) AS total
     FROM orders
     WHERE trang_thai IN ('da_thanh_toan','hoan_thanh')
       AND ngay_dat >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
     GROUP BY DATE(ngay_dat)"
  )->fetchAll(PDO::FETCH_KEY_PAIR);
  $revenueByDay = [];
  for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $revenueByDay[$d] = (float)($revByDayRaw[$d] ?? 0);
  }

  /* Top 5 sản phẩm bán chạy nhất (theo số lượng, chỉ tính đơn đã thanh toán/hoàn thành) */
  $topProducts = db()->query(
    "SELECT p.id, p.ten_san_pham, p.hinh_anh,
            SUM(oi.so_luong) AS sold_qty,
            SUM(oi.so_luong * oi.don_gia) AS sold_revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.don_hang_id
     JOIN products p ON p.id = oi.san_pham_id
     WHERE o.trang_thai IN ('da_thanh_toan','hoan_thanh')
     GROUP BY p.id
     ORDER BY sold_qty DESC
     LIMIT 5"
  )->fetchAll();
} catch(Exception $e) {
  $total_orders=0;$total_users=0;$total_products=0;$total_posts=0;$revenue=0;$pending=0;$recent_orders=[];$recent_users=[];
  $revenueByDay=[];$topProducts=[];
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

      <!-- REVENUE CHART + TOP PRODUCTS -->
      <div class="adm-dash-grid" style="margin-bottom:24px">

        <div class="table-card" style="padding:18px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <span style="font-size:14px;font-weight:800">📈 Doanh thu 14 ngày gần nhất</span>
            <span style="font-size:12px;color:var(--text-muted2,#94a3b8)">Đơn đã thanh toán / hoàn thành</span>
          </div>
          <?php
            $chartW = 640; $chartH = 200; $padL = 44; $padB = 26; $padT = 10;
            $plotW = $chartW - $padL - 10;
            $plotH = $chartH - $padT - $padB;
            $maxRev = max(1, max($revenueByDay ?: [0]));
            $days = array_keys($revenueByDay);
            $n = max(1, count($days) - 1);
            $points = [];
            foreach (array_values($revenueByDay) as $i => $v) {
                $x = $padL + ($n > 0 ? ($i / $n) * $plotW : 0);
                $y = $padT + $plotH - ($v / $maxRev) * $plotH;
                $points[] = [$x, $y];
            }
            $linePath = '';
            foreach ($points as $i => [$x, $y]) { $linePath .= ($i === 0 ? "M $x $y" : " L $x $y"); }
            $areaPath = $linePath;
            if ($points) {
                [$lastX] = end($points); [$firstX] = $points[0];
                $areaPath .= " L $lastX " . ($padT + $plotH) . " L $firstX " . ($padT + $plotH) . " Z";
            }
          ?>
          <?php if ($maxRev <= 1): ?>
            <div class="table-empty" style="padding:40px 0"><span class="te-icon">📈</span>Chưa có dữ liệu doanh thu trong 14 ngày qua.</div>
          <?php else: ?>
          <svg viewBox="0 0 <?= $chartW ?> <?= $chartH ?>" style="width:100%;height:auto;overflow:visible">
            <?php for ($g = 0; $g <= 3; $g++):
                $gy = $padT + $plotH - ($g / 3) * $plotH;
                $gv = round($maxRev * $g / 3);
            ?>
              <line x1="<?= $padL ?>" y1="<?= $gy ?>" x2="<?= $chartW - 10 ?>" y2="<?= $gy ?>" stroke="var(--bg-2, #e5e7eb)" stroke-width="1"/>
              <text x="0" y="<?= $gy + 4 ?>" font-size="9" fill="var(--text-muted2, #94a3b8)"><?= $gv >= 1000000 ? round($gv/1000000,1).'tr' : ($gv >= 1000 ? round($gv/1000).'k' : $gv) ?></text>
            <?php endfor; ?>
            <path d="<?= $areaPath ?>" fill="var(--green, #16a34a)" opacity="0.12"/>
            <path d="<?= $linePath ?>" fill="none" stroke="var(--green, #16a34a)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
            <?php foreach ($points as $i => [$x, $y]):
                $label = date('d/m', strtotime($days[$i]));
                $val = $revenueByDay[$days[$i]];
            ?>
              <circle cx="<?= $x ?>" cy="<?= $y ?>" r="3" fill="var(--green, #16a34a)">
                <title><?= $label ?>: <?= fmtVND($val) ?></title>
              </circle>
              <?php if ($i % 2 === 0 || $i === count($points) - 1): ?>
              <text x="<?= $x ?>" y="<?= $chartH - 6 ?>" font-size="9" fill="var(--text-muted2, #94a3b8)" text-anchor="middle"><?= $label ?></text>
              <?php endif; ?>
            <?php endforeach; ?>
          </svg>
          <?php endif; ?>
        </div>

        <div class="table-card" style="padding:16px">
          <div style="font-size:13px;font-weight:800;margin-bottom:12px">🏆 Top sản phẩm bán chạy</div>
          <?php if (empty($topProducts)): ?>
            <div class="table-empty" style="padding:20px 0"><span class="te-icon">🏆</span>Chưa có dữ liệu.</div>
          <?php else: ?>
            <?php foreach ($topProducts as $i => $tp):
              $imgPath = $tp['hinh_anh'] ?? '';
              $imgUrl  = $imgPath === '' ? SITE_URL.'/images/ui/default.jpg' : SITE_URL.'/images/'.ltrim(str_contains($imgPath,'/') ? $imgPath : 'products/'.$imgPath, '/');
            ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--bg-2)">
              <span style="font-weight:800;font-size:12px;color:var(--text-muted2,#94a3b8);width:16px">#<?= $i+1 ?></span>
              <img src="<?= e($imgUrl) ?>" alt="" width="34" height="34" style="border-radius:8px;object-fit:cover;background:var(--bg-2,#f1f5f9)" onerror="this.src='<?= SITE_URL ?>/images/ui/default.jpg'">
              <div style="flex:1;min-width:0">
                <div style="font-size:12.5px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($tp['ten_san_pham']) ?></div>
                <div class="mono text-muted2" style="font-size:11px"><?= (int)$tp['sold_qty'] ?> đã bán · <?= fmtVND((float)$tp['sold_revenue']) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
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