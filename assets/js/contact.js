document.addEventListener('DOMContentLoaded', () => {
  restoreTheme();
  updateCartBadge();
  syncCartPanel();
});

document.addEventListener('click', e => {
  const m = document.getElementById('uDrop');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});

function handleSubmit(e) {
  // Client-side validation before submit
  const name  = document.getElementById('ctName').value.trim();
  const email = document.getElementById('ctEmail').value.trim();
  const msg   = document.getElementById('ctMsg').value.trim();
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;

  document.querySelectorAll('.ct-input').forEach(el => el.classList.remove('error'));

  let ok = true;
  if (name.length < 2)       { document.getElementById('ctName').classList.add('error');    ok = false; }
  if (!emailRe.test(email))  { document.getElementById('ctEmail').classList.add('error');   ok = false; }
  if (msg.length < 10)       { document.getElementById('ctMsg').classList.add('error');     ok = false; }

  if (!ok) {
    showToast('⚠ Vui lòng kiểm tra lại các trường thông tin.');
    return false;
  }

  // Show loading
  const btn = document.getElementById('ctSubmitBtn');
  btn.classList.add('loading');
  btn.disabled = true;
  return true; // allow normal form POST
}
