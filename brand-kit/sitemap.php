<?php
// The website's main sitemap (see htaccess-rules.txt), always up to date:
//   /sitemap.xml        index of the three parts below (the one address to give Google)
//   /sitemap-pages.xml  every page file of the website (new files join by themselves)
//   /sitemap-blogs.xml and /sitemap-locations.xml come from blog-sitemap.php and location-sitemap.php
// Pages hidden from Google in the panel (Pages & SEO) are left out.

require __DIR__ . '/cms/brand.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";

if (($_GET['part'] ?? '') !== 'pages'):
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php // Blogs and locations only once the brand has some. ?>
<?php foreach (array_keys(array_filter(['pages' => true, 'blogs' => cms_has_posts(), 'locations' => (bool) (cms_locations()['paths'] ?? [])])) as $part): ?>
    <sitemap><loc><?= cms_e(cms_url("/sitemap-{$part}.xml")) ?></loc></sitemap>
<?php endforeach ?>
</sitemapindex>
<?php
    return;
endif;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (cms_site_pages() as $page): ?>
<?php if (empty(cms_page_seo($page['path'])['noindex'])): ?>
    <url><loc><?= cms_e(cms_url($page['path'])) ?></loc><lastmod><?= date('c', $page['modified']) ?></lastmod></url>
<?php endif ?>
<?php endforeach ?>
</urlset>
