document.addEventListener('DOMContentLoaded', () => {
  restoreTheme();
  updateCartBadge();
  syncCartPanel();
  if (window.initFswEffects) window.initFswEffects();
});
