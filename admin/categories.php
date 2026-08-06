<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
  $a = $_POST['action'] ?? '';
  if ($a === 'save') {
    $id   = (int)($_POST['id'] ?? 0);
    $ten  = trim($_POST['ten_danh_muc'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i','-',$ten));
    $mota = trim($_POST['mo_ta'] ?? '');
    $tt   = (int)($_POST['thu_tu'] ?? 0);
    if ($id) {
      db()->prepare("UPDATE categories SET ten_danh_muc=:t,slug=:s,mo_ta=:m,thu_tu=:o WHERE id=:id")
        ->execute([':t'=>$ten,':s'=>$slug,':m'=>$mota,':o'=>$tt,':id'=>$id]);
      $msg = 'Đã cập nhật danh mục.';
    } else {
      db()->prepare("INSERT INTO categories(ten_danh_muc,slug,mo_ta,thu_tu) VALUES(:t,:s,:m,:o)")
        ->execute([':t'=>$ten,':s'=>$slug,':m'=>$mota,':o'=>$tt]);
      $msg = 'Đã thêm danh mục mới.';
    }
  }
  if ($a === 'delete') {
    db()->prepare("DELETE FROM categories WHERE id=:id")->execute([':id'=>(int)$_POST['id']]);
    $msg = 'Đã xoá danh mục.';
  }
}
$cats = db()->query("SELECT c.*,COUNT(p.id) sp FROM categories c LEFT JOIN products p ON p.danh_muc_id=c.id GROUP BY c.id ORDER BY c.thu_tu")->fetchAll();
$admPageTitle = 'Danh mục — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel = 'Danh mục';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <button onclick="document.querySelector('.adm-side').classList.toggle('open');document.querySelector('.adm-side-backdrop').classList.toggle('open')" class="adm-hamburger" aria-label="Mở menu" title="Menu">☰</button>
      <div class="adm-topbar">
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <strong>Danh mục</strong></div>
      <div class="adm-topbar-right">
        <button onclick="toggleTheme()" class="adm-theme-btn" title="Đổi giao diện sáng/tối" id="admThemeBtn">☀️</button>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-secondary">🌐 Xem website</a>
        <button class="btn btn-primary" onclick="openForm()">
          <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="1" x2="8" y2="15"/><line x1="1" y1="8" x2="15" y2="8"/></svg>
          Thêm danh mục
        </button>
      </div>
    </div>
    <div class="adm-side-backdrop" onclick="document.querySelector('.adm-side').classList.remove('open');this.classList.remove('open')"></div>
    <div class="adm-content">
      <?php if($msg): ?><div class="adm-alert adm-alert-ok">✓ <?= e($msg) ?></div><?php endif; ?>

      <div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">

        <!-- FORM -->
        <div class="form-card" id="catFormWrap">
          <div class="form-card-header">
            <span class="form-card-title" id="fTitle">➕ Thêm danh mục mới</span>
            <button type="button" onclick="resetF()" style="font-size:20px;background:none;border:none;cursor:pointer;color:var(--ink-4);line-height:1">×</button>
          </div>
          <form method="POST" id="catForm">
          <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="fId" value="0">
            <div class="fg">
              <label>Tên danh mục *</label>
              <input type="text" name="ten_danh_muc" id="fTen" required placeholder="VD: Thiết kế đồ hoạ"
                     oninput="autoSlug(this.value)">
            </div>
            <div class="fg">
              <label>Slug (URL)</label>
              <input type="text" name="slug" id="fSlug" placeholder="thiet-ke-do-hoa" class="mono" style="font-size:12px">
            </div>
            <div class="fg">
              <label>Mô tả</label>
              <input type="text" name="mo_ta" id="fMota" placeholder="Phần mềm đồ họa chuyên nghiệp...">
            </div>
            <div class="fg">
              <label>Thứ tự sắp xếp</label>
              <input type="number" name="thu_tu" id="fOrder" value="0" min="0">
            </div>
            <div style="display:flex;gap:8px;margin-top:4px">
              <button type="submit" class="btn btn-primary" style="flex:1">💾 Lưu danh mục</button>
              <button type="button" onclick="resetF()" class="btn btn-secondary">Huỷ</button>
            </div>
          </form>
        </div>

        <!-- TABLE -->
        <div>
          <div style="font-size:14px;font-weight:800;margin-bottom:12px">🗂️ Danh sách (<?= count($cats) ?>)</div>
          <div class="table-card">
            <table>
              <thead><tr><th>#</th><th>Tên danh mục</th><th>Slug</th><th>Số SP</th><th>Thứ tự</th><th>Thao tác</th></tr></thead>
              <tbody>
              <?php if(empty($cats)): ?>
                <tr><td colspan="6" class="table-empty"><span class="te-icon">🗂️</span>Chưa có danh mục nào.</td></tr>
              <?php endif; ?>
              <?php foreach($cats as $c): ?>
              <tr>
                <td class="mono text-muted2"><?= $c['id'] ?></td>
                <td style="font-weight:700"><?= e($c['ten_danh_muc']) ?></td>
                <td class="mono text-muted2" style="font-size:12px"><?= e($c['slug']) ?></td>
                <td>
                  <span class="badge b-green"><span class="badge-dot"></span><?= $c['sp'] ?> SP</span>
                </td>
                <td class="text-muted2"><?= $c['thu_tu'] ?></td>
                <td>
                  <div class="act-row">
                    <button class="act-btn ab-edit" title="Sửa"
                      onclick='editC(<?= json_encode($c, JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                    <form method="POST" style="display:contents" onsubmit="return confirm('Xoá danh mục «<?= e($c['ten_danh_muc']) ?>»?')">
          <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button class="act-btn ab-del" type="submit" title="Xoá">🗑</button>
                    </form>
                  </div>
                </td>
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
<script src="<?= SITE_URL ?>/assets/js/admin-categories.js"></script>
</body>
</html>