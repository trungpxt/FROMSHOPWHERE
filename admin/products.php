<?php
require_once __DIR__ . '/../config.php';
requireAdmin();


$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = (int)($_POST['id']??0);
        $dm   = (int)$_POST['danh_muc_id'];
        $ten  = trim($_POST['ten_san_pham']);
        $mota = trim($_POST['mo_ta']??'');
        $gg   = (float)($_POST['gia_goc']??0);
        $gb   = (float)$_POST['gia_ban'];
        $pv   = trim($_POST['phien_ban']??'');
        $lm   = isset($_POST['la_moi'])?1:0;
        $tt   = $_POST['trang_thai'];
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$ten),'-'));

        if (!$ten||!$gb||!$dm) { $error='Vui lòng điền tên, danh mục và giá bán.'; }
        else {
            $hinh = $_POST['hinh_anh_cu']??'';
            if (!empty($_FILES['hinh_anh']['name'])) {
                $ext = strtolower(pathinfo($_FILES['hinh_anh']['name'],PATHINFO_EXTENSION));
                if (!in_array($ext,['jpg','jpeg','png','webp'])) { $error='Định dạng ảnh không hợp lệ.'; }
                elseif ($_FILES['hinh_anh']['size']>5*1024*1024) { $error='Ảnh tối đa 5MB.'; }
                else {
                    $fn  = $slug.'-'.time().'.'.$ext;
                    $dst = __DIR__.'/../images/'.$fn;
                    if (move_uploaded_file($_FILES['hinh_anh']['tmp_name'],$dst)) $hinh=$fn;
                    else $error='Không upload được ảnh.';
                }
            }
            if (!$error) {
                $base=$slug; $i=1;
                while(true){
                    $c=db()->prepare("SELECT id FROM products WHERE slug=:s AND id!=:id");
                    $c->execute([':s'=>$slug,':id'=>$id]);
                    if(!$c->fetch()) break;
                    $slug=$base.'-'.$i++;
                }
                if ($id) {
                    db()->prepare("UPDATE products SET danh_muc_id=:dm,ten_san_pham=:ten,slug=:slug,mo_ta=:mota,gia_goc=:gg,gia_ban=:gb,phien_ban=:pv,hinh_anh=:ha,la_moi=:lm,trang_thai=:tt WHERE id=:id")
                       ->execute([':dm'=>$dm,':ten'=>$ten,':slug'=>$slug,':mota'=>$mota,':gg'=>$gg?:null,':gb'=>$gb,':pv'=>$pv,':ha'=>$hinh,':lm'=>$lm,':tt'=>$tt,':id'=>$id]);
                    $msg = 'Đã cập nhật sản phẩm thành công.';
                } else {
                    db()->prepare("INSERT INTO products(danh_muc_id,ten_san_pham,slug,mo_ta,gia_goc,gia_ban,phien_ban,hinh_anh,la_moi,trang_thai) VALUES(:dm,:ten,:slug,:mota,:gg,:gb,:pv,:ha,:lm,:tt)")
                       ->execute([':dm'=>$dm,':ten'=>$ten,':slug'=>$slug,':mota'=>$mota,':gg'=>$gg?:null,':gb'=>$gb,':pv'=>$pv,':ha'=>$hinh,':lm'=>$lm,':tt'=>$tt]);
                    $msg = 'Đã thêm sản phẩm mới thành công.';
                }
            }
        }
    }
    if ($action==='delete') {
        db()->prepare("DELETE FROM products WHERE id=:id")->execute([':id'=>(int)$_POST['id']]);
        $msg='Đã xoá sản phẩm.';
    }
    if ($action==='toggle') {
        db()->prepare("UPDATE products SET trang_thai=CASE WHEN trang_thai='hien' THEN 'an' ELSE 'hien' END WHERE id=:id")
           ->execute([':id'=>(int)$_POST['id']]);
        $msg='Đã đổi trạng thái sản phẩm.';
    }
}

$s   = trim($_GET['s']??'');
$cat = (int)($_GET['cat']??0);
$filter_tt = $_GET['filter'] ?? 'all';
$w   = ["1=1"]; $p=[];
if($s){$w[]="p.ten_san_pham LIKE :s";$p[':s']="%$s%";}
if($cat){$w[]="p.danh_muc_id=:cat";$p[':cat']=$cat;}
if($filter_tt !== 'all'){$w[]="p.trang_thai=:tt";$p[':tt']=$filter_tt;}
$stmt=db()->prepare("SELECT p.*,c.ten_danh_muc FROM products p JOIN categories c ON c.id=p.danh_muc_id WHERE ".implode(' AND ',$w)." ORDER BY p.id DESC");
$stmt->execute($p);
$products=$stmt->fetchAll();
$cats=db()->query("SELECT * FROM categories ORDER BY thu_tu")->fetchAll();

$counts_tt = db()->query("SELECT trang_thai,COUNT(*) c FROM products GROUP BY trang_thai")->fetchAll(PDO::FETCH_KEY_PAIR);
$total_p = array_sum($counts_tt);
$visible = $counts_tt['hien'] ?? 0;
$hidden  = $counts_tt['an'] ?? 0;

$editProduct = null;
if(isset($_GET['edit'])){
    $ep=db()->prepare("SELECT * FROM products WHERE id=:id");
    $ep->execute([':id'=>(int)$_GET['edit']]);
    $editProduct=$ep->fetch();
}
$admPageTitle = 'Sản phẩm — Admin FSW';
$admBreadcrumb = 'Admin';
$admPageLabel = 'Sản phẩm';
include __DIR__ . '/../includes/admin-head.php';
?>
<div class="adm">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="adm-main">
    <div class="adm-topbar">
      <div class="adm-breadcrumb">Admin <span class="sep">/</span> <strong>Sản phẩm</strong></div>
      <div class="adm-topbar-right">
        <a href="<?= admThemeToggleUrl() ?>" class="adm-theme-btn" title="Đổi giao diện sáng/tối"><?= admThemeIcon() ?></a>
        <button class="btn btn-primary" onclick="openEditor()">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="1" x2="8" y2="15"/><line x1="1" y1="8" x2="15" y2="8"/></svg>
          Thêm sản phẩm
        </button>
      </div>
    </div>

    <div class="adm-content">
      <?php if($msg): ?><div class="adm-alert adm-alert-ok">✓ <?= e($msg) ?></div><?php endif; ?>
      <?php if($error): ?><div class="adm-alert adm-alert-err">⚠ <?= e($error) ?></div><?php endif; ?>

      <!-- STATS -->
      <div class="stats-grid stats-grid-3" style="margin-bottom:20px">
        <div class="stat-card">
          <div class="stat-icon si-blue">📦</div>
          <div><div class="stat-num"><?= $total_p ?></div><div class="stat-lbl">Tổng sản phẩm</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-green">✅</div>
          <div><div class="stat-num"><?= $visible ?></div><div class="stat-lbl">Đang hiển thị</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-yellow">🙈</div>
          <div><div class="stat-num"><?= $hidden ?></div><div class="stat-lbl">Đang ẩn</div></div>
        </div>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="toolbar-left">
          <form method="GET" style="display:contents">
            <input type="hidden" name="filter" value="<?= e($filter_tt) ?>">
            <div class="search-wrap">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6.5" cy="6.5" r="5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
              <input class="search-input" type="text" name="s" value="<?= e($s) ?>" placeholder="Tìm tên sản phẩm...">
            </div>
            <select name="cat" class="ss" onchange="this.form.submit()">
              <option value="0">Tất cả danh mục</option>
              <?php foreach($cats as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= e($c['ten_danh_muc']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn-search" type="submit">Tìm</button>
            <?php if($s||$cat): ?><a href="?filter=<?= $filter_tt ?>" style="font-size:12px;color:var(--ink-3);padding:8px 4px">✕ Xoá lọc</a><?php endif; ?>
          </form>
        </div>
        <div class="filter-tabs">
          <a href="?s=<?= urlencode($s) ?>&cat=<?= $cat ?>&filter=all" class="filter-tab <?= $filter_tt==='all'?'active':'' ?>">Tất cả (<?= $total_p ?>)</a>
          <a href="?s=<?= urlencode($s) ?>&cat=<?= $cat ?>&filter=hien" class="filter-tab <?= $filter_tt==='hien'?'active':'' ?>">Hiện (<?= $visible ?>)</a>
          <a href="?s=<?= urlencode($s) ?>&cat=<?= $cat ?>&filter=an" class="filter-tab <?= $filter_tt==='an'?'active':'' ?>">Ẩn (<?= $hidden ?>)</a>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th>#</th><th>Sản phẩm</th><th>Danh mục</th>
              <th>Giá bán</th><th>Trạng thái</th><th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
          <?php if(empty($products)): ?>
            <tr><td colspan="6" class="table-empty"><span class="te-icon">📦</span>Không tìm thấy sản phẩm nào.</td></tr>
          <?php endif; ?>
          <?php foreach($products as $p):
            $isHien = $p['trang_thai'] === 'hien';
            $disc = ($p['gia_goc']>0&&$p['gia_goc']>$p['gia_ban'])
              ? round((1-$p['gia_ban']/$p['gia_goc'])*100) : 0;
          ?>
          <tr>
            <td class="mono text-muted2"><?= $p['id'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:12px">
                <?php if(!empty($p['hinh_anh'])): ?>
                  <img class="prod-thumb" src="<?= SITE_URL ?>/images/<?= e($p['hinh_anh']) ?>" alt="" onerror="this.src=''">
                <?php else: ?>
                  <div class="prod-thumb" style="display:flex;align-items:center;justify-content:center;font-size:20px">📦</div>
                <?php endif; ?>
                <div>
                  <div class="prod-name"><?= e($p['ten_san_pham']) ?><?php if($p['la_moi']): ?> <span class="badge b-red" style="font-size:9px;padding:2px 6px">MỚI</span><?php endif; ?></div>
                  <div class="prod-ver"><?= e($p['phien_ban']??'') ?></div>
                </div>
              </div>
            </td>
            <td>
              <span class="badge b-blue"><span class="badge-dot"></span><?= e($p['ten_danh_muc']) ?></span>
            </td>
            <td>
              <div class="price-main"><?= fmtVND($p['gia_ban']) ?><?php if($disc>0): ?><span class="discount-chip">-<?= $disc ?>%</span><?php endif; ?></div>
              <?php if($p['gia_goc']>0): ?><div class="price-old"><?= fmtVND($p['gia_goc']) ?></div><?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $isHien?'b-green':'b-gray' ?>">
                <span class="badge-dot"></span><?= $isHien?'Hiển thị':'Đang ẩn' ?>
              </span>
            </td>
            <td>
              <div class="act-row">
                <button class="act-btn ab-edit" title="Sửa"
                  onclick='openEditor(<?= json_encode($p,JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
                <form method="POST" style="display:contents">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="act-btn ab-toggle" type="submit" title="<?= $isHien?'Ẩn':'Hiện' ?>">
                    <?= $isHien ? '🙈' : '👁' ?>
                  </button>
                </form>
                <a class="act-btn ab-view" href="<?= SITE_URL ?>/product-detail.php?slug=<?= e($p['slug']) ?>" target="_blank" title="Xem">👁</a>
                <form method="POST" style="display:contents" onsubmit="return confirm('Xoá sản phẩm «<?= e($p['ten_san_pham']) ?>»?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="act-btn ab-del" type="submit" title="Xoá">🗑</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /adm-content -->
  </main>
</div>

<!-- ════ EDITOR MODAL ════ -->
<div class="editor-overlay" id="editorOverlay" onclick="if(event.target===this)closeEditor()">
  <div class="editor-panel">
    <div class="ep-header">
      <div class="ep-header-left">
        <span class="ep-badge" id="epBadge">📦 Mới</span>
        <span class="ep-title" id="epTitle">Thêm sản phẩm mới</span>
      </div>
      <button class="ep-close" onclick="closeEditor()">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data" id="productForm">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" id="fId" value="0">
      <input type="hidden" name="hinh_anh_cu" id="fHinhCu" value="">
      <div class="ep-body">
        <!-- Tên + Danh mục -->
        <div class="form-row form-row-2">
          <div class="fg" style="margin:0">
            <label>Tên sản phẩm *</label>
            <input type="text" name="ten_san_pham" id="fTen" required placeholder="VD: Adobe Photoshop 2025">
          </div>
          <div class="fg" style="margin:0">
            <label>Danh mục *</label>
            <select name="danh_muc_id" id="fDm" required>
              <option value="">— Chọn danh mục —</option>
              <?php foreach($cats as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['ten_danh_muc']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <!-- Giá -->
        <div class="form-row form-row-3">
          <div class="fg" style="margin:0">
            <label>Giá bán (đ) *</label>
            <input type="number" name="gia_ban" id="fGb" required min="0" placeholder="350000">
          </div>
          <div class="fg" style="margin:0">
            <label>Giá gốc (đ)</label>
            <input type="number" name="gia_goc" id="fGg" min="0" placeholder="500000">
          </div>
          <div class="fg" style="margin:0">
            <label>Phiên bản</label>
            <input type="text" name="phien_ban" id="fPv" placeholder="2025">
          </div>
        </div>
        <!-- Mô tả -->
        <div class="fg" style="margin:0">
          <label>Mô tả sản phẩm</label>
          <textarea name="mo_ta" id="fMota" placeholder="Mô tả ngắn về sản phẩm..." style="min-height:80px"></textarea>
        </div>
        <!-- Ảnh + trạng thái -->
        <div class="form-row form-row-2">
          <div class="fg" style="margin:0">
            <label>Hình ảnh</label>
            <div class="img-upload-area" onclick="document.getElementById('fHinh').click()">
              <input type="file" name="hinh_anh" id="fHinh" accept="image/*"
                     style="display:none" onchange="previewImg(this)">
              <div id="imgPreviewWrap">
                <div style="font-size:28px;margin-bottom:6px">🖼️</div>
                <div style="font-size:12px;color:var(--ink-3)">Click để chọn ảnh (JPG/PNG, tối đa 5MB)</div>
              </div>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div class="fg" style="margin:0">
              <label>Trạng thái</label>
              <select name="trang_thai" id="fTt">
                <option value="hien">✅ Hiển thị — công khai</option>
                <option value="an">🙈 Ẩn — không hiển thị</option>
              </select>
            </div>
            <div class="fg" style="margin:0">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" name="la_moi" id="fLm" style="width:16px;height:16px;accent-color:var(--green)">
                <span style="text-transform:none;letter-spacing:0;font-size:13px;font-weight:600;color:var(--ink)">Đánh dấu là sản phẩm mới 🆕</span>
              </label>
            </div>
          </div>
        </div>
      </div>
      <div class="ep-footer">
        <button type="submit" class="btn-save">💾 Lưu sản phẩm</button>
        <button type="button" class="btn-cancel-ep" onclick="closeEditor()">Huỷ</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditor(p) {
  const ov = document.getElementById('editorOverlay');
  ov.classList.add('open');
  document.body.style.overflow = 'hidden';
  if (p) {
    document.getElementById('epBadge').textContent = '✏️ Sửa';
    document.getElementById('epTitle').textContent = 'Sửa sản phẩm';
    document.getElementById('fId').value   = p.id;
    document.getElementById('fTen').value  = p.ten_san_pham || '';
    document.getElementById('fDm').value   = p.danh_muc_id || '';
    document.getElementById('fGb').value   = p.gia_ban || '';
    document.getElementById('fGg').value   = p.gia_goc || '';
    document.getElementById('fPv').value   = p.phien_ban || '';
    document.getElementById('fMota').value = p.mo_ta || '';
    document.getElementById('fTt').value   = p.trang_thai || 'hien';
    document.getElementById('fLm').checked = p.la_moi == 1;
    document.getElementById('fHinhCu').value = p.hinh_anh || '';
    // show existing image
    const pw = document.getElementById('imgPreviewWrap');
    if (p.hinh_anh) {
      pw.innerHTML = `<img class="img-preview" src="<?= SITE_URL ?>/images/${p.hinh_anh}" onerror="this.style.display='none'">
        <div style="font-size:12px;color:var(--ink-3)">Click để đổi ảnh</div>`;
    } else {
      pw.innerHTML = '<div style="font-size:28px;margin-bottom:6px">🖼️</div><div style="font-size:12px;color:var(--ink-3)">Click để chọn ảnh</div>';
    }
  } else {
    document.getElementById('epBadge').textContent = '📦 Mới';
    document.getElementById('epTitle').textContent = 'Thêm sản phẩm mới';
    document.getElementById('fId').value = '0';
    document.getElementById('productForm').reset();
    document.getElementById('fId').value = '0';
    document.getElementById('imgPreviewWrap').innerHTML = '<div style="font-size:28px;margin-bottom:6px">🖼️</div><div style="font-size:12px;color:var(--ink-3)">Click để chọn ảnh (JPG/PNG, tối đa 5MB)</div>';
  }
  setTimeout(() => document.getElementById('fTen').focus(), 100);
}

function closeEditor() {
  document.getElementById('editorOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

function previewImg(input) {
  const f = input.files[0]; if (!f) return;
  const r = new FileReader();
  r.onload = e => {
    document.getElementById('imgPreviewWrap').innerHTML =
      `<img class="img-preview" src="${e.target.result}">
       <div style="font-size:12px;color:var(--ink-3);margin-top:4px">${f.name}</div>`;
  };
  r.readAsDataURL(f);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditor(); });

<?php if($editProduct): ?>
openEditor(<?= json_encode($editProduct, JSON_UNESCAPED_UNICODE) ?>);
<?php endif; ?>
</script>
</body>
</html>