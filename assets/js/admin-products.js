function openEditor(p, errMsg) {
  const ov = document.getElementById('editorOverlay');
  ov.classList.add('open');
  document.body.style.overflow = 'hidden';
  const isEdit = !!(p && Number(p.id) > 0);
  if (isEdit) {
    document.getElementById('epBadge').textContent = '✏️ Sửa';
    document.getElementById('epTitle').textContent = 'Sửa sản phẩm';
    document.getElementById('fId').value   = p.id;
    document.getElementById('fTen').value  = p.ten_san_pham || '';
    document.getElementById('fTh').value   = p.thuong_hieu || '';
    document.getElementById('fDm').value   = p.danh_muc_id || '';
    document.getElementById('fGb').value   = p.gia_ban ?? '';
    document.getElementById('fGg').value   = p.gia_goc || '';
    document.getElementById('fPv').value   = p.phien_ban || '';
    document.getElementById('fMota').value = p.mo_ta || '';
    document.getElementById('fTt').value   = p.trang_thai || 'hien';
    document.getElementById('fLm').checked = p.la_moi == 1;
    document.getElementById('fHinhCu').value = p.hinh_anh || '';
    // show existing image
    const pw = document.getElementById('imgPreviewWrap');
    if (p.hinh_anh) {
      pw.innerHTML = `<img class="img-preview" src="${SITE_URL}/images/${p.hinh_anh}" onerror="this.style.display='none'">
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
    // nếu mở lại sau khi lưu lỗi nhưng vẫn ở chế độ "thêm mới", giữ lại dữ liệu đã nhập
    if (p) {
      document.getElementById('fTen').value  = p.ten_san_pham || '';
      document.getElementById('fTh').value   = p.thuong_hieu || '';
      document.getElementById('fDm').value   = p.danh_muc_id || '';
      document.getElementById('fGb').value   = p.gia_ban ?? '';
      document.getElementById('fGg').value   = p.gia_goc || '';
      document.getElementById('fPv').value   = p.phien_ban || '';
      document.getElementById('fMota').value = p.mo_ta || '';
      document.getElementById('fTt').value   = p.trang_thai || 'hien';
      document.getElementById('fLm').checked = p.la_moi == 1;
    }
  }
  const err = document.getElementById('epError');
  if (errMsg) { err.textContent = '⚠ ' + errMsg; err.style.display = 'flex'; }
  else { err.style.display = 'none'; err.textContent = ''; }
  setTimeout(() => document.getElementById('fTen').focus(), 100);
}

function closeEditor() {
  const ov = document.getElementById('editorOverlay');
  if (!ov || !ov.classList.contains('open')) return;
  ov.classList.add('closing');
  setTimeout(() => {
    ov.classList.remove('open');
    ov.classList.remove('closing');
    document.body.style.overflow = '';
  }, 180);
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

/* ── VIEWER: xem nhanh sản phẩm mà không rời trang admin ── */
function fmtVNDjs(n) {
  return Number(n || 0).toLocaleString('vi-VN') + 'đ';
}

function openViewer(p) {
  window.__vwCurrentProduct = p; // lưu lại để nút "Sửa" trong viewer dùng được

  document.getElementById('vwTitle').textContent = p.ten_san_pham || '';

  const imgWrap = document.getElementById('vwImgWrap');
  imgWrap.innerHTML = p.hinh_anh
    ? `<img src="${SITE_URL}/images/${p.hinh_anh}" alt="" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.innerHTML='📦'">`
    : '📦';

  document.getElementById('vwCat').innerHTML = '<span class="badge-dot"></span>' + (p.ten_danh_muc || '—');

  const isHien = p.trang_thai === 'hien';
  const isHetHang = p.trang_thai === 'het_hang';
  const statusEl = document.getElementById('vwStatus');
  statusEl.className = 'badge ' + (isHien ? 'b-green' : isHetHang ? 'b-red' : 'b-gray');
  statusEl.innerHTML = '<span class="badge-dot"></span>' + (isHien ? 'Hiển thị' : isHetHang ? 'Hết hàng' : 'Đang ẩn');

  document.getElementById('vwNew').style.display = (p.la_moi == 1) ? '' : 'none';

  document.getElementById('vwPrice').textContent = fmtVNDjs(p.gia_ban);
  const priceOldEl = document.getElementById('vwPriceOld');
  const hasDiscount = p.gia_goc && Number(p.gia_goc) > Number(p.gia_ban);
  priceOldEl.textContent = hasDiscount ? fmtVNDjs(p.gia_goc) : '';
  priceOldEl.style.display = hasDiscount ? '' : 'none';

  const verWrap = document.getElementById('vwVerWrap');
  if (p.phien_ban) {
    verWrap.style.display = '';
    document.getElementById('vwVer').textContent = p.phien_ban;
  } else {
    verWrap.style.display = 'none';
  }

  document.getElementById('vwDesc').textContent = p.mo_ta || 'Chưa có mô tả.';
  document.getElementById('vwPublicLink').href = SITE_URL + '/product-demo.php?id=' + p.id;

  document.getElementById('viewerOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeViewer() {
  const ov = document.getElementById('viewerOverlay');
  if (!ov || !ov.classList.contains('open')) return;
  ov.classList.add('closing');
  setTimeout(() => {
    ov.classList.remove('open');
    ov.classList.remove('closing');
    document.body.style.overflow = '';
  }, 180);
}

function switchViewerToEditor() {
  closeViewer();
  setTimeout(() => openEditor(window.__vwCurrentProduct), 190);
}
