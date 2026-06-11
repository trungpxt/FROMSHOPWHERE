<?php
/**
 * Render một thẻ sản phẩm (PHP thuần).
 * $p: row từ DB (ten_san_pham, gia_ban, gia_goc, hinh_anh, ten_danh_muc, la_moi, id)
 * $variant: 'home' | 'grid'
 */
function renderProductCard(array $p, string $variant = 'home'): void {
    $img  = !empty($p['hinh_anh']) ? 'images/' . $p['hinh_anh'] : 'images/default.jpg';
    $disc = (!empty($p['gia_goc']) && $p['gia_goc'] > $p['gia_ban'])
        ? (int) round((1 - $p['gia_ban'] / $p['gia_goc']) * 100) : 0;
    $detailUrl = 'product-demo.php?id=' . (int)$p['id'];

    if ($variant === 'grid'): ?>
    <article class="hero-card prod-card-wrap">
        <a class="hc-icon" href="<?= e($detailUrl) ?>">
            <img src="<?= e($img) ?>" alt="<?= e($p['ten_san_pham']) ?>" loading="lazy"
                 onerror="this.src='images/default.jpg'">
        </a>
        <a class="hc-name" href="<?= e($detailUrl) ?>"><?= e($p['ten_san_pham']) ?></a>
        <?php if (!empty($p['la_moi'])): ?><span class="badge-new-inline">MỚI</span><?php endif; ?>
        <div class="hc-price">
            <?= fmtVND((float)$p['gia_ban']) ?>
            <?php if (!empty($p['gia_goc']) && $p['gia_goc'] > $p['gia_ban']): ?>
                <span class="price-old-inline"><?= fmtVND((float)$p['gia_goc']) ?></span>
            <?php endif; ?>
            <?php if ($disc > 0): ?><span class="disc-badge-inline">-<?= $disc ?>%</span><?php endif; ?>
        </div>
        <div class="hc-tag"><?= e($p['ten_danh_muc'] ?? '') ?></div>
        <div class="prod-card-actions">
            <button class="btn-atc" onclick="addToCart(<?= (int)$p['id'] ?>,'<?= addslashes(e($p['ten_san_pham'])) ?>',<?= (float)$p['gia_ban'] ?>,'<?= addslashes($p['hinh_anh'] ?? '') ?>')">🛒 Mua ngay</button>
            <a class="btn-detail" href="<?= e($detailUrl) ?>">Chi tiết</a>
        </div>
    </article>
    <?php else: ?>
    <article class="prod-card">
        <a class="pc-thumb" href="<?= e($detailUrl) ?>">
            <img src="<?= e($img) ?>" alt="<?= e($p['ten_san_pham']) ?>" loading="lazy"
                 onerror="this.src='images/default.jpg'">
        </a>
        <div class="pc-badges">
            <span class="pc-badge-cat"><?= e($p['ten_danh_muc'] ?? '') ?></span>
            <?php if (!empty($p['la_moi'])): ?><span class="pc-badge-new">MỚI</span><?php endif; ?>
            <?php if ($disc > 0): ?><span class="pc-discount">-<?= $disc ?>%</span><?php endif; ?>
        </div>
        <a class="pc-name" href="<?= e($detailUrl) ?>"><?= e($p['ten_san_pham']) ?></a>
        <div class="pc-price-row">
            <span class="pc-price"><?= fmtVND((float)$p['gia_ban']) ?></span>
            <?php if (!empty($p['gia_goc']) && $p['gia_goc'] > $p['gia_ban']): ?>
                <span class="pc-price-old"><?= fmtVND((float)$p['gia_goc']) ?></span>
            <?php endif; ?>
        </div>
        <div class="pc-btns">
            <button class="pc-btn-cart" onclick="addToCart(<?= (int)$p['id'] ?>,'<?= addslashes(e($p['ten_san_pham'])) ?>',<?= (float)$p['gia_ban'] ?>,'<?= addslashes($p['hinh_anh'] ?? '') ?>')">🛒 Mua ngay</button>
            <a class="pc-btn-detail" href="<?= e($detailUrl) ?>">Chi tiết</a>
        </div>
    </article>
    <?php endif;
}
