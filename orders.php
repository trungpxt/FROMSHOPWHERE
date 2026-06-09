<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='update'){
    db()->prepare("UPDATE orders SET trang_thai=:s WHERE id=:id")->execute([':s'=>$_POST['trang_thai'],':id'=>(int)$_POST['id']]);
    $msg='✓ Đã cập nhật trạng thái.';
}
$orders=db()->query("SELECT o.*,u.ho_ten,u.email,(SELECT COUNT(*) FROM order_items WHERE don_hang_id=o.id) AS sp FROM orders o JOIN users u ON u.id=o.nguoi_dung_id ORDER BY o.ngay_dat DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đơn hàng — Admin</title><link rel="stylesheet" href="<?= SITE_URL ?>/style.css">
<style>
body{margin:0}.adm{display:flex;min-height:100vh}.adm-side{width:230px;flex-shrink:0;background:#010D05;border-right:1px solid #1a2e1c;position:sticky;top:0;height:100vh;overflow-y:auto}.adm-logo{padding:20px;border-bottom:1px solid #1a2e1c}.adm-logo img{height:36px}.adm-nav{padding:12px 8px}.adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:black;font-size:13px;font-weight:500;margin-bottom:2px;transition:all .15s}.adm-nav a:hover,.adm-nav a.on{background:#0A1E0C;color:#C8FF00;font-weight:600}.adm-main{flex:1;background:#F5F7F5;padding:28px}.body.dark .adm-main{background:#080F09}.box{background:#fff;border:1px solid #DDE3DD;border-radius:12px;overflow:hidden}.body.dark .box{background:#111D12;border-color:#1E2E1F}table{width:100%;border-collapse:collapse;font-size:13px}th{padding:10px 14px;background:#F5F7F5;color:#7A8F7C;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700}td{padding:11px 14px;border-bottom:1px solid #EEF1EE;vertical-align:middle}.msg-ok{background:#D1FAE5;color:#065F46;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700}.b-cho{background:#FEF3C7;color:#92400E}.b-tt{background:#D1FAE5;color:#065F46}.b-hoan{background:#DBEAFE;color:#1E40AF}.b-huy{background:#FEE2E2;color:#991B1B}select.ss{padding:4px 8px;border-radius:6px;border:1px solid #DDE3DD;font-size:12px;background:#fff;cursor:pointer}
</style>
</head><body>
<div class="adm">
  <aside class="adm-side">
    <div class="adm-logo"><img src="<?= SITE_URL ?>/images/logo.png" alt="FSW"></div>
    <nav class="adm-nav">
      <a href="<?= SITE_URL ?>/admin/">📊 Dashboard</a>
      <a href="<?= SITE_URL ?>/admin/products.php">📦 Sản phẩm</a>
      <a href="<?= SITE_URL ?>/admin/orders.php" class="on">🛒 Đơn hàng</a>
      <a href="<?= SITE_URL ?>/admin/users.php">👥 Người dùng</a>
      <a href="<?= SITE_URL ?>/admin/categories.php">🗂️ Danh mục</a>
      <div style="border-top:1px solid #1a2e1c;margin:12px 4px"></div>
      <a href="<?= SITE_URL ?>/index.php" target="_blank">🌐 Xem website</a>
      <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
    </nav>
  </aside>
  <main class="adm-main">
    <h1 style="font-size:22px;font-weight:800;margin:0 0 20px;font-family:'Space Grotesk',sans-serif">🛒 Đơn hàng</h1>
    <?php if($msg):?><div class="msg-ok"><?= $msg ?></div><?php endif;?>
    <div class="box">
      <table>
        <thead><tr><th>#</th><th>Khách hàng</th><th>SP</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th></tr></thead>
        <tbody>
        <?php if(empty($orders)):?><tr><td colspan="7" style="text-align:center;padding:32px;color:#7A8F7C">Chưa có đơn hàng.</td></tr><?php endif;?>
        <?php foreach($orders as $o):[$cls,$lbl]=match($o['trang_thai']){'da_thanh_toan'=>['b-tt','Đã TT'],'hoan_thanh'=>['b-hoan','Hoàn thành'],'huy'=>['b-huy','Huỷ'],default=>['b-cho','Chờ xử lý']};?>
        <tr>
          <td style="font-weight:700;color:#0A8A4C">#<?= $o['id']?></td>
          <td><div style="font-weight:600"><?= e($o['ho_ten'])?></div><div style="font-size:11px;color:#7A8F7C"><?= e($o['email'])?></div></td>
          <td><?= $o['sp']?> SP</td>
          <td style="font-weight:700;color:#0A8A4C"><?= fmtVND($o['tong_tien'])?></td>
          <td style="color:#7A8F7C"><?= e($o['phuong_thuc_tt'])?></td>
          <td>
            <form method="POST" onchange="this.submit()" style="display:inline">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= $o['id']?>">
              <select name="trang_thai" class="ss">
                <?php foreach(['cho_xu_ly'=>'Chờ xử lý','da_thanh_toan'=>'Đã TT','hoan_thanh'=>'Hoàn thành','huy'=>'Huỷ'] as $v=>$l):?>
                <option value="<?= $v?>" <?= $o['trang_thai']===$v?'selected':''?>><?= $l?></option>
                <?php endforeach;?>
              </select>
            </form>
          </td>
          <td style="color:#7A8F7C"><?= date('d/m/Y H:i',strtotime($o['ngay_dat']))?></td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="<?= SITE_URL ?>/shared.js"></script><script>restoreTheme();</script>
</body></html>
