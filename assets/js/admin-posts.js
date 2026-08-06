function openEditor(p, errMsg) {
  const ov = document.getElementById('editorOverlay');
  ov.classList.remove('closing');
  ov.classList.add('open');
  document.body.style.overflow='hidden';
  const isEdit = !!(p && Number(p.id) > 0);
  if(isEdit){
    document.getElementById('epBadge').textContent='✏️ Sửa';
    document.getElementById('epTitle').textContent='Sửa bài viết';
    document.getElementById('fId').value=p.id;
    document.getElementById('fTitle').value=p.tieu_de||'';
    document.getElementById('fContent').value=p.noi_dung||'';
    document.getElementById('fTag').value=p.tag||'';
    document.getElementById('fIconD').value=p.icon||'📝';
    document.getElementById('fIconH').value=p.icon||'📝';
    document.getElementById('tagIcon').textContent=p.icon||'📝';
    document.getElementById('fRt').value=p.read_time||5;
    document.getElementById('fSt').value=p.trang_thai||'nhap';
    document.getElementById('fColor').value=p.tag_color||'#065E34';
    document.getElementById('fHinhCu').value=p.hinh_anh||'';
    setPostImgPreview(p.hinh_anh);
    hiTag(p.tag);
    updC(document.getElementById('fContent'));
    showExistingWordChip(p.noi_dung);
  } else {
    document.getElementById('epBadge').textContent='✍️ Mới';
    document.getElementById('epTitle').textContent='Viết bài mới';
    document.getElementById('fId').value='0';
    document.getElementById('postForm').reset();
    document.getElementById('fId').value='0';
    document.getElementById('fIconH').value='📝';
    document.getElementById('tagIcon').textContent='📝';
    document.getElementById('fHinhCu').value='';
    setPostImgPreview(null);
    document.getElementById('fWordImport').value='';
    document.getElementById('wordFileChip').style.display='none';
    document.querySelectorAll('.tag-o').forEach(e=>e.classList.remove('sel'));
    // nếu mở lại sau khi lưu lỗi nhưng vẫn ở chế độ "viết mới", giữ lại dữ liệu đã nhập
    if(p){
      document.getElementById('fTitle').value=p.tieu_de||'';
      document.getElementById('fContent').value=p.noi_dung||'';
      document.getElementById('fTag').value=p.tag||'';
      if(p.icon){ document.getElementById('fIconD').value=p.icon; document.getElementById('fIconH').value=p.icon; document.getElementById('tagIcon').textContent=p.icon; }
      document.getElementById('fRt').value=p.read_time||5;
      document.getElementById('fSt').value=p.trang_thai||'nhap';
      if(p.tag_color) document.getElementById('fColor').value=p.tag_color;
      document.getElementById('fHinhCu').value=p.hinh_anh||'';
      setPostImgPreview(p.hinh_anh);
      hiTag(p.tag);
      document.getElementById('fWordImport').value='';
      showExistingWordChip(p.noi_dung);
    }
    updC(document.getElementById('fContent'));
  }
  const err = document.getElementById('epError');
  if(errMsg){ err.textContent='⚠ '+errMsg; err.style.display='flex'; }
  else { err.style.display='none'; err.textContent=''; }
  setTimeout(()=>document.getElementById('fTitle').focus(),100);
}
function showExistingWordChip(noiDung){
  const chip = document.getElementById('wordFileChip');
  if (!chip) return;
  const m = /<!--fsw-word-file:([^>]*?)-->/.exec(noiDung || '');
  if (m) {
    const fname = m[1].split('/').pop();
    chip.style.display = 'inline-flex';
    chip.textContent = '📎 ' + fname + ' (đã đính kèm — chọn file mới để thay thế)';
  } else {
    chip.style.display = 'none';
  }
}
function setPostImgPreview(hinh){
  const pw = document.getElementById('postImgPreviewWrap');
  if(!pw) return;
  if(hinh){
    pw.innerHTML = `<img class="img-preview" src="${SITE_URL}/images/${hinh}" style="max-width:100%;max-height:140px;border-radius:8px" onerror="this.style.display='none'">
      <div style="font-size:12px;color:var(--ink-3);margin-top:6px">Click để đổi ảnh</div>`;
  } else {
    pw.innerHTML = '<div style="font-size:28px;margin-bottom:6px">🖼️</div><div style="font-size:12px;color:var(--ink-3)">Click để chọn ảnh (JPG/PNG/WEBP, tối đa 5MB)</div>';
  }
}
function previewPostImg(input){
  const f = input.files[0]; if(!f) return;
  const r = new FileReader();
  r.onload = e => {
    document.getElementById('postImgPreviewWrap').innerHTML =
      `<img class="img-preview" src="${e.target.result}" style="max-width:100%;max-height:140px;border-radius:8px">
       <div style="font-size:12px;color:var(--ink-3);margin-top:6px">${f.name}</div>`;
  };
  r.readAsDataURL(f);
}
function closeEditor(){
  const ov = document.getElementById('editorOverlay');
  if(!ov || !ov.classList.contains('open')) return;
  ov.classList.add('closing');
  document.getElementById('tagDd').classList.remove('open');
  setTimeout(()=>{
    ov.classList.remove('open');
    ov.classList.remove('closing');
    document.body.style.overflow='';
  }, 180);
}
function toggleTagDd(){document.getElementById('tagDd').classList.toggle('open');}
function selTag(n,i,c){
  document.getElementById('fTag').value=n;
  document.getElementById('fIconD').value=i;
  document.getElementById('fIconH').value=i;
  document.getElementById('fColor').value=c;
  document.getElementById('tagIcon').textContent=i;
  document.getElementById('tagDd').classList.remove('open');
  hiTag(n);
}
function hiTag(n){document.querySelectorAll('.tag-o').forEach(e=>e.classList.toggle('sel',e.textContent.trim().includes(n)));}
if(!window.__admPostsBound){
  window.__admPostsBound = true;
  document.addEventListener('click',e=>{
    const dd=document.getElementById('tagDd');
    if(dd&&!dd.closest('.tag-pw').contains(e.target))dd.classList.remove('open');
  });
  document.addEventListener('keydown',e=>{
    if(e.key!=='Escape') return;
    if(document.getElementById('contentFg') && document.getElementById('contentFg').classList.contains('zoomed')){
      closeContentFull();
    } else {
      closeEditor();
    }
  });
}
function ins(t){
  const ta=document.getElementById('fContent');
  const s=ta.selectionStart,e=ta.selectionEnd;
  ta.value=ta.value.slice(0,s)+t+ta.value.slice(e);
  ta.selectionStart=ta.selectionEnd=s+t.length;
  ta.focus();updC(ta);
}
function updC(ta){
  const l=ta.value.length,m=Math.max(1,Math.ceil(l/800));
  document.getElementById('charC').textContent=l.toLocaleString('vi-VN')+' ký · ~'+m+' phút';
  document.getElementById('fRt').value=m;
}

/* ── Mở rộng khung Nội dung gần full màn hình để đọc/sửa dễ hơn ── */
let _contentZoomScrollY = 0;
function openContentFull(){
  document.getElementById('contentFg').classList.add('zoomed');
  document.getElementById('contentZoomBackdrop').classList.add('show');
  document.getElementById('contentExpandBtn').style.display='none';
  document.getElementById('contentCollapseBtn').style.display='';
  // Khoá cuộn trang nền lại để khung "dính" cố định trên màn hình,
  // không bị xê dịch dù con trỏ chuột/cuộn trang di chuyển ở đâu
  _contentZoomScrollY = window.scrollY || document.documentElement.scrollTop || 0;
  document.body.classList.add('content-zoom-lock');
  document.body.style.top = (-_contentZoomScrollY) + 'px';
  const ta = document.getElementById('fContent');
  ta.focus();
  ta.selectionStart = ta.selectionEnd = ta.value.length;
}
function closeContentFull(){
  document.getElementById('contentFg').classList.remove('zoomed');
  document.getElementById('contentZoomBackdrop').classList.remove('show');
  document.getElementById('contentExpandBtn').style.display='';
  document.getElementById('contentCollapseBtn').style.display='none';
  // Mở khoá cuộn trang và khôi phục đúng vị trí đã cuộn trước đó
  document.body.classList.remove('content-zoom-lock');
  document.body.style.top='';
  window.scrollTo(0, _contentZoomScrollY);
}

/* ── Đính kèm file Word (.docx) ──
   Không quy đổi/render gì ở admin nữa: file .docx được giữ NGUYÊN VẸN và
   upload lên server cùng lúc lưu bài (form đã có enctype=multipart/form-data).
   Ở đây chỉ kiểm tra sơ bộ + chèn một "mốc" [[FSW_WORD_FILE]] vào vị trí
   con trỏ trong ô Nội dung; khi lưu bài, admin/posts.php sẽ thay mốc này
   bằng đường dẫn file thật. Trang blog công khai sẽ tự render trực tiếp
   file .docx gốc bằng docx-preview.js (xem assets/js/blog-detail.js),
   nên luôn đúng y hệt font/cỡ chữ/màu/bảng/ảnh của file Word — không có
   bước quy đổi trung gian nào có thể làm lệch hoặc mất dữ liệu. */
function importWordFile(input){
  const file = input.files && input.files[0];
  const chip = document.getElementById('wordFileChip');
  if (!file) { if (chip) chip.style.display = 'none'; return; }

  if (!/\.docx$/i.test(file.name)) {
    alert('Chỉ hỗ trợ file .docx.');
    input.value = '';
    if (chip) chip.style.display = 'none';
    return;
  }
  if (file.size > 20 * 1024 * 1024) {
    alert('File Word tối đa 20MB.');
    input.value = '';
    if (chip) chip.style.display = 'none';
    return;
  }

  if (chip) {
    chip.style.display = 'inline-flex';
    chip.textContent = '📎 ' + file.name;
  }

  const ta = document.getElementById('fContent');
  if (ta.value.includes('[[FSW_WORD_FILE]]')) return; // đã có mốc rồi (vd sửa lại lựa chọn file), không chèn trùng
  const replace = ta.value.trim() === '' || confirm('Chèn file Word vào toàn bộ nội dung (thay thế nội dung hiện tại)?\n(Bấm Cancel để chèn thêm vào vị trí con trỏ thay vì thay thế)');
  const marker = '\n\n[[FSW_WORD_FILE]]\n\n';
  if (replace) {
    ta.value = marker.trim();
    updC(ta);
  } else {
    ins(marker);
  }
}
