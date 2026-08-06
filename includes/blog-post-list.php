  <div id="blog-post-list-wrap">
    <?php if(empty($posts)): ?>
    <div class="blog-empty">
      <div class="ei">🔍</div>
      <p><?= $search ? "Không tìm thấy bài nào cho \"".htmlspecialchars($search)."\"" : "Chưa có bài viết nào." ?></p>
      <?php if($filter || $search): ?><a href="blog.php" style="color:var(--b-lime);font-size:14px;font-weight:700">← Xem tất cả</a><?php endif; ?>
    </div>
    <?php else: ?>

    <!-- Result header -->
    <div class="result-header">
      <span class="result-title">
        <?php if($filter): ?>
          Tag: <strong><?= htmlspecialchars($filter) ?></strong>
        <?php elseif($search): ?>
          Kết quả cho: <strong>"<?= htmlspecialchars($search) ?>"</strong>
        <?php else: ?>
          Bài viết mới nhất
        <?php endif; ?>
      </span>
      <span class="result-count"><?= count($posts) ?> bài viết</span>
    </div>

    <?php
    $featured = array_shift($posts); // First post = featured
    $bg_f = bgByColor($featured['tag_color'] ?? '#065E34');
    $hasImg_f = !empty($featured['hinh_anh']);
    ?>

    <!-- FEATURED POST -->
    <a class="post-featured" href="blog-detail.php?slug=<?= urlencode($featured['slug']) ?>">
      <div class="pf-thumb" style="background:<?= $bg_f ?>">
        <?php if($hasImg_f): ?>
          <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($featured['hinh_anh']) ?>"
               alt="<?= htmlspecialchars($featured['tieu_de']) ?>"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="pf-icon-wrap" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;font-size:72px;background:<?= $bg_f ?>"><?= htmlspecialchars($featured['icon'] ?? '📝') ?></div>
        <?php else: ?>
          <div class="pf-icon-wrap"><?= htmlspecialchars($featured['icon'] ?? '📝') ?></div>
        <?php endif; ?>

        <!-- Tag badge -->
        <span style="position:absolute;top:14px;left:14px;padding:5px 13px;border-radius:20px;font-size:11px;font-weight:700;background:rgba(0,0,0,.55);color:#fff;backdrop-filter:blur(6px);letter-spacing:.05em">
          <?= htmlspecialchars($featured['icon']??'') ?> <?= htmlspecialchars($featured['tag']??'') ?>
        </span>

        <?php if($_isAdmin): ?>
        <div class="pi-admin">
<button type="button"
        class="adm-mini adm-mini-e"
        onclick="event.stopPropagation();location.href='admin/posts.php?edit=<?= $featured['id'] ?>'">
    ✏️
</button>
          <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Ẩn bài?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $featured['id'] ?>">
            <button class="adm-mini adm-mini-h" onclick="event.stopPropagation()">👁</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <div class="pf-body">
        <div class="pf-label">✦ Bài nổi bật</div>
        <h2 class="pf-title"><?= htmlspecialchars($featured['tieu_de']) ?></h2>
        <p class="pf-excerpt"><?= htmlspecialchars(mb_substr($featured['excerpt']??'',0,200)) ?></p>
        <div class="pf-meta">
          <span class="pf-date">📅 <?= date('d/m/Y',strtotime($featured['ngay_dang'])) ?> · ⏱ <?= $featured['read_time']??5 ?> phút</span>
          <span class="pf-read">Đọc ngay →</span>
        </div>
      </div>
    </a>

    <!-- POST LIST -->
    <?php if(!empty($posts)): ?>
    <div class="post-list">
      <?php foreach($posts as $p):
        $bg_p = bgByColor($p['tag_color'] ?? '#065E34');
        $hasImg_p = !empty($p['hinh_anh']);
        $tc = htmlspecialchars($p['tag_color'] ?? '#C8FF00');
      ?>
      <a class="post-item" href="blog-detail.php?slug=<?= urlencode($p['slug']) ?>">
        <div class="pi-thumb" style="background:<?= $bg_p ?>">
          <?php if($hasImg_p): ?>
            <img src="<?= SITE_URL ?>/images/<?= htmlspecialchars($p['hinh_anh']) ?>"
                 alt="<?= htmlspecialchars($p['tieu_de']) ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="pi-thumb-icon" style="display:none;position:absolute;inset:0;background:<?= $bg_p ?>"><?= htmlspecialchars($p['icon']??'📝') ?></div>
          <?php else: ?>
            <div class="pi-thumb-icon" style="background:<?= $bg_p ?>"><?= htmlspecialchars($p['icon']??'📝') ?></div>
          <?php endif; ?>

          <?php if($_isAdmin): ?>
          <div class="pi-admin">
<button type="button"
        class="adm-mini adm-mini-e"
        onclick="location.href='admin/posts.php?edit=<?= $p['id'] ?>'">
    ✏️
</button>
            <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Ẩn bài?')">
            <?= csrfField() ?>
              <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="adm-mini adm-mini-h" onclick="event.stopPropagation()">👁</button>
            </form>
            <form method="POST" action="admin/posts.php" style="display:inline" onsubmit="return confirm('Xoá bài?')">
            <?= csrfField() ?>
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="adm-mini adm-mini-d" onclick="event.stopPropagation()">🗑</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <div class="pi-body">
          <div class="pi-tag" style="color:<?= $tc ?>">
            <?= htmlspecialchars($p['icon']??'') ?> <?= htmlspecialchars($p['tag']??'') ?>
          </div>
          <div class="pi-title"><?= htmlspecialchars($p['tieu_de']) ?></div>
          <div class="pi-excerpt"><?= htmlspecialchars(mb_substr($p['excerpt']??'',0,120)) ?></div>
          <div class="pi-meta">
            <span>📅 <?= date('d/m/Y',strtotime($p['ngay_dang'])) ?></span>
            <span>⏱ <?= $p['read_time']??5 ?> phút</span>
            <span class="read-link">Đọc tiếp →</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; // end if posts ?>
  </div>
