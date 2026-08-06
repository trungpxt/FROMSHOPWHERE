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
