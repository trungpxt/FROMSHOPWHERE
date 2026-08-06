<?php
/**
 * Render HTML sao đánh giá (dùng chung cho cả 2 variant của product card).
 */
function renderRatingStars(float $avgRating, int $ratingCount): string {
    $full = (int) round($avgRating);
    $full = max(0, min(5, $full));
    $stars = '<span class="rt-filled">' . str_repeat('★', $full) . '</span>'
           . '<span class="rt-empty">' . str_repeat('★', 5 - $full) . '</span>';
    $label = $ratingCount > 0
        ? number_format($avgRating, 1) . ' (' . $ratingCount . ')'
        : 'Chưa có đánh giá';
    return $stars . '<span class="rt-label">' . $label . '</span>';
}

/**
 * Render một thẻ sản phẩm (PHP thuần).
 * $p: row từ DB (ten_san_pham, gia_ban, gia_goc, hinh_anh, ten_danh_muc, la_moi, id, avg_rating, rating_count)
 * $variant: 'home' | 'grid'
 */
function renderProductCard(array $p, string $variant = 'home'): void {
    $img  = !empty($p['hinh_anh']) ? 'images/' . $p['hinh_anh'] : 'images/ui/default.jpg';
    $disc = (!empty($p['gia_goc']) && $p['gia_goc'] > $p['gia_ban'])
        ? (int) round((1 - $p['gia_ban'] / $p['gia_goc']) * 100) : 0;
    $detailUrl = 'product-demo.php?id=' . (int)$p['id'];
    $avgRating   = (float)($p['avg_rating'] ?? 0);
    $ratingCount = (int)($p['rating_count'] ?? 0);
    $outOfStock  = ($p['trang_thai'] ?? '') === 'het_hang';

    if ($variant === 'grid'): ?>
    <article class="hero-card prod-card-wrap<?= $outOfStock ? ' prod-card-oos' : '' ?>" style="cursor:pointer" onclick="window.location.href='<?= e($detailUrl) ?>'">
        <a class="hc-icon" href="<?= e($detailUrl) ?>">
            <img src="<?= e($img) ?>" alt="<?= e($p['ten_san_pham']) ?>" loading="lazy"
                 onerror="this.src='images/ui/default.jpg'">
            <?php if ($outOfStock): ?><div class="ribbon-wrap wrap-left"><span class="ribbon ribbon-oos">HẾT HÀNG</span></div>
            <?php elseif (!empty($p['la_moi'])): ?><div class="ribbon-wrap wrap-left"><span class="ribbon ribbon-new">MỚI</span></div><?php endif; ?>
            <?php if ($disc > 0 && !$outOfStock): ?><div class="ribbon-wrap wrap-right"><span class="ribbon ribbon-discount">-<?= $disc ?>%</span></div><?php endif; ?>
        </a>
        <div class="hc-actions-row">
            <button type="button" class="wl-heart" data-pid="<?= (int)$p['id'] ?>"
                    onclick="event.stopPropagation();toggleWishlist(<?= (int)$p['id'] ?>, this)" title="Thêm vào yêu thích" aria-label="Yêu thích">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z"/></svg>
            </button>
            <button type="button" class="cmp-btn" data-pid="<?= (int)$p['id'] ?>"
                    onclick="event.stopPropagation();toggleCompare(<?= (int)$p['id'] ?>,'<?= addslashes(e($p['ten_san_pham'])) ?>',<?= (float)$p['gia_ban'] ?>,'<?= addslashes($img) ?>',this)"
                    title="So sánh sản phẩm" aria-label="So sánh">⇄</button>
        </div>

        <?php if (!empty($p['thuong_hieu'])): ?><div class="hc-brand"><?= e($p['thuong_hieu']) ?></div><?php endif; ?>
        <a class="hc-name" href="<?= e($detailUrl) ?>"><?= e($p['ten_san_pham']) ?></a>
        <div class="hc-rating"><?= renderRatingStars($avgRating, $ratingCount) ?></div>
        <div class="hc-price">
            <?= fmtVND((float)$p['gia_ban']) ?>
            <?php if (!empty($p['gia_goc']) && $p['gia_goc'] > $p['gia_ban']): ?>
                <span class="price-old-inline"><?= fmtVND((float)$p['gia_goc']) ?></span>
            <?php endif; ?>
        </div>
        <div class="hc-tag-row">
            <span class="hc-tag"><?= e($p['ten_danh_muc'] ?? '') ?></span>
        </div>
        <?php if (!$outOfStock): ?><div class="hc-seal-row"><span class="mini-seal" title="Sản phẩm chính hãng, đã xác thực">✓ Chính hãng</span></div><?php endif; ?>
        <div class="prod-card-actions">
            <?php if ($outOfStock): ?>
            <button class="btn-atc" disabled onclick="event.stopPropagation()" style="opacity:.5;cursor:not-allowed">Hết hàng</button>
            <?php else: ?>
            <button class="btn-atc" onclick="event.stopPropagation();addToCart(<?= (int)$p['id'] ?>,'<?= addslashes(e($p['ten_san_pham'])) ?>',<?= (float)$p['gia_ban'] ?>,'<?= addslashes($p['hinh_anh'] ?? '') ?>')">🛒 Thêm vào giỏ</button>
            <?php endif; ?>
        </div>
    </article>
    <?php else: ?>
    <article class="prod-card<?= $outOfStock ? ' prod-card-oos' : '' ?>" style="cursor:pointer" onclick="window.location.href='<?= e($detailUrl) ?>'">
        <a class="pc-thumb" href="<?= e($detailUrl) ?>">
            <img src="<?= e($img) ?>" alt="<?= e($p['ten_san_pham']) ?>" loading="lazy"
                 onerror="this.src='images/ui/default.jpg'">
            <?php if ($outOfStock): ?><div class="ribbon-wrap wrap-left"><span class="ribbon ribbon-oos">HẾT HÀNG</span></div>
            <?php elseif (!empty($p['la_moi'])): ?><div class="ribbon-wrap wrap-left"><span class="ribbon ribbon-new">MỚI</span></div><?php endif; ?>
            <?php if ($disc > 0 && !$outOfStock): ?><div class="ribbon-wrap wrap-right"><span class="ribbon ribbon-discount">-<?= $disc ?>%</span></div><?php endif; ?>
        </a>
        <div class="pc-badges">
            <span class="pc-badge-cat"><?= e($p['ten_danh_muc'] ?? '') ?></span>
            <button type="button" class="wl-heart" data-pid="<?= (int)$p['id'] ?>"
                    onclick="event.stopPropagation();toggleWishlist(<?= (int)$p['id'] ?>, this)" title="Thêm vào yêu thích" aria-label="Yêu thích">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z"/></svg>
            </button>
            <button type="button" class="cmp-btn" data-pid="<?= (int)$p['id'] ?>"
                    onclick="event.stopPropagation();toggleCompare(<?= (int)$p['id'] ?>,'<?= addslashes(e($p['ten_san_pham'])) ?>',<?= (float)$p['gia_ban'] ?>,'<?= addslashes($img) ?>',this)"
                    title="So sánh sản phẩm" aria-label="So sánh">⇄</button>
        </div>
        <?php if (!empty($p['thuong_hieu'])): ?><div class="pc-brand"><?= e($p['thuong_hieu']) ?></div><?php endif; ?>
        <a class="pc-name" href="<?= e($detailUrl) ?>"><?= e($p['ten_san_pham']) ?></a>
        <div class="pc-rating"><?= renderRatingStars($avgRating, $ratingCount) ?></div>
        <div class="pc-price-row">
            <span class="pc-price"><?= fmtVND((float)$p['gia_ban']) ?></span>
            <?php if (!empty($p['gia_goc']) && $p['gia_goc'] > $p['gia_ban']): ?>
                <span class="pc-price-old"><?= fmtVND((float)$p['gia_goc']) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!$outOfStock): ?><div class="pc-seal-row"><span class="mini-seal" title="Sản phẩm chính hãng, đã xác thực">✓ Chính hãng</span></div><?php endif; ?>
        <div class="pc-btns">
            <?php if ($outOfStock): ?>
            <button class="pc-btn-cart" disabled onclick="event.stopPropagation()" style="opacity:.5;cursor:not-allowed">Hết hàng</button>
            <?php else: ?>
            <button class="pc-btn-cart" onclick="event.stopPropagation();addToCart(<?= (int)$p['id'] ?>,'<?= addslashes(e($p['ten_san_pham'])) ?>',<?= (float)$p['gia_ban'] ?>,'<?= addslashes($p['hinh_anh'] ?? '') ?>')">🛒 Thêm vào giỏ</button>
            <?php endif; ?>
        </div>
    </article>
    <?php endif;
}

