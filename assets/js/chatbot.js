/* ══════════════ FSW CHATBOT — trợ lý FAQ miễn phí, chạy client-side ══════════════ */

(function () {

  function stripAccents(str) {
    return str
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd');
  }

  var FAQ = [
    {
      id: 'giao-hang',
      label: '🚚 Giao hàng',
      keywords: ['giao hang', 'giao key', 'nhan key', 'bao lau', 'thoi gian giao', 'key gui qua dau', 'nhan duoc key', 'gui key'],
      answer: 'Sau khi thanh toán thành công, hệ thống tự động gửi license key qua email bạn đăng ký chỉ trong khoảng 5 giây, không cần chờ nhân viên duyệt thủ công. Nếu chưa thấy email, bạn nhớ kiểm tra thêm mục Spam/Quảng cáo nhé!'
    },
    {
      id: 'thanh-toan',
      label: '💳 Thanh toán',
      keywords: ['thanh toan', 'phuong thuc thanh toan', 'vnpay', 'momo', 'zalopay', 'chuyen khoan', 'visa', 'mastercard', 'the tin dung', 'vietqr'],
      answer: 'FROMSHOPWHERE hỗ trợ thanh toán qua VNPay, thẻ Visa/Mastercard, MoMo, ZaloPay, VietQR và chuyển khoản ngân hàng. Bạn chọn phương thức phù hợp ngay ở trang thanh toán sau khi đặt hàng.'
    },
    {
      id: 'bao-hanh',
      label: '🛡️ Bảo hành / Đổi trả',
      keywords: ['bao hanh', 'doi tra', 'hoan tien', 'key loi', 'key die', 'het han', 'refund', 'key bi thu hoi'],
      answer: 'Nếu license key gặp lỗi hoặc bị thu hồi không do lỗi từ phía bạn, hãy liên hệ đội hỗ trợ để được cấp lại hoặc đổi key mới miễn phí. Bạn có thể gửi yêu cầu chi tiết tại trang <a href="contact.php">Liên hệ</a>.'
    },
    {
      id: 'cach-mua',
      label: '🛒 Cách mua hàng',
      keywords: ['mua hang', 'dat hang', 'cach mua', 'huong dan mua', 'lam sao de mua'],
      answer: 'Rất đơn giản: chọn phần mềm cần mua → bấm "Thêm vào giỏ" hoặc "Mua ngay" → điền thông tin và chọn phương thức thanh toán → hoàn tất đơn hàng. Key sẽ được gửi ngay qua email sau khi thanh toán thành công.'
    },
    {
      id: 'cai-dat',
      label: '⚙️ Hướng dẫn kích hoạt',
      keywords: ['cai dat', 'huong dan cai', 'cach kich hoat', 'nhap key', 'kich hoat ban quyen', 'active', 'activate'],
      answer: 'Sau khi nhận key qua email, bạn mở phần mềm tương ứng → chọn mục "Nhập mã bản quyền / Activate" → dán key vào là xong. Nếu cần, đội kỹ thuật có thể hỗ trợ cài đặt từ xa miễn phí — cứ nhắn cho tụi mình nhé.'
    },
    {
      id: 'tai-khoan',
      label: '👤 Tài khoản',
      keywords: ['dang nhap', 'tai khoan', 'quen mat khau', 'doi mat khau', 'dang ky'],
      answer: 'Bạn có thể đăng nhập/đăng ký tài khoản ở góc phải thanh điều hướng. Nếu quên mật khẩu, chọn "Quên mật khẩu" ngay trên trang đăng nhập để nhận link đặt lại qua email.'
    },
    {
      id: 'gia-ca',
      label: '💰 Giá & khuyến mãi',
      keywords: ['gia', 'khuyen mai', 'giam gia', 'ma giam gia', 'coupon', 'sale'],
      answer: 'Giá phần mềm được cập nhật trực tiếp trên trang <a href="products.php">Sản phẩm</a>, luôn kèm giá gốc để bạn dễ so sánh. Theo dõi trang chủ thường xuyên để không bỏ lỡ các đợt giảm giá nhé!'
    },
    {
      id: 'gap-nhan-vien',
      label: '🙋 Gặp nhân viên',
      keywords: ['gap nhan vien', 'nguoi that', 'tu van vien', 'hotline', 'zalo', 'lien he', 'so dien thoai'],
      answer: 'Bạn có thể liên hệ đội ngũ hỗ trợ qua:<br>📧 support@fromshopwhere.com<br>☎️ Hotline miễn phí: 1900 1234 (8:00–22:00 mỗi ngày)<br>💬 Zalo OA: FROMSHOPWHERE Official<br>Hoặc để lại lời nhắn tại trang <a href="contact.php">Liên hệ</a>, đội ngũ sẽ phản hồi trong khoảng 2 giờ.'
    },
    {
      id: 'chao-hoi',
      label: null,
      keywords: ['xin chao', 'chao ban', 'hello', 'hi ', ' hi', 'alo'],
      answer: 'Xin chào! Mình là trợ lý ảo của FROMSHOPWHERE 👋 Bạn cần hỗ trợ gì hôm nay?'
    },
    {
      id: 'cam-on',
      label: null,
      keywords: ['cam on', 'thank', 'thanks', 'tks'],
      answer: 'Không có gì, rất vui được hỗ trợ bạn 😊 Còn câu hỏi nào khác không?'
    }
  ];

  var FALLBACK = 'Mình chưa chắc câu trả lời chính xác cho câu hỏi này 🤔. Bạn có thể liên hệ trực tiếp đội ngũ hỗ trợ:<br>📧 support@fromshopwhere.com<br>☎️ Hotline: 1900 1234 (8:00–22:00)<br>💬 Zalo OA: FROMSHOPWHERE Official<br>Hoặc bấm nút bên dưới để gửi yêu cầu chi tiết.';

  var QUICK_IDS = ['giao-hang', 'thanh-toan', 'bao-hanh', 'cach-mua', 'gap-nhan-vien'];

  function findAnswer(text) {
    var q = stripAccents(text.trim());
    if (!q) return null;
    var best = null, bestScore = 0;
    for (var i = 0; i < FAQ.length; i++) {
      var item = FAQ[i];
      for (var j = 0; j < item.keywords.length; j++) {
        var kw = item.keywords[j];
        if (q.indexOf(kw) !== -1 && kw.length > bestScore) {
          best = item;
          bestScore = kw.length;
        }
      }
    }
    return best;
  }

  function el(tag, cls, html) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (html != null) e.innerHTML = html;
    return e;
  }

  function scrollToBottom(box) {
    box.scrollTop = box.scrollHeight;
  }

  function addMsg(box, text, who) {
    var row = el('div', 'cb-row cb-row-' + who);
    if (who === 'bot') row.appendChild(el('div', 'cb-avatar', '🤖'));
    row.appendChild(el('div', 'cb-bubble cb-bubble-' + who, text));
    box.appendChild(row);
    scrollToBottom(box);
  }

  function addTyping(box) {
    var row = el('div', 'cb-row cb-row-bot cb-typing-row');
    row.appendChild(el('div', 'cb-avatar', '🤖'));
    row.appendChild(el('div', 'cb-bubble cb-bubble-bot cb-typing', '<span></span><span></span><span></span>'));
    box.appendChild(row);
    scrollToBottom(box);
    return row;
  }

  function addQuickReplies(box, sendFn) {
    var wrap = el('div', 'cb-quick');
    QUICK_IDS.forEach(function (id) {
      var item = FAQ.filter(function (f) { return f.id === id; })[0];
      if (!item) return;
      var btn = el('button', 'cb-quick-btn', item.label);
      btn.type = 'button';
      btn.onclick = function () { sendFn(item.label.replace(/^\S+\s/, '')); };
      wrap.appendChild(btn);
    });
    box.appendChild(wrap);
    scrollToBottom(box);
  }

  function addFeedback(box, userText, botText) {
    var row = el('div', 'cb-feedback');
    var up = el('button', 'cb-fb-btn', '👍');
    var down = el('button', 'cb-fb-btn', '👎');
    up.type = 'button';
    down.type = 'button';
    up.title = 'Câu trả lời hữu ích';
    down.title = 'Câu trả lời chưa đúng ý';

    function sendFb(rating) {
      up.disabled = true;
      down.disabled = true;
      row.appendChild(el('span', 'cb-fb-thanks', 'Cảm ơn phản hồi của bạn!'));
      fetch('api/chatbot-feedback.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: userText, reply: botText, rating: rating })
      }).catch(function () {});
    }

    up.onclick = function () { sendFb('tot'); };
    down.onclick = function () { sendFb('chua_tot'); };
    row.appendChild(up);
    row.appendChild(down);
    box.appendChild(row);
    scrollToBottom(box);
  }

  var CONVO_HISTORY = []; // { role: 'user'|'bot', text: string } — ngữ cảnh gửi kèm cho AI

  function escapeHtml(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function addContactQuickReply(box) {
    var wrap = el('div', 'cb-quick');
    var btn = el('button', 'cb-quick-btn cb-quick-btn-contact', '📨 Liên hệ hỗ trợ');
    btn.type = 'button';
    btn.onclick = function () { window.location.href = 'contact.php'; };
    wrap.appendChild(btn);
    box.appendChild(wrap);
    scrollToBottom(box);
  }

  function offlineReply(box, text) {
    var match = findAnswer(text);
    var answer = match ? match.answer : FALLBACK;
    addMsg(box, answer, 'bot');
    CONVO_HISTORY.push({ role: 'bot', text: answer });
    if (!match) {
      addContactQuickReply(box);
    } else {
      addFeedback(box, text, answer);
    }
  }

  /* Gọi backend chatbot thật (api/chatbot.php — tự viết luật, tra cứu đúng dữ
     liệu sản phẩm/giá thật trong database, KHÔNG gọi AI ngoài). Nếu lỗi mạng/
     timeout hiếm gặp, tự động rơi về bộ trả lời FAQ offline bên dưới — người
     dùng luôn nhận được phản hồi. */
  function callAI(text, onSuccess, onFail) {
    var controller = ('AbortController' in window) ? new AbortController() : null;
    var timer = setTimeout(function () { if (controller) controller.abort(); }, 12000);

    fetch('api/chatbot.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text, history: CONVO_HISTORY.slice(-12) }),
      signal: controller ? controller.signal : undefined
    })
      .then(function (res) { return res.json().catch(function () { return {}; }); })
      .then(function (data) {
        clearTimeout(timer);
        if (data && data.ok && data.reply) {
          onSuccess(data.reply);
        } else {
          // AI lỗi (key sai, hết quota, model không tồn tại, server chặn cURL...) ->
          // in ra console để dev debug thay vì âm thầm rơi về FAQ offline không rõ lý do.
          console.warn('[FSW chatbot] Backend trả lời lỗi, đang dùng FAQ offline. Chi tiết:', data && (data.debug || data.error) || data);
          onFail();
        }
      })
      .catch(function (err) {
        clearTimeout(timer);
        console.warn('[FSW chatbot] Không gọi được api/chatbot.php (mạng/CORS/timeout):', err);
        onFail();
      });
  }

  function reply(box, text, sendFn) {
    var typing = addTyping(box);
    var minDelay = 500 + Math.min(text.length * 12, 700);
    var startedAt = Date.now();

    function finish(renderFn) {
      var wait = Math.max(0, minDelay - (Date.now() - startedAt));
      setTimeout(function () {
        typing.remove();
        renderFn();
      }, wait);
    }

    callAI(
      text,
      function (aiText) {
        finish(function () {
          var html = escapeHtml(aiText).replace(/\n/g, '<br>');
          addMsg(box, html, 'bot');
          CONVO_HISTORY.push({ role: 'bot', text: aiText });
          addFeedback(box, text, aiText);
        });
      },
      function () {
        finish(function () { offlineReply(box, text); });
      }
    );
  }

  function initChatbot() {
    var toggleBtn = document.getElementById('cbToggleBtn');
    var panel = document.getElementById('cbPanel');
    var closeBtn = document.getElementById('cbCloseBtn');
    var box = document.getElementById('cbMessages');
    var form = document.getElementById('cbForm');
    var input = document.getElementById('cbInput');
    if (!toggleBtn || !panel) return;

    var started = false;

    function send(text) {
      if (!text) return;
      addMsg(box, text, 'user');
      CONVO_HISTORY.push({ role: 'user', text: text });
      reply(box, text, send);
    }

    function openPanel() {
      panel.classList.add('cb-open');
      toggleBtn.classList.add('cb-toggle-hide');
      if (!started) {
        started = true;
        var typing = addTyping(box);
        setTimeout(function () {
          typing.remove();
          addMsg(box, 'Xin chào! Mình là trợ lý ảo của FROMSHOPWHERE 👋 Bạn cần hỗ trợ gì hôm nay?', 'bot');
          addQuickReplies(box, send);
        }, 500);
      }
      setTimeout(function () { input.focus(); }, 300);
    }

    function closePanel() {
      panel.classList.remove('cb-open');
      toggleBtn.classList.remove('cb-toggle-hide');
    }

    toggleBtn.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var text = input.value;
      input.value = '';
      send(text);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatbot);
  } else {
    initChatbot();
  }
})();
