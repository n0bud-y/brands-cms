<?php
/**
 * Blog page header = the website's own header:
 *
 *     include 'includes/header.php';   (path set by 'site_header' in cms/config.php)
 *
 * This page's SEO tags (title, description, canonical, social, schema) are put into that header's
 * <head> automatically, replacing its own title/description, so the header file needs no changes.
 * If the website has no such file, the simple header further down is used instead.
 *
 * @var array $meta  see cms_header()
 */
if ($siteHeader = cms_site_file('site_header')) {
    echo cms_seo_head(cms_render_site_file($siteHeader, $meta), $meta);
    echo "\n<main class=\"cms-blog\">\n";

    return;
}

$siteName = cms_site_name();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (setting_raw('favicon') !== ''): ?>
    <link rel="icon" href="<?= setting('favicon') ?>">
<?php endif ?>
    <?= cms_head_tags($meta) ?>
</head>
<body>
<?php cms_body_code() ?>
<header class="cms-site-header">
    <nav class="cms-wrap cms-wrap-xl">
        <a class="cms-site-logo" href="/">
<?php if (setting_raw('logo') !== ''): ?>
            <img src="<?= setting('logo') ?>" alt="<?= cms_e($siteName) ?>">
<?php else: ?>
            <?= cms_e($siteName) ?>
<?php endif ?>
        </a>
        <span>
            <a href="/blogs">Blog</a>
<?php if (setting_raw('phone') !== ''): ?>
            <a href="tel:<?= cms_e(cms_tel(setting_raw('phone'))) ?>"><?= setting('phone') ?></a>
<?php endif ?>
        </span>
    </nav>
</header>
<main class="cms-blog">
