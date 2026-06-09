<?php
/** Topbar admin — cần include sau khi load admin-head.php */
?>
<div class="adm-topbar">
  <div class="adm-breadcrumb"><?= e($admBreadcrumb ?? 'Admin') ?> <span class="sep">/</span> <strong><?= e($admPageLabel ?? 'Dashboard') ?></strong></div>
  <div class="adm-topbar-right">
    <a href="<?= admThemeToggleUrl() ?>" class="adm-theme-btn" title="Đổi giao diện sáng/tối"><?= admThemeIcon() ?></a>
    <span class="text-muted2">📅 <?= date('d/m/Y') ?></span>
    <a href="<?= SITE_URL ?>/index.php" target="_blank" class="btn btn-secondary">🌐 Xem website</a>
  </div>
</div>
