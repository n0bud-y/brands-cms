<?php
// Location sitemap at /sitemap-locations.xml (see htaccess-rules.txt): every state, city and service page.

require __DIR__ . '/cms/brand.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach (array_keys((array) (cms_locations()['paths'] ?? [])) as $path): ?>
    <url><loc><?= cms_e(cms_location_url($path)) ?></loc></url>
<?php endforeach ?>
</urlset>
