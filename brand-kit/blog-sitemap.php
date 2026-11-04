<?php
// Blog sitemap at /sitemap-blogs.xml (see htaccess-rules.txt). Needs site_url in cms/config.php.

require __DIR__ . '/cms/brand.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php if (cms_has_posts()): ?>
    <url><loc><?= cms_e(cms_url('/blogs')) ?></loc></url>
<?php endif ?>
<?php foreach (cms_sitemap_posts() as $post): ?>
    <url><loc><?= cms_e(cms_post_url($post['slug'])) ?></loc><lastmod><?= cms_e($post['updated_at']) ?></lastmod></url>
<?php endforeach ?>
</urlset>
