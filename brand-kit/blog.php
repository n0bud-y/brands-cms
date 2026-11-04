<?php
// Blog listing at /blogs (see htaccess-rules.txt).

require __DIR__ . '/cms/brand.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = cms_posts($page);
if ($result === null) {
    cms_unavailable();
}

$posts = $result['data'];
$lastPage = (int) $result['meta']['last_page'];
// No posts yet: the blog doesn't exist on this website (its menu link is hidden too, see cms_has_posts()).
if (!$posts) {
    cms_site_not_found();
}
if ($page > $lastPage) {
    cms_not_found();
}

$title = setting_raw('blog_title', 'Blog');
$pageUrl = function ($n) {
    return cms_url('/blogs' . ($n > 1 ? '?page=' . $n : ''));
};

cms_header([
    'title' => $page > 1 ? "{$title} – Page {$page}" : $title,
    'description' => setting_raw('blog_description'),
    'url' => $pageUrl($page),
    'prev' => $page > 1 ? $pageUrl($page - 1) : null,
    'next' => $page < $lastPage ? $pageUrl($page + 1) : null,
]);
?>
<section class="cms-list-head">
    <div class="cms-wrap cms-wrap-md cms-center">
        <p class="cms-eyebrow">Our blog</p>
        <h1 class="cms-post-title"><?= cms_e($title) ?></h1>
        <?php if (setting_raw('blog_description') !== ''): ?>
            <p class="cms-post-lead"><?= setting('blog_description') ?></p>
        <?php endif ?>
    </div>
</section>

<section class="cms-wrap cms-wrap-xl cms-list">
    <?php if (!$posts): ?>
        <p class="cms-empty">No articles yet. Check back soon.</p>
    <?php else: ?>
        <div class="cms-grid">
            <?php foreach ($posts as $post): ?>
                <?php cms_partial('card', ['post' => $post]) ?>
            <?php endforeach ?>
        </div>

        <?php if ($lastPage > 1): ?>
            <nav class="cms-pager" aria-label="Blog pages">
                <?php if ($page > 1): ?>
                    <a href="<?= $page > 2 ? '/blogs?page=' . ($page - 1) : '/blogs' ?>" rel="prev" aria-label="Previous page"><?= cms_icon('back') ?></a>
                <?php endif ?>
                <?php for ($n = 1; $n <= $lastPage; $n++): ?>
                    <a href="<?= $n > 1 ? '/blogs?page=' . $n : '/blogs' ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= $n ?></a>
                <?php endfor ?>
                <?php if ($page < $lastPage): ?>
                    <a href="/blogs?page=<?= $page + 1 ?>" rel="next" aria-label="Next page"><?= cms_icon('arrow') ?></a>
                <?php endif ?>
            </nav>
        <?php endif ?>
    <?php endif ?>
</section>
<?php cms_footer();
