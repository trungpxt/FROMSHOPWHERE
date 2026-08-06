<?php
require_once __DIR__ . '/config.php';
startSession();
$currentPage = '';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="apple-touch-icon" href="images/ui/apple-touch-icon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 — Không tìm thấy trang | FROMSHOPWHERE</title>
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
.err-code{
    font-family:var(--font-display);font-weight:700;
    font-size:clamp(64px,14vw,120px);line-height:1;
    background:linear-gradient(135deg,var(--accent-700),var(--accent-500));
    -webkit-background-clip:text;background-clip:text;color:transparent;
    letter-spacing:-.02em;margin-bottom:8px;
}
.err-title{font-size:clamp(20px,3vw,26px);font-weight:700;color:var(--text);margin-bottom:10px;font-family:var(--font-display)}
.err-sub{font-size:15px;color:var(--text-sub);margin-bottom:32px;line-height:1.6}
.err-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:36px}
.err-search{max-width:380px;margin:0 auto;position:relative}
.err-search input{
    width:100%;padding:12px 44px 12px 16px;border-radius:12px;
    border:1.5px solid var(--border);background:var(--bg-card);color:var(--text);
    font-size:14px;font-family:var(--font-body);box-sizing:border-box;
}
.err-search input:focus{outline:none;border-color:var(--accent-500);box-shadow:0 0 0 3px rgba(var(--accent-rgb),.12)}
.err-search button{
    position:absolute;right:6px;top:50%;transform:translateY(-50%);
    background:var(--accent-600);border:none;color:#fff;
    width:32px;height:32px;border-radius:8px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
}
</style>
</head>
<body>
<script>if(localStorage.getItem('fsw-theme')==='dark')document.body.classList.add('dark');</script>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="err-wrap">
    <div class="err-inner">
        <div class="err-code">404</div>
        <h1 class="err-title">Trang này không tồn tại</h1>
        <p class="err-sub">Có thể đường dẫn đã bị đổi, sản phẩm đã ngừng bán, hoặc bạn gõ nhầm địa chỉ. Thử tìm lại hoặc quay về trang chủ nhé.</p>
        <div class="err-actions">
            <a class="btn-primary" href="index.php" style="background:var(--accent-600);color:#fff">Về trang chủ</a>
            <a class="btn-ghost" href="products.php">Xem tất cả sản phẩm</a>
        </div>
        <form class="err-search" action="products.php" method="get">
            <input type="search" name="q" placeholder="Tìm phần mềm bạn cần...">
            <button type="submit" aria-label="Tìm kiếm">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </button>
        </form>
    </div>
</section>
</body>
</html>
