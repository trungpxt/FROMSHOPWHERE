/* ══════════════════════════════════════════════════════════════
   admin-orders.js — Xử lý đổi trạng thái đơn hàng: modal xác nhận
   tuỳ biến (thay window.confirm mặc định của trình duyệt) + màn
   hình chờ khi đang gửi request (gửi email license key/nhắc đánh
   giá có thể mất vài giây).
═══════════════════════════════════════════════════════════════ */

let admConfirmResolver = null;

/** Hiện modal xác nhận tuỳ biến, trả về Promise<boolean> */
function admConfirm({ title, msg, icon = '❓' } = {}) {
  const overlay = document.getElementById('admConfirmOverlay');
  document.getElementById('admConfirmTitle').textContent = title || 'Xác nhận?';
  document.getElementById('admConfirmMsg').textContent   = msg   || '';
  overlay.querySelector('.adm-confirm-icon').textContent = icon;
  overlay.classList.add('open');

  return new Promise(resolve => {
    admConfirmResolver = resolve;
  });
}

/** Gọi bởi 2 nút trong modal (Huỷ / Xác nhận) */
function admConfirmResolve(ok) {
  document.getElementById('admConfirmOverlay').classList.remove('open');
  if (admConfirmResolver) {
    admConfirmResolver(ok);
    admConfirmResolver = null;
  }
}

function showAdmLoading(text) {
  document.getElementById('admLoadingText').textContent = text || 'Đang xử lý…';
  document.getElementById('admLoadingOverlay').classList.add('open');
}

/**
 * Xử lý đổi trạng thái đơn hàng — dùng chung cho:
 *  - form dropdown ở từng dòng bảng (gọi qua onchange — sự kiện này KHÔNG
 *    tự submit form, nên mọi nhánh bên dưới đều phải tự gọi form.submit())
 *  - form trong sidebar chi tiết đơn (gọi qua onsubmit, luôn return false
 *    để chặn submit mặc định, rồi tự form.submit())
 */
function submitStatusForm(form) {
  const statusEl = form.querySelector('[name=trang_thai]');
  if (!statusEl) { showAdmLoading('Đang cập nhật…'); return true; }

  const newVal = statusEl.value;

  if (newVal === 'hoan_thanh') {
    admConfirm({
      icon: '🎉',
      title: 'Xác nhận hoàn thành đơn hàng?',
      msg: 'Hệ thống sẽ tự động gửi email kèm license key cho khách hàng.',
    }).then(ok => {
      if (ok) {
        showAdmLoading('Đang gửi email license key…');
        form.submit();
      } else if (statusEl.tagName === 'SELECT') {
        statusEl.value = statusEl.dataset.prev || 'cho_xu_ly';
      }
    });
    return false; // chặn submit mặc định (nếu gọi từ onsubmit), chờ người dùng xác nhận
  }

  showAdmLoading('Đang cập nhật trạng thái…');
  form.submit(); // gọi tường minh: onchange (dropdown ở bảng) không tự submit, và
                  // form.submit() không kích hoạt lại sự kiện "submit" nên gọi từ
                  // onsubmit (sidebar) cũng an toàn, không lặp vô hạn
  return false;
}

document.querySelectorAll('select[name=trang_thai]').forEach(sel => {
  sel.dataset.prev = sel.value;
  sel.addEventListener('focus', () => { sel.dataset.prev = sel.value; });
});

// Đóng modal xác nhận bằng phím Escape (= Huỷ)
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && document.getElementById('admConfirmOverlay').classList.contains('open')) {
    admConfirmResolve(false);
  }
});
