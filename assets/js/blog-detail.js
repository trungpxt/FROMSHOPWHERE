document.addEventListener('DOMContentLoaded', () => {
    restoreTheme(); updateCartBadge(); syncCartPanel();
    document.addEventListener('click', e => {
        const d = document.getElementById('uDrop');
        if (d && !d.parentElement.contains(e.target)) d.classList.remove('open');
    });
    renderLocalWordFallback();
});

/* ── Dự phòng xem trước file Word khi đang chạy localhost (XAMPP) ──
   Ở hosting thật, file Word được nhúng thẳng qua Office Online Viewer (iframe) — xem file
   gốc 100%. Nhưng Office Online là dịch vụ của Microsoft chạy trên internet, nó phải tự tải
   được file qua URL công khai, nên KHÔNG hoạt động với "http://localhost/...". Vì vậy lúc
   đang phát triển trên XAMPP, PHP sẽ in ra khối `.fsw-word-doc-embed[data-docx-src]` thay vì
   iframe, và đoạn JS này sẽ tự dựng lại file bằng docx-preview.js ngay trong trình duyệt để
   bạn vẫn xem trước được (độ chính xác rất cao dù không phải file gốc tuyệt đối như bản
   Office Online). Chỉ tải thư viện khi trang thực sự có khối nào cần dùng tới. */
let __docxPreviewLoading = null;
function loadDocxPreview() {
    if (window.docx && window.JSZip) return Promise.resolve();
    if (__docxPreviewLoading) return __docxPreviewLoading;
    __docxPreviewLoading = new Promise((resolve, reject) => {
        const jszipS = document.createElement('script');
        jszipS.src = 'https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js';
        jszipS.onload = () => {
            const dxS = document.createElement('script');
            dxS.src = 'https://cdn.jsdelivr.net/npm/docx-preview@0.3.7/dist/docx-preview.min.js';
            dxS.onload = () => resolve();
            dxS.onerror = () => reject(new Error('load-failed'));
            document.head.appendChild(dxS);
        };
        jszipS.onerror = () => reject(new Error('load-failed'));
        document.head.appendChild(jszipS);
    });
    return __docxPreviewLoading;
}

function renderLocalWordFallback() {
    const targets = document.querySelectorAll('.fsw-word-doc-embed[data-docx-src]');
    if (!targets.length) return;
    loadDocxPreview().then(() => {
        targets.forEach(el => {
            const url = el.getAttribute('data-docx-src');
            fetch(url)
                .then(r => { if (!r.ok) throw new Error('fetch-failed'); return r.arrayBuffer(); })
                .then(buf => {
                    el.innerHTML = '';
                    const box = document.createElement('div');
                    box.className = 'fsw-word-doc fswdoc';
                    el.appendChild(box);
                    return docx.renderAsync(buf, box, box, {
                        className: 'fswdoc',
                        inWrapper: false,
                        ignoreWidth: true,
                        ignoreHeight: true,
                        breakPages: false,
                        ignoreLastRenderedPageBreak: true,
                        renderHeaders: false,
                        renderFooters: false,
                        useBase64URL: true,
                    });
                })
                .catch(() => {
                    el.innerHTML = '<div class="fsw-word-doc-error">⚠ Không xem trước được file này ở localhost. Bạn có thể tải file gốc bên dưới để xem.</div>';
                });
        });
    }).catch(() => {
        targets.forEach(el => {
            el.innerHTML = '<div class="fsw-word-doc-error">⚠ Không tải được thư viện xem trước. Bạn có thể tải file gốc bên dưới để xem.</div>';
        });
    });
}
