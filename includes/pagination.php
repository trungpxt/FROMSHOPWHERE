<?php
/* ══════════════════════════════════════════════════════════════════
   includes/pagination.php — Thanh phân trang dùng chung

   Trước đây trang Sản phẩm tải TOÀN BỘ sản phẩm khớp bộ lọc trong 1 lần —
   không sao khi ít sản phẩm, nhưng sẽ chậm và cuộn rất dài khi shop có
   nhiều hàng. Giờ chia trang PAGE_SIZE sản phẩm/trang.
═══════════════════════════════════════════════════════════════════ */

const PRODUCTS_PAGE_SIZE = 24;

/**
 * @param int $page       Trang hiện tại (bắt đầu từ 1)
 * @param int $totalPages Tổng số trang
 * @param callable $urlFn function(int $page): string — build URL cho 1 trang
 */
function renderPagination(int $page, int $totalPages, callable $urlFn): void {
    if ($totalPages <= 1) return;

    $page = max(1, min($page, $totalPages));

    echo '<nav class="pager" aria-label="Phân trang sản phẩm">';

    echo $page > 1
        ? '<a class="pager-btn pager-prev" href="' . e($urlFn($page - 1)) . '" data-page="' . ($page - 1) . '">‹ Trước</a>'
        : '<span class="pager-btn pager-prev pager-disabled">‹ Trước</span>';

    // Danh sách số trang rút gọn: 1 ... p-1 p p+1 ... N
    $items = [];
    $items[] = 1;
    for ($i = $page - 1; $i <= $page + 1; $i++) {
        if ($i > 1 && $i < $totalPages) $items[] = $i;
    }
    if ($totalPages > 1) $items[] = $totalPages;
    $items = array_values(array_unique($items));
    sort($items);

    $prev = 0;
    foreach ($items as $n) {
        if ($prev && $n - $prev > 1) echo '<span class="pager-dots">…</span>';
        if ($n === $page) {
            echo '<span class="pager-num pager-current" aria-current="page">' . $n . '</span>';
        } else {
            echo '<a class="pager-num" href="' . e($urlFn($n)) . '" data-page="' . $n . '">' . $n . '</a>';
        }
        $prev = $n;
    }

    echo $page < $totalPages
        ? '<a class="pager-btn pager-next" href="' . e($urlFn($page + 1)) . '" data-page="' . ($page + 1) . '">Sau ›</a>'
        : '<span class="pager-btn pager-next pager-disabled">Sau ›</span>';

    echo '</nav>';
}
