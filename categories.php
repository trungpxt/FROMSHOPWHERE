<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $a=$_POST['action']??'';
    if($a==='save'){
        $id=(int)($_POST['id']??0);
        $ten=trim($_POST['ten_danh_muc']);
        $slug=trim($_POST['slug'])?:strtolower(preg_replace('/[^a-z0-9]+/i','-',$ten));
        $mota=trim($_POST['mo_ta']??'');
        $tt=(int)($_POST['thu_tu']??0);
        if($id) db()->prepare("UPDATE categories SET ten_danh_muc=:t,slug=:s,mo_ta=:m,thu_tu=:o WHERE id=:id")->execute([':t'=>$ten,':s'=>$slug,':m'=>$mota,':o'=>$tt,':id'=>$id]);
        else db()->prepare("INSERT INTO categories(ten_danh_muc,slug,mo_ta,thu_tu) VALUES(:t,:s,:m,:o)")->execute([':t'=>$ten,':s'=>$slug,':m'=>$mota,':o'=>$tt]);
        $msg='✓ Đã lưu danh mục.';
    }
    if($a==='delete'){db()->prepare("DELETE FROM categories WHERE id=:id")->execute([':id'=>(int)$_POST['id']]);$msg='✓ Đã xoá.';}
}
$cats=db()->query("SELECT c.*,COUNT(p.id) sp FROM categories c LEFT JOIN products p ON p.danh_muc_id=c.id GROUP BY c.id ORDER BY c.thu_tu")->fetchAll();
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Danh mục — Admin</title><link rel="stylesheet" href="<?= SITE_URL ?>/style.css">
<style>body{margin:0}.adm{display:flex;min-height:100vh}.adm-side{width:230px;flex-shrink:0;background:#010D05;border-right:1px solid #1a2e1c;position:sticky;top:0;height:100vh;overflow-y:auto}.adm-logo{padding:20px;border-bottom:1px solid #1a2e1c}.adm-logo img{height:36px}.adm-nav{padding:12px 8px}.adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:#000000;font-size:13px;font-weight:500;margin-bottom:2px;transition:all .15s}.adm-nav a:hover,.adm-nav a.on{background:#0A1E0C;color:#C8FF00;font-weight:600}.adm-main{flex:1;background:#F5F7F5;padding:28px}.form-box{background:#fff;border:1px solid #DDE3DD;border-radius:12px;padding:22px;margin-bottom:20px}.form-box h3{font-size:16px;font-weight:700;margin:0 0 16px}.msg-ok{background:#D1FAE5;color:#065F46;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:13px}.box{background:#fff;border:1px solid #DDE3DD;border-radius:12px;overflow:hidden}table{width:100%;border-collapse:collapse;font-size:13px}th{padding:10px 14px;background:#F5F7F5;color:#7A8F7C;text-align:left;font-size:11px;text-transform:uppercase;font-weight:700}td{padding:11px 14px;border-bottom:1px solid #EEF1EE}.fi{width:100%;padding:9px 12px;border:1.5px solid #DDE3DD;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:#0D1A0F;background:#fff;box-sizing:border-box}.fi:focus{border-color:#0A8A4C;outline:none}.fl{font-size:11px;font-weight:700;color:#3D5040;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:5px}.mg{margin-bottom:12px}.btn-sm{padding:5px 11px;font-size:12px;border-radius:6px;cursor:pointer;border:none;font-weight:600}.btn-e{background:#E8FFF3;color:#065F46}.btn-d{background:#FEE2E2;color:#991B1B}</style>
</head><body>
<div class="adm">
  <aside class="adm-side">
    <div class="adm-logo"><img src="<?= SITE_URL ?>/images/logo.png" alt="FSW"></div>
    <nav class="adm-nav">
      <a href="<?= SITE_URL ?>/admin/">📊 Dashboard</a>
      <a href="<?= SITE_URL ?>/admin/products.php">📦 Sản phẩm</a>
      <a href="<?= SITE_URL ?>/admin/orders.php">🛒 Đơn hàng</a>
      <a href="<?= SITE_URL ?>/admin/users.php">👥 Người dùng</a>
      <a href="<?= SITE_URL ?>/admin/categories.php" class="on">🗂️ Danh mục</a>
      <div style="border-top:1px solid #1a2e1c;margin:12px 4px"></div>
      <a href="<?= SITE_URL ?>/index.php" target="_blank">🌐 Xem website</a>
      <a href="<?= SITE_URL ?>/logout.php">🚪 Đăng xuất</a>
    </nav>
  </aside>
  <main class="adm-main">
    <h1 style="font-size:22px;font-weight:800;margin:0 0 20px;font-family:'Space Grotesk',sans-serif">🗂️ Danh mục</h1>
    <?php if($msg):?><div class="msg-ok"><?= $msg ?></div><?php endif;?>
    <div class="form-box">
      <h3 id="fTitle">Thêm danh mục mới</h3>
      <form method="POST" id="catForm">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="fId" value="0">
        <div style="display:grid;grid-template-columns:2fr 2fr 1fr;gap:12px">
          <div class="mg"><label class="fl">Tên danh mục *</label><input class="fi" type="text" name="ten_danh_muc" id="fTen" required placeholder="Thiết kế"></div>
          <div class="mg"><label class="fl">Slug</label><input class="fi" type="text" name="slug" id="fSlug" placeholder="thiet-ke"></div>
          <div class="mg"><label class="fl">Thứ tự</label><input class="fi" type="number" name="thu_tu" id="fOrder" value="0" min="0"></div>
        </div>
        <div class="mg"><label class="fl">Mô tả</label><input class="fi" type="text" name="mo_ta" id="fMota" placeholder="Phần mềm đồ họa..."></div>
        <div style="display:flex;gap:8px">
          <button type="submit" class="btn-submit" style="padding:10px 22px;font-size:13px">💾 Lưu</button>
          <button type="button" onclick="resetF()" style="padding:10px 18px;border:1.5px solid #DDE3DD;border-radius:8px;background:none;cursor:pointer;font-size:13px">Huỷ</button>
        </div>
      </form>
    </div>
    <div class="box">
      <table>
        <thead><tr><th>#</th><th>Tên</th><th>Slug</th><th>Số SP</th><th>Thứ tự</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php foreach($cats as $c):?>
        <tr>
          <td>#<?= $c['id']?></td>
          <td><b><?= e($c['ten_danh_muc'])?></b></td>
          <td style="color:#7A8F7C;font-family:monospace"><?= e($c['slug'])?></td>
          <td><?= $c['sp']?></td>
          <td><?= $c['thu_tu']?></td>
          <td>
            <button class="btn-sm btn-e" onclick='editC(<?= json_encode($c,JSON_UNESCAPED_UNICODE)?>')>✏️ Sửa</button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Xoá danh mục này?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $c['id']?>">
              <button class="btn-sm btn-d" type="submit">🗑 Xoá</button>
            </form>
          </td>
        </tr>
        <?php endforeach;?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<script src="<?= SITE_URL ?>/shared.js"></script>
<script>
restoreTheme();
function editC(c){
  document.getElementById('fTitle').textContent='Sửa danh mục';
  document.getElementById('fId').value=c.id;
  document.getElementById('fTen').value=c.ten_danh_muc;
  document.getElementById('fSlug').value=c.slug;
  document.getElementById('fMota').value=c.mo_ta||'';
  document.getElementById('fOrder').value=c.thu_tu;
  document.getElementById('catForm').scrollIntoView({behavior:'smooth'});
}
function resetF(){
  document.getElementById('fTitle').textContent='Thêm danh mục mới';
  document.getElementById('catForm').reset();
  document.getElementById('fId').value='0';
}
</script>
</body></html>
