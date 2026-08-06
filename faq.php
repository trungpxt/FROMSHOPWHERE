<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = 'faq';

$faqGroups = [
    [
        'title' => '🛒 Mua hàng & Thanh toán',
        'items' => [
            ['q' => 'Mua hàng tại FROMSHOPWHERE như thế nào?',
             'a' => 'Chọn phần mềm cần mua → bấm "Thêm vào giỏ" hoặc "Mua ngay" → điền thông tin nhận hàng → chọn phương thức thanh toán → hoàn tất đơn. Key sẽ được gửi ngay qua email sau khi thanh toán thành công.'],
            ['q' => 'Website hỗ trợ những phương thức thanh toán nào?',
             'a' => 'Chúng tôi hỗ trợ VNPay, thẻ Visa/Mastercard, MoMo, ZaloPay, VietQR và chuyển khoản ngân hàng. Bạn chọn phương thức phù hợp ngay ở trang thanh toán.'],
            ['q' => 'Tại sao giá phần mềm ở đây lại rẻ hơn mua trực tiếp từ hãng?',
             'a' => 'Chúng tôi cung cấp Key dạng OEM và Volume License hợp pháp, tối ưu chi phí vận hành (không cửa hàng, không kho bãi) và giao hàng số 100% qua email nên tiết kiệm được rất nhiều chi phí trung gian.'],
            ['q' => 'Có được xuất hoá đơn / mã giảm giá không?',
             'a' => 'Mã giảm giá được áp dụng ngay tại bước thanh toán nếu bạn có. Về hoá đơn, vui lòng liên hệ đội hỗ trợ để được cung cấp theo yêu cầu.'],
        ],
    ],
    [
        'title' => '🚚 Giao hàng & Kích hoạt',
        'items' => [
            ['q' => 'Bao lâu thì nhận được license key?',
             'a' => 'Ngay sau khi thanh toán thành công, hệ thống tự động gửi key qua email đăng ký chỉ trong khoảng vài giây đến 2 phút, không cần chờ nhân viên duyệt thủ công.'],
            ['q' => 'Không thấy email chứa key thì phải làm sao?',
             'a' => 'Vui lòng kiểm tra thêm mục Spam/Quảng cáo trong hộp thư. Nếu vẫn không thấy sau 15 phút, hãy vào mục "Đơn hàng" trong trang Tài khoản để xem lại key, hoặc liên hệ hỗ trợ.'],
            ['q' => 'Kích hoạt phần mềm bằng key như thế nào?',
             'a' => 'Mở phần mềm tương ứng → chọn mục "Nhập mã bản quyền / Activate" → dán key vào là xong. Nếu cần, đội kỹ thuật hỗ trợ cài đặt từ xa miễn phí qua UltraView/TeamViewer.'],
            ['q' => 'Key có dùng được trên nhiều thiết bị không?',
             'a' => 'Tuỳ loại sản phẩm — thông tin số thiết bị được kích hoạt sẽ ghi rõ trong mô tả từng sản phẩm. Nếu không chắc, hãy hỏi trước khi mua qua khung chat hoặc trang Liên hệ.'],
        ],
    ],
    [
        'title' => '🛡️ Bảo hành & Đổi trả',
        'items' => [
            ['q' => 'Key bị lỗi hoặc không kích hoạt được thì sao?',
             'a' => 'Chúng tôi áp dụng chính sách bảo hành 1 đổi 1 ngay lập tức nếu key gặp lỗi trong quá trình kích hoạt không do lỗi từ phía bạn. Vào trang chi tiết đơn hàng và bấm "Yêu cầu bảo hành" để gửi yêu cầu.'],
            ['q' => 'Chính sách hoàn tiền như thế nào?',
             'a' => 'Nếu chúng tôi không có sản phẩm thay thế hoặc sản phẩm không đúng như mô tả, số tiền của bạn sẽ được hoàn trả đầy đủ. Vui lòng liên hệ đội hỗ trợ kèm mã đơn hàng để được xử lý nhanh nhất.'],
            ['q' => 'Thời gian bảo hành kéo dài bao lâu?',
             'a' => 'Với các gói có thời hạn (1 năm, vĩnh viễn...), chúng tôi bảo hành trong suốt thời gian sử dụng của gói đó. Nếu giữa chừng key bị lỗi do chính sách từ hãng, bạn sẽ được cấp lại key mới tương ứng thời gian còn lại.'],
        ],
    ],
    [
        'title' => '👤 Tài khoản',
        'items' => [
            ['q' => 'Quên mật khẩu thì làm sao lấy lại?',
             'a' => 'Vào trang Đăng nhập → chọn "Quên mật khẩu" → nhập email đã đăng ký để nhận link đặt lại mật khẩu.'],
            ['q' => 'Làm sao xem lại các đơn hàng đã mua?',
             'a' => 'Đăng nhập → vào mục "Tài khoản" ở góc phải thanh điều hướng → chọn tab "Đơn hàng" để xem toàn bộ lịch sử mua hàng và key tương ứng.'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Câu Hỏi Thường Gặp (FAQ) — FROMSHOPWHERE</title>
<meta name="description" content="Giải đáp các câu hỏi thường gặp về mua hàng, thanh toán, giao key, kích hoạt phần mềm và chính sách bảo hành tại FROMSHOPWHERE.">
<link rel="canonical" href="<?= SITE_URL ?>/faq.php">
<meta property="og:type" content="website">
<meta property="og:title" content="Câu Hỏi Thường Gặp — FROMSHOPWHERE">
<meta property="og:description" content="Giải đáp nhanh các thắc mắc về mua hàng, thanh toán, giao key và bảo hành phần mềm bản quyền.">
<meta property="og:image" content="<?= SITE_URL ?>/images/ui/logo.png">
<meta property="og:url" content="<?= SITE_URL ?>/faq.php">
<meta name="twitter:card" content="summary_large_image">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<link rel="stylesheet" href="assets/css/faq.css?v=<?= CSS_VER ?>">
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="page-header">
  <div class="page-header-inner">
    <div class="ph-eyebrow"><span class="mini-seal mini-seal-light">💬 Hỗ trợ khách hàng</span></div>
    <h1>Câu hỏi thường gặp</h1>
    <p>Giải đáp nhanh các thắc mắc phổ biến nhất về mua hàng, thanh toán và bảo hành</p>
  </div>
</div>

<div class="section">
  <div class="faq-wrap">

    <div class="faq-search-wrap">
      <input type="search" id="faqSearch" class="faq-search" placeholder="Tìm câu hỏi... (vd: hoàn tiền, kích hoạt, thanh toán)">
    </div>

    <?php foreach ($faqGroups as $gi => $group): ?>
    <div class="faq-group">
      <h2 class="faq-group-title"><?= e($group['title']) ?></h2>
      <div class="faq-list">
        <?php foreach ($group['items'] as $ii => $item): $itemId = "faq-$gi-$ii"; ?>
        <div class="faq-item" data-q="<?= e(mb_strtolower($item['q'] . ' ' . $item['a'])) ?>">
          <button type="button" class="faq-q" onclick="toggleFaq('<?= $itemId ?>')" aria-expanded="false">
            <span><?= e($item['q']) ?></span>
            <span class="faq-icon" id="icon-<?= $itemId ?>">+</span>
          </button>
          <div class="faq-a" id="<?= $itemId ?>"><p><?= $item['a'] ?></p></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <div id="faqNoResult" class="faq-empty" style="display:none">
      <p>Không tìm thấy câu hỏi phù hợp. Hãy gửi câu hỏi trực tiếp cho chúng tôi nhé!</p>
      <a class="btn-detail" href="contact.php">Liên hệ hỗ trợ →</a>
    </div>

    <div class="faq-cta">
      <div>
        <strong>Vẫn chưa tìm được câu trả lời?</strong>
        <p>Đội ngũ hỗ trợ của chúng tôi phản hồi trong vòng 2 giờ.</p>
      </div>
      <a class="btn-detail" href="contact.php">Liên hệ hỗ trợ →</a>
    </div>

  </div>
</div>

<script>
function toggleFaq(id) {
  const panel = document.getElementById(id);
  const icon  = document.getElementById('icon-' + id);
  const isOpen = panel.classList.contains('open');
  document.querySelectorAll('.faq-a.open').forEach(el => {
    el.classList.remove('open');
    el.style.maxHeight = null;
    const ic = document.getElementById('icon-' + el.id);
    if (ic) ic.textContent = '+';
    const btn = el.previousElementSibling;
    if (btn) btn.setAttribute('aria-expanded', 'false');
  });
  if (!isOpen) {
    panel.classList.add('open');
    panel.style.maxHeight = panel.scrollHeight + 'px';
    icon.textContent = '−';
    panel.previousElementSibling.setAttribute('aria-expanded', 'true');
  }
}

document.getElementById('faqSearch').addEventListener('input', function () {
  const term = this.value.trim().toLowerCase();
  let anyVisible = false;
  document.querySelectorAll('.faq-group').forEach(group => {
    let groupHasVisible = false;
    group.querySelectorAll('.faq-item').forEach(item => {
      const match = !term || item.dataset.q.includes(term);
      item.style.display = match ? '' : 'none';
      if (match) groupHasVisible = true;
    });
    group.style.display = groupHasVisible ? '' : 'none';
    if (groupHasVisible) anyVisible = true;
  });
  document.getElementById('faqNoResult').style.display = anyVisible ? 'none' : '';
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
