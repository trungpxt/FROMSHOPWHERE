<?php
/**
 * Head cho trang admin (dùng chung).
 * Theme đồng bộ với toàn site qua localStorage('fsw-theme') + class "dark" trên <body>.
 */
if (!defined('SITE_URL')) require_once __DIR__ . '/../config.php';

$admPageTitle = $admPageTitle ?? 'Admin FSW';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($admPageTitle) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= CSS_VER ?>">
<script>const ADMIN_NAV_BASE = "<?= SITE_URL ?>/admin";</script>
<script src="<?= SITE_URL ?>/assets/js/shared.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin-nav.js"></script>
<?php if (!empty($admExtraHead)) echo $admExtraHead; ?>
</head>
<body class="adm-page">
<script>restoreTheme();</script>
