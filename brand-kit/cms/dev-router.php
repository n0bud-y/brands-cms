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
if (preg_match('#^/cms/(?!assets/|clear-cache\.php$)#', $path)) {
    http_response_code(403);
    return true;
}

return false;
