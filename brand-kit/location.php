<?php
// Location pages (see htaccess-rules.txt). One file serves every state, city and service page:
//   /california   /california-ghostwriting   /california/los-angeles   /california/los-angeles-ghostwriting

require __DIR__ . '/cms/brand.php';

$part = function ($name) {
    return isset($_GET[$name]) && is_string($_GET[$name]) ? $_GET[$name] : '';
};
$path = trim($part('a') . '/' . $part('b'), '/');
$result = cms_location($path);

if ($result['status'] === 301) {
    header('Location: ' . cms_location_url($result['redirect']), true, 301);
    exit;
}
if ($result['status'] === 503) {
    cms_unavailable();
}
if ($result['status'] !== 200) {
    // Not a location either, so this URL isn't on the website.
    cms_site_not_found();
}

$page = $result['page'];

$crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => cms_site_name() ?: 'Home', 'item' => cms_url('/')]];
foreach ($page['breadcrumbs'] as $i => $crumb) {
    $crumbs[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $crumb['name'], 'item' => cms_location_url($crumb['path'])];
}

cms_header([
    'title' => $page['meta_title'],
    'description' => $page['meta_description'],
    'image' => (string) ($page['banner_image'] ?? ''),
    'url' => cms_location_url($page['path']),
    'jsonld' => ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs],
]);

include __DIR__ . '/cms/locations/' . ($page['type'] === 'service' ? 'service' : 'place') . '.php';

cms_footer();
