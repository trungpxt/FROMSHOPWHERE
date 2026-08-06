<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$s = trim($_GET['s'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$where = [];
$params = [];
if ($s) { $where[] = "(ho_ten LIKE :s OR email LIKE :s2)"; $params[':s']="%$s%"; $params[':s2']="%$s%"; }
if ($filter !== 'all') { $where[] = "vai_tro=:r"; $params[':r']=$filter; }
$wh = $where ? "WHERE ".implode(" AND ",$where) : "";
$stmt = db()->prepare("SELECT u.*,COUNT(o.id) so_don FROM users u LEFT JOIN orders o ON o.nguoi_dung_id=u.id $wh GROUP BY u.id ORDER BY u.ngay_tao DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$counts = db()->query("SELECT vai_tro,COUNT(*) c FROM users GROUP BY vai_tro")->fetchAll(PDO::FETCH_KEY_PAIR);
$total  = array_sum($counts);
$admins = $counts['admin'] ?? 0;
$guests = $counts['khach_hang'] ?? 0;
$ucolors = ['#065E34','#185FA5','#534AB7','#A32D2D','#BA7517'];
$admPageTitle = 'Người dùng — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel = 'Người dùng';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <?php include __DIR__ . '/../includes/admin-topbar.php'; ?>
    <div class="adm-content">

      <!-- STATS -->
      <div class="stats-grid stats-grid-3" style="margin-bottom:20px">
        <div class="stat-card">
          <div class="stat-icon si-purple">👥</div>
          <div><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Tổng người dùng</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-green">👤</div>
          <div><div class="stat-num"><?= $guests ?></div><div class="stat-lbl">Khách hàng</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-blue">⚙️</div>
          <div><div class="stat-num"><?= $admins ?></div><div class="stat-lbl">Quản trị viên</div></div>
        </div>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <form method="GET" style="display:contents">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <div class="search-wrap">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6.5" cy="6.5" r="5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
              <input class="search-input" type="text" name="s" value="<?= e($s) ?>" placeholder="Tìm tên, email...">
            </div>
            <button class="btn-search" type="submit">Tìm</button>
            <?php if($s): ?><a href="?filter=<?= $filter ?>" style="font-size:12px;color:var(--ink-3);padding:8px 4px">✕</a><?php endif; ?>
          </form>
        </div>
        <div class="filter-tabs">
          <a href="?s=<?= urlencode($s) ?>&filter=all" class="filter-tab <?= $filter==='all'?'active':'' ?>">Tất cả (<?= $total ?>)</a>
          <a href="?s=<?= urlencode($s) ?>&filter=khach_hang" class="filter-tab <?= $filter==='khach_hang'?'active':'' ?>">Khách hàng</a>
          <a href="?s=<?= urlencode($s) ?>&filter=admin" class="filter-tab <?= $filter==='admin'?'active':'' ?>">Admin</a>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead><tr><th>Người dùng</th><th>Email</th><th>SĐT</th><th>Vai trò</th><th>Đơn hàng</th><th>Ngày tạo</th></tr></thead>
          <tbody>
          <?php if(empty($users)): ?>
            <tr><td colspan="6" class="table-empty"><span class="te-icon">👥</span>Không tìm thấy người dùng.</td></tr>
          <?php endif; ?>
          <?php foreach($users as $u):
            $uc = $ucolors[$u['id'] % count($ucolors)];
            $isAdmin = $u['vai_tro'] === 'admin';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="user-av" style="background:<?= $uc ?>"><?= strtoupper(mb_substr($u['ho_ten'],0,1)) ?></div>
                <div>
                  <div style="font-weight:700;font-size:13.5px"><?= e($u['ho_ten']) ?></div>
                  <?php if($isAdmin): ?><div style="font-size:10px;color:var(--blue);font-weight:700;margin-top:1px">Quản trị viên</div><?php endif; ?>
                </div>
              </div>
            </td>
            <td class="mono text-muted2"><?= e($u['email']) ?></td>
            <td style="font-size:12px;color:var(--ink-3)"><?= e($u['so_dien_thoai']??'—') ?></td>
            <td>
              <span class="badge <?= $isAdmin?'b-blue':'b-green' ?>">
                <span class="badge-dot"></span>
                <?= $isAdmin ? '⚙️ Admin' : '👤 Khách' ?>
              </span>
            </td>
            <td style="font-weight:700;color:var(--ink)"><?= $u['so_don'] ?></td>
            <td class="mono text-muted2"><?= date('d/m/Y',strtotime($u['ngay_tao'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>
</div>
</body>
</html>