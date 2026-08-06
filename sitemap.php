<?php
/* ══════════════════════════════════════════════════════════════════
   sitemap.php — Sitemap XML động cho SEO
   Tự động liệt kê: trang tĩnh, tất cả sản phẩm đang hiển thị,
   tất cả bài blog đã đăng và danh mục sản phẩm.
   Truy cập: SITE_URL/sitemap.php  (nhớ khai báo trong robots.txt)
═══════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/config.php';

header('Content-Type: application/xml; charset=utf-8');

function sitemapUrl(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string {
    $xml  = "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
    if ($lastmod) $xml .= "    <lastmod>" . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
    $xml .= "    <changefreq>$changefreq</changefreq>\n";
    $xml .= "    <priority>$priority</priority>\n";
    $xml .= "  </url>\n";
    return $xml;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

/* ── Trang tĩnh ── */
$staticPages = [
    ['url' => '/index.php',    'freq' => 'daily',   'pri' => '1.0'],
    ['url' => '/products.php', 'freq' => 'daily',   'pri' => '0.9'],
    ['url' => '/blog.php',     'freq' => 'weekly',  'pri' => '0.7'],
    ['url' => '/faq.php',      'freq' => 'monthly', 'pri' => '0.6'],
    ['url' => '/about.php',    'freq' => 'monthly', 'pri' => '0.5'],
    ['url' => '/contact.php',  'freq' => 'monthly', 'pri' => '0.5'],
    ['url' => '/terms.php',    'freq' => 'yearly',  'pri' => '0.3'],
    ['url' => '/privacy.php',  'freq' => 'yearly',  'pri' => '0.3'],
];
foreach ($staticPages as $p) {
    echo sitemapUrl(SITE_URL . $p['url'], $p['freq'], $p['pri']);
}

/* ── Sản phẩm đang hiển thị ── */
try {
    $products = db()->query(
        "SELECT id, ngay_tao FROM products WHERE trang_thai != 'an' ORDER BY ngay_tao DESC"
    )->fetchAll();
    foreach ($products as $p) {
        echo sitemapUrl(SITE_URL . '/product-demo.php?id=' . (int)$p['id'], 'weekly', '0.8', $p['ngay_tao']);
    }
} catch (Exception $e) {}

/* ── Danh mục sản phẩm ── */
try {
    $cats = db()->query("SELECT ten_danh_muc FROM categories")->fetchAll();
    foreach ($cats as $c) {
        echo sitemapUrl(SITE_URL . '/products.php?cat=' . urlencode($c['ten_danh_muc']), 'weekly', '0.6');
    }
} catch (Exception $e) {}

/* ── Bài blog đã đăng ── */
try {
    $posts = db()->query(
        "SELECT slug, ngay_dang FROM posts WHERE trang_thai = 'da_dang' ORDER BY ngay_dang DESC"
    )->fetchAll();
    foreach ($posts as $post) {
        echo sitemapUrl(SITE_URL . '/blog-detail.php?slug=' . urlencode($post['slug']), 'monthly', '0.6', $post['ngay_dang']);
    }
} catch (Exception $e) {}

echo '</urlset>';
