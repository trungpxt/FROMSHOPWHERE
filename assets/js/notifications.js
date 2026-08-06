/* ══════════════════════════════════════════════════════════════
   assets/js/notifications.js — Chuông thông báo trên thanh điều hướng
   Poll api/notifications.php mỗi 20s để cập nhật số lượng chưa đọc
   và danh sách thông báo (liên hệ được trả lời / đơn hàng xác nhận /
   bình luận được phản hồi).
═══════════════════════════════════════════════════════════════ */

const NOTIF_ICONS = { lien_he: '📩', don_hang: '📦', danh_gia: '💬' };

function timeAgoVi(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T'));
    const diffSec = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diffSec < 60) return 'Vừa xong';
    if (diffSec < 3600) return Math.floor(diffSec / 60) + ' phút trước';
    if (diffSec < 86400) return Math.floor(diffSec / 3600) + ' giờ trước';
    return Math.floor(diffSec / 86400) + ' ngày trước';
}

function toggleNotif(e) {
    e.stopPropagation();
    const dd = document.getElementById('notifDropdown');
    if (!dd) return;
    const willOpen = !dd.classList.contains('open');
    dd.classList.toggle('open');
    if (willOpen) fetchNotifications();
}

document.addEventListener('click', (e) => {
    const wrap = document.getElementById('notifWrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('notifDropdown')?.classList.remove('open');
    }
});

async function fetchNotifications() {
    try {
        const res = await fetch('api/notifications.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.ok) return;
        renderNotifBadge(data.unread);
        renderNotifList(data.items || []);
    } catch (err) {
        console.error('Lỗi tải thông báo:', err);
    }
}

function renderNotifBadge(unread) {
    const badge = document.getElementById('notifCount');
    if (!badge) return;
    if (unread > 0) {
        badge.style.display = 'flex';
        badge.textContent = unread > 9 ? '9+' : unread;
    } else {
        badge.style.display = 'none';
    }
}

function renderNotifList(items) {
    const list = document.getElementById('notifList');
    if (!list) return;
    if (!items.length) {
        list.innerHTML = '<div class="notif-empty">Chưa có thông báo nào.</div>';
        return;
    }
    list.innerHTML = items.map(n => `
        <div class="notif-item ${n.da_doc == 0 ? 'unread' : ''}" onclick="openNotif(${n.id}, ${n.link ? `'${n.link.replace(/'/g, "\\'")}'` : 'null'})">
            <div class="notif-item-top">
                <span class="notif-icon">${NOTIF_ICONS[n.loai] || '🔔'}</span>
                <span class="notif-title">${escapeHtmlNotif(n.tieu_de)}</span>
            </div>
            ${n.noi_dung ? `<div class="notif-body">${escapeHtmlNotif(n.noi_dung)}</div>` : ''}
            <div class="notif-time">${timeAgoVi(n.created_at)}</div>
        </div>
    `).join('');
}

function escapeHtmlNotif(s) {
    const div = document.createElement('div');
    div.textContent = s || '';
    return div.innerHTML;
}

async function openNotif(id, link) {
    try {
        await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ action: 'mark_read', id })
        });
    } catch (err) { /* im lặng bỏ qua */ }
    if (link) window.location.href = link;
    else fetchNotifications();
}

async function markAllNotifRead() {
    try {
        await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        fetchNotifications();
    } catch (err) { console.error(err); }
}

if (window.FSW_IS_LOGGED_IN) {
    document.addEventListener('DOMContentLoaded', () => {
        fetchNotifications();
        setInterval(fetchNotifications, 20000); // poll mỗi 20 giây
    });
}
