document.addEventListener('click', e => {
  const m = document.getElementById('userMenu');
  if (m && !m.parentElement.contains(e.target)) m.classList.remove('open');
});

function showTab(name, btn) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('on'));
  document.querySelectorAll('.ptab').forEach(b => b.classList.remove('on'));
  const tabEl = document.getElementById('tab-' + name);
  if (tabEl) {
    tabEl.classList.add('on');
    tabEl.classList.remove('fsw-tab-in');
    void tabEl.offsetWidth;
    tabEl.classList.add('fsw-tab-in');
  }
  if (btn) btn.classList.add('on');
}

document.addEventListener('DOMContentLoaded', () => {
  restoreTheme(); updateCartBadge(); syncCartPanel();
  bindVnPhoneInput(document.getElementById('profilePhone'));

  // Mở tab dựa trên data attribute (set từ PHP) hoặc query string
  const initialTab = document.body.dataset.initialTab;
  if (initialTab) {
    const idx = initialTab === 'pass' ? 1 : (initialTab === 'orders' ? 2 : 0);
    const btn = document.querySelectorAll('.ptab')[idx];
    if (btn) showTab(initialTab, btn);
  }

  const params = new URLSearchParams(window.location.search);
  if (params.get('tab') === 'orders') {
    const ordersTab = document.querySelectorAll('.ptab')[2];
    if (ordersTab) showTab('orders', ordersTab);
  }
});

function copyRefLink() {
  const input = document.getElementById('refLinkInput');
  if (!input) return;
  input.select();
  navigator.clipboard.writeText(input.value).then(() => {
    if (typeof showToast === 'function') showToast('✓ Đã sao chép link giới thiệu');
  });
}
