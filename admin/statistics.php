<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

/* ── Bộ lọc: xem theo ngày hoặc theo tháng ── */
$view  = (($_GET['view'] ?? 'day') === 'month') ? 'month' : 'day';
$allowedDayRanges   = [7, 30, 90];
$allowedMonthRanges = [6, 12, 24];
$range = (int)($_GET['range'] ?? ($view === 'day' ? 30 : 12));
if ($view === 'day'   && !in_array($range, $allowedDayRanges, true))   $range = 30;
if ($view === 'month' && !in_array($range, $allowedMonthRanges, true)) $range = 12;

try {
    if ($view === 'day') {
        $from = date('Y-m-d', strtotime('-' . ($range - 1) . ' days'));
        $stmt = db()->prepare("
            SELECT DATE(ngay_dat) AS bucket, SUM(tong_tien) AS revenue, COUNT(*) AS so_don
            FROM orders
            WHERE trang_thai IN ('da_thanh_toan','hoan_thanh') AND DATE(ngay_dat) >= :from
            GROUP BY DATE(ngay_dat)
        ");
        $stmt->execute([':from' => $from]);
        $byBucket = [];
        foreach ($stmt->fetchAll() as $r) $byBucket[$r['bucket']] = $r;

        $labels = []; $revenueData = []; $ordersData = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $labels[]      = date('d/m', strtotime($d));
            $revenueData[] = (float)($byBucket[$d]['revenue'] ?? 0);
            $ordersData[]  = (int)($byBucket[$d]['so_don'] ?? 0);
        }
        $periodFrom = $from . ' 00:00:00';
    } else {
        $from = date('Y-m-01', strtotime('-' . ($range - 1) . ' months'));
        $stmt = db()->prepare("
            SELECT DATE_FORMAT(ngay_dat, '%Y-%m') AS bucket, SUM(tong_tien) AS revenue, COUNT(*) AS so_don
            FROM orders
            WHERE trang_thai IN ('da_thanh_toan','hoan_thanh') AND ngay_dat >= :from
            GROUP BY DATE_FORMAT(ngay_dat, '%Y-%m')
        ");
        $stmt->execute([':from' => $from]);
        $byBucket = [];
        foreach ($stmt->fetchAll() as $r) $byBucket[$r['bucket']] = $r;

        $labels = []; $revenueData = []; $ordersData = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $labels[]      = 'Th' . date('n/Y', strtotime($ym . '-01'));
            $revenueData[] = (float)($byBucket[$ym]['revenue'] ?? 0);
            $ordersData[]  = (int)($byBucket[$ym]['so_don'] ?? 0);
        }
        $periodFrom = $from . ' 00:00:00';
    }

    $periodRevenue = array_sum($revenueData);
    $periodOrders  = array_sum($ordersData);
    $avgOrder      = $periodOrders > 0 ? $periodRevenue / $periodOrders : 0;

    /* ── Top sản phẩm bán chạy trong cùng khoảng thời gian ── */
    $stmt = db()->prepare("
        SELECT p.id, p.ten_san_pham, p.hinh_anh,
               SUM(oi.so_luong) AS qty_sold,
               SUM(oi.so_luong * oi.don_gia) AS revenue
        FROM order_items oi
        JOIN orders o   ON o.id = oi.don_hang_id
        JOIN products p ON p.id = oi.san_pham_id
        WHERE o.trang_thai IN ('da_thanh_toan','hoan_thanh') AND o.ngay_dat >= :from
        GROUP BY p.id, p.ten_san_pham, p.hinh_anh
        ORDER BY qty_sold DESC
        LIMIT 10
    ");
    $stmt->execute([':from' => $periodFrom]);
    $topProducts = $stmt->fetchAll();
} catch (Exception $ex) {
    $labels = $revenueData = $ordersData = [];
    $periodRevenue = $periodOrders = $avgOrder = 0;
    $topProducts = [];
}

$topProductLabel = !empty($topProducts) ? $topProducts[0]['ten_san_pham'] : '—';
$topProductQty   = !empty($topProducts) ? (int)$topProducts[0]['qty_sold'] : 0;

$admPageTitle  = 'Thống kê doanh thu — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel  = 'Thống kê doanh thu';
include __DIR__ . '/../includes/admin-head.php';

/* ── Chuẩn bị dữ liệu cho biểu đồ vẽ bằng HTML/CSS thuần (không phụ thuộc thư viện ngoài/internet) ── */
$maxRevenue = max($revenueData ?: [0]) ?: 1;
$labelStep  = max(1, (int)ceil(count($labels) / 14)); // tránh chồng chữ khi có nhiều cột
$maxQty     = 0;
foreach ($topProducts as $p) $maxQty = max($maxQty, (int)$p['qty_sold']);
if ($maxQty === 0) $maxQty = 1;
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <?php include __DIR__ . '/../includes/admin-topbar.php'; ?>

    <div class="adm-content">

      <div class="adm-welcome" style="margin-bottom:22px;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
          <h1>📊 Thống kê doanh thu</h1>
          <p>Theo dõi doanh thu theo thời gian và các sản phẩm bán chạy nhất.</p>
        </div>

        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
          <select name="view" onchange="this.form.submit()" class="btn btn-secondary" style="cursor:pointer">
            <option value="day"   <?= $view === 'day'   ? 'selected' : '' ?>>Theo ngày</option>
            <option value="month" <?= $view === 'month' ? 'selected' : '' ?>>Theo tháng</option>
          </select>
          <select name="range" onchange="this.form.submit()" class="btn btn-secondary" style="cursor:pointer">
            <?php if ($view === 'day'): ?>
              <option value="7"  <?= $range == 7  ? 'selected' : '' ?>>7 ngày qua</option>
              <option value="30" <?= $range == 30 ? 'selected' : '' ?>>30 ngày qua</option>
              <option value="90" <?= $range == 90 ? 'selected' : '' ?>>90 ngày qua</option>
            <?php else: ?>
              <option value="6"  <?= $range == 6  ? 'selected' : '' ?>>6 tháng qua</option>
              <option value="12" <?= $range == 12 ? 'selected' : '' ?>>12 tháng qua</option>
              <option value="24" <?= $range == 24 ? 'selected' : '' ?>>24 tháng qua</option>
            <?php endif; ?>
          </select>
        </form>
      </div>

      <!-- STATS -->
      <div class="stats-grid stats-grid-4" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-icon si-green">💰</div>
          <div>
            <div class="stat-num"><?= fmtVND($periodRevenue) ?></div>
            <div class="stat-lbl">Doanh thu kỳ này</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-blue">🛒</div>
          <div>
            <div class="stat-num"><?= $periodOrders ?></div>
            <div class="stat-lbl">Đơn hàng đã thanh toán</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-purple">🧾</div>
          <div>
            <div class="stat-num"><?= fmtVND($avgOrder) ?></div>
            <div class="stat-lbl">Giá trị đơn TB</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-yellow">🏆</div>
          <div>
            <div class="stat-num" style="font-size:16px" title="<?= e($topProductLabel) ?>"><?= e(mb_strimwidth($topProductLabel, 0, 22, '…')) ?></div>
            <div class="stat-lbl">Bán chạy nhất (<?= $topProductQty ?> đã bán)</div>
          </div>
        </div>
      </div>

      <!-- REVENUE CHART (HTML/CSS thuần — không cần internet/thư viện ngoài) -->
      <div class="table-card" style="padding:20px;margin-bottom:20px">
        <div style="font-size:14px;font-weight:800;margin-bottom:18px">
          📈 Doanh thu <?= $view === 'day' ? 'theo ngày' : 'theo tháng' ?>
        </div>
        <?php if (empty(array_filter($revenueData))): ?>
          <div class="table-empty"><span class="te-icon">📈</span>Chưa có doanh thu trong khoảng thời gian này.</div>
        <?php else: ?>
        <div style="display:flex;align-items:flex-end;gap:<?= count($labels) > 40 ? '2px' : '6px' ?>;height:140px;padding-top:10px;border-bottom:1px solid var(--adm-border)">
          <?php foreach ($revenueData as $i => $val):
            $pct    = $val > 0 ? max(3, round($val / $maxRevenue * 100)) : 1;
            $tip    = ($view === 'day' ? 'Ngày ' . $labels[$i] : $labels[$i]) . ': ' . fmtVND($val) . ' — ' . $ordersData[$i] . ' đơn';
          ?>
          <div style="flex:1 1 0;min-width:2px;height:100%;display:flex;align-items:flex-end" title="<?= e($tip) ?>">
            <div style="width:100%;height:<?= $pct ?>%;background:linear-gradient(180deg,#3B9E86,#3B2FA0);border-radius:4px 4px 0 0;transition:opacity .15s" onmouseover="this.style.opacity=.75" onmouseout="this.style.opacity=1"></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:<?= count($labels) > 40 ? '2px' : '6px' ?>;margin-top:6px">
          <?php foreach ($labels as $i => $lbl): ?>
          <div style="flex:1 1 0;min-width:2px;text-align:center;font-size:10px;color:var(--adm-ink-3);white-space:nowrap;overflow:hidden">
            <?= $i % $labelStep === 0 ? e($lbl) : '' ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- TOP SẢN PHẨM: chart + bảng chi tiết -->
      <div class="adm-dash-grid">
        <div class="table-card" style="padding:20px">
          <div style="font-size:14px;font-weight:800;margin-bottom:16px">🔥 Top sản phẩm bán chạy (theo số lượng)</div>
          <?php if (empty($topProducts)): ?>
            <div class="table-empty"><span class="te-icon">📦</span>Chưa có dữ liệu bán hàng trong kỳ này.</div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:12px">
              <?php foreach ($topProducts as $p):
                $pct = max(4, round((int)$p['qty_sold'] / $maxQty * 100));
              ?>
              <div>
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                  <span style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:70%"><?= e($p['ten_san_pham']) ?></span>
                  <span class="mono text-muted2"><?= (int)$p['qty_sold'] ?></span>
                </div>
                <div style="background:var(--bg-alt, rgba(127,127,127,.15));border-radius:5px;height:10px;overflow:hidden">
                  <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,#3B2FA0,#3B9E86);border-radius:5px"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <div style="font-size:14px;font-weight:800;margin-bottom:12px">📋 Chi tiết top sản phẩm</div>
          <div class="table-card" style="overflow-x:auto">
            <table style="font-size:12px">
              <thead>
                <tr><th style="padding:10px 8px">Sản phẩm</th><th style="padding:10px 8px;text-align:right">Đã bán</th><th style="padding:10px 8px;text-align:right">Doanh thu</th></tr>
              </thead>
              <tbody>
              <?php if (empty($topProducts)): ?>
                <tr><td colspan="3" class="table-empty"><span class="te-icon">📦</span>Chưa có dữ liệu.</td></tr>
              <?php endif; ?>
              <?php foreach ($topProducts as $i => $p):
                $rev = (float)$p['revenue'];
                $revShort = $rev >= 1000000 ? number_format($rev / 1000000, 1, ',', '.') . 'tr' : number_format($rev, 0, ',', '.') . 'đ';
              ?>
                <tr>
                  <td style="padding:10px 8px" title="<?= e($p['ten_san_pham']) ?>">
                    <span class="text-muted2 mono" style="font-size:11px">#<?= $i + 1 ?></span>
                    <?= e(mb_strimwidth($p['ten_san_pham'], 0, 20, '…')) ?>
                  </td>
                  <td style="padding:10px 8px;text-align:right" class="mono"><?= (int)$p['qty_sold'] ?></td>
                  <td style="padding:10px 8px;text-align:right;white-space:nowrap" class="price-main" title="<?= e(fmtVND($rev)) ?>"><?= $revShort ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

</body>
</html>
