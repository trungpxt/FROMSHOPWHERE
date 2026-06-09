<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$users=db()->query("SELECT u.*,COUNT(o.id) so_don FROM users u LEFT JOIN orders o ON o.nguoi_dung_id=u.id GROUP BY u.id ORDER BY u.ngay_tao DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Người dùng — Admin</title><link rel="stylesheet" href="<?= SITE_URL ?>/style.css">
<style>body{margin:0}.adm{display:flex;min-height:100vh}.adm-side{width:230px;flex-shrink:0;background:#010D05;border-right:1px solid #1a2e1c;position:sticky;top:0;height:100vh;overflow-y:auto}.adm-logo{padding:20px;border-bottom:1px solid #1a2e1c}.adm-logo img{height:36px}.adm-nav{padding:12px 8px}.adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:black;font-size:13px;font-weight:500;margin-bottom:2px;transition:all .15s}.adm-nav a:hover,.adm-nav a.on{background:#0A1E0C;color:#C8FF00;font-weight:600}.adm-main{flex:1;background:#F5F7F5;padding:28px}.box{background:#fff;border:1px solid #DDE3DD;border-radius:12px;overflow:hidden}table{width:100%;border-collapse:collapse;font-size:13px}th{padding:10px 14px;background:#F5F7F5;color:#7A8F7C;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700}td{padding:11px 14px;border-bottom:1px solid #EEF1EE}.av{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;color:#fff;font-family:'Space Grotesk',sans-serif}.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}.b-adm{background:#DBEAFE;color:#1E40AF}.b-kh{background:#D1FAE5;color:#065F46}</style>
</head><body>
<div class="adm">
  <aside class="adm-side">
    <div class="adm-logo"><img src="<?= SITE_URL ?>/images/logo.png" alt="FSW"></div>
    <nav class="adm-nav">
      <a href="<?= SITE_URL ?>/admin/">📊 Dashboard</a>
      <a href="<?= SITE_URL ?>/admin/products.php">📦 Sản phẩm</a>
      <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Đơn hàng</a>
      <a href="<?= SITE_URL ?>/admin/users.php" class="on">👥 Người dùng</a>
      <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Danh mục</a>
      <div style="border-top:1px solid #1a2e1c;margin:12px 4px"></div>
      <a href="<?= SITE_URL ?>/index.php" target="_blank">🌐 Xem website</a>
      <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
    </nav>
  </aside>
  <main class="adm-main">
    <h1 style="font-size:22px;font-weight:800;margin:0 0 20px;font-family:'Space Grotesk',sans-serif">👥 Người dùng (<?= count($users)?>)</h1>
    <div class="box">
      <table>
        <thead><tr><th>Người dùng</th><th>Email</th><th>SĐT</th><th>Vai trò</th><th>Đơn hàng</th><th>Ngày tạo</th></tr></thead>
        <tbody>
        <?php $colors=['#065E34','#185FA5','#534AB7','#A32D2D','#BA7517'];
        foreach($users as $u):$c=$colors[$u['id']%count($colors)];?>
        <tr>
          <td><div style="display:flex;align-items:center;gap:10px"><div class="av" style="background:<?= $c?>"><?= strtoupper(mb_substr($u['ho_ten'],0,1))?></div><b><?= e($u['ho_ten'])?></b></div></td>
          <td style="color:#7A8F7C"><?= e($u['email'])?></td>
          <td style="color:#7A8F7C"><?= e($u['so_dien_thoai']??'—')?></td>
          <td><span class="badge <?= $u['vai_tro']==='admin'?'b-adm':'b-kh'?>"><?= $u['vai_tro']==='admin'?'⚙️ Admin':'👤 Khách'?></span></td>
          <td style="font-weight:700"><?= $u['so_don']?></td>
          <td style="color:#7A8F7C"><?= date('d/m/Y',strtotime($u['ngay_tao']))?></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="<?= SITE_URL ?>/shared.js"></script><script>restoreTheme();</script>
</body></html>
