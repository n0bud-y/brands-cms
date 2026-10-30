<?php
// Local testing only. PHP's built-in server ignores .htaccess, so this mimics its rules.
// From the website root:  php -S localhost:8080 cms/dev-router.php

$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = dirname(__DIR__);

if ($path === '/' && !is_file($root . '/index.php') && !is_file($root . '/index.html')) {
    header('Location: /blogs');
    return true;
}
if (preg_match('#^/blogs/?$#', $path)) {
    require $root . '/blog.php';
    return true;
}
if (preg_match('#^/blogs/([a-z0-9-]+)/?$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    require $root . '/blog-post.php';
    return true;
}
if ($path === '/sitemap-blogs.xml') {
    require $root . '/blog-sitemap.php';
    return true;
}
if ($path === '/sitemap-locations.xml') {
    require $root . '/location-sitemap.php';
    return true;
}
if ($path === '/sitemap.xml' || $path === '/sitemap-pages.xml') {
    $_GET['part'] = $path === '/sitemap-pages.xml' ? 'pages' : '';
    require $root . '/sitemap.php';
    return true;
}
if (preg_match('#^/locations/?$#', $path)) {
    require $root . '/locations.php';
    return true;
}
// Like the site's own "/page -> page.php" rule.
if (preg_match('#^/([a-z0-9_-]+)$#i', $path, $m) && is_file("{$root}/{$m[1]}.php")) {
    $_SERVER['SCRIPT_NAME'] = "/{$m[1]}.php";
    require "{$root}/{$m[1]}.php";
    return true;
}
if (preg_match('#^/cms/(?!assets/|(?:clear-cache|pages)\.php$)#', $path)) {
    http_response_code(403);
    return true;
}
// Location pages: like the .htaccess rule, only when no real file or page has this name.
if (preg_match('#^/([a-z0-9-]+)(?:/([a-z0-9-]+))?/?$#', $path, $m)
    && !file_exists($root . $path) && !is_file("{$root}/{$m[1]}.php") && !is_file("{$root}/{$m[1]}.html")) {
    $_GET['a'] = $m[1];
    $_GET['b'] = $m[2] ?? '';
    require $root . '/location.php';
    return true;
}

return false;
