<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = '';
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đã có lỗi xảy ra | FROMSHOPWHERE</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="assets/css/style.css?v=<?= CSS_VER ?>">
<style>
.err-wrap{
    min-height:calc(100vh - 64px);
    display:flex;align-items:center;justify-content:center;
    text-align:center;padding:60px 24px;position:relative;overflow:hidden;
}
.err-wrap::before{
    content:'';position:absolute;inset:0;
    background-image:
        linear-gradient(rgba(var(--accent-rgb),.06) 1px,transparent 1px),
        linear-gradient(90deg,rgba(var(--accent-rgb),.06) 1px,transparent 1px);
    background-size:48px 48px;
    -webkit-mask-image:radial-gradient(ellipse 60% 70% at 50% 40%,#000 0%,transparent 75%);
    mask-image:radial-gradient(ellipse 60% 70% at 50% 40%,#000 0%,transparent 75%);
    pointer-events:none;
}
.err-inner{position:relative;z-index:1;max-width:520px}
.err-icon{font-size:56px;margin-bottom:18px}
.err-code{
    font-family:var(--font-display);font-weight:700;
    font-size:clamp(48px,10vw,84px);line-height:1;
    background:linear-gradient(135deg,var(--accent-700),var(--accent-500));
    -webkit-background-clip:text;background-clip:text;color:transparent;
    letter-spacing:-.02em;margin-bottom:8px;
}
.err-title{font-size:clamp(20px,3vw,26px);font-weight:700;color:var(--text);margin-bottom:10px;font-family:var(--font-display)}
.err-sub{font-size:15px;color:var(--text-sub);margin-bottom:32px;line-height:1.6}
.err-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
</style>
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="err-wrap">
    <div class="err-inner">
        <div class="err-icon">⚠️</div>
        <div class="err-code">Rất tiếc</div>
        <h1 class="err-title">Đã có lỗi xảy ra</h1>
        <p class="err-sub">Hệ thống đang gặp sự cố tạm thời. Đội ngũ kỹ thuật đã được thông báo. Bạn vui lòng thử lại sau ít phút, hoặc quay về trang chủ.</p>
        <div class="err-actions">
            <a class="btn-primary" href="index.php" style="background:var(--accent-600);color:#fff">Về trang chủ</a>
            <a class="btn-ghost" href="contact.php">Liên hệ hỗ trợ</a>
        </div>
    </div>
</section>
</body>
</html>
