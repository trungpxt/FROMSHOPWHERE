<?php
/** Topbar admin — cần include sau khi load admin-head.php */
?>
<div class="adm-topbar">
  <button onclick="document.querySelector('.adm-side').classList.toggle('open');document.querySelector('.adm-side-backdrop').classList.toggle('open')" class="adm-hamburger" aria-label="Mở menu" title="Menu">☰</button>
  <div class="adm-breadcrumb"><?= e($admBreadcrumb ?? 'Admin') ?> <span class="sep">/</span> <strong><?= e($admPageLabel ?? 'Dashboard') ?></strong></div>
  <div class="adm-topbar-right">
    <button onclick="toggleTheme()" class="adm-theme-btn" title="Đổi giao diện sáng/tối" id="admThemeBtn">☀️</button>
    <span class="text-muted2">📅 <?= date('d/m/Y') ?></span>
    <a href="<?= SITE_URL ?>/index.php" class="btn btn-secondary">🌐 Xem website</a>
  </div>
</div>
<div class="adm-side-backdrop" onclick="document.querySelector('.adm-side').classList.remove('open');this.classList.remove('open')"></div>
