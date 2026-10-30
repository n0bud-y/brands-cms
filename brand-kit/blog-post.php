<?php
// Single post at /blogs/{slug} (see htaccess-rules.txt). One file serves every post.

require __DIR__ . '/cms/brand.php';

$result = cms_post($_GET['slug'] ?? '');

if ($result['status'] === 301) {
    header('Location: ' . cms_post_url($result['redirect']), true, 301);
    exit;
}
if ($result['status'] === 503) {
    cms_unavailable();
}
if ($result['status'] !== 200) {
    cms_not_found();
}

$post = $result['post'];
$url = cms_post_url($post['slug']);
$image = (string) ($post['og_image'] ?: $post['cover_image']);

cms_header([
    'title' => $post['meta_title'],
    'description' => $post['meta_description'],
    'image' => $image,
    'url' => $url,
    'type' => 'article',
    'jsonld' => array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'description' => $post['meta_description'],
        'image' => $image ?: null,
        'datePublished' => $post['published_at'],
        'dateModified' => $post['updated_at'],
        'mainEntityOfPage' => $url,
        'author' => !empty($post['author']) ? ['@type' => 'Person', 'name' => $post['author']] : null,
        'publisher' => ['@type' => 'Organization', 'name' => cms_site_name()],
    ]),
    // Extra schema the SEO team added to this post in the dashboard.
    'schema' => $post['schema'] ?? null,
]);

// The layout chosen in the panel, if this site has it; classic otherwise.
$layout = (string) ($post['layout'] ?? '');
if (!preg_match('/^[a-z0-9_-]+$/', $layout) || !is_file(__DIR__ . "/cms/layouts/{$layout}.php")) {
    $layout = 'classic';
}
include __DIR__ . "/cms/layouts/{$layout}.php";

// "Keep reading": other posts, same category first.
cms_partial('related', ['posts' => cms_related_posts($post)]);

cms_footer();
