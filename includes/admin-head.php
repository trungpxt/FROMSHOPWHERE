<?php
/**
 * Head + theme cho trang admin (dùng chung).
 * Theme lưu cookie PHP — không phụ thuộc localStorage.
 */
if (!defined('SITE_URL')) require_once __DIR__ . '/../config.php';

$admPageTitle = $admPageTitle ?? 'Admin FSW';

if (isset($_GET['adm_theme']) && in_array($_GET['adm_theme'], ['light', 'dark'], true)) {
    setcookie('adm_theme', $_GET['adm_theme'], [
        'expires'  => time() + 31536000,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
    $_COOKIE['adm_theme'] = $_GET['adm_theme'];
    $qs = $_GET;
    unset($qs['adm_theme']);
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    if ($qs) {
        $redirect .= '?' . http_build_query($qs);
    }
    header('Location: ' . $redirect);
    exit;
}

$admTheme = ($_COOKIE['adm_theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

function admThemeToggleUrl(): string {
    global $admTheme;
    $next = $admTheme === 'dark' ? 'light' : 'dark';
    $qs   = $_GET;
    $qs['adm_theme'] = $next;
    return '?' . http_build_query($qs);
}

function admThemeIcon(): string {
    global $admTheme;
    return $admTheme === 'dark' ? '🌙' : '☀️';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($admPageTitle) ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/style.css">
<?php if (!empty($admExtraHead)) echo $admExtraHead; ?>
</head>
<body class="adm-page adm-<?= $admTheme ?>">
