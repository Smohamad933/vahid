<?php
/**
 * نقشه سایت پویا (پشتیبانی از فراخوانی مستقیم sitemap.php در کنار sitemap.xml ایستا)
 */
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=utf-8');
$canonical = !empty($siteData['seo']['canonicalUrl'])
    ? rtrim((string)$siteData['seo']['canonicalUrl'], '/') . '/'
    : (SITE_FULL_BASE . '/');

$lastmod = !empty($siteData['meta']['updatedAt']) ? substr((string)$siteData['meta']['updatedAt'], 0, 10) : date('Y-m-d');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= htmlspecialchars($canonical, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
    <lastmod><?= e($lastmod) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
