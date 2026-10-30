<?php
/**
 * Breadcrumbs, category, title, intro and the byline (writer, date, reading time). Used by every layout.
 *
 * @var array $post
 * @var bool  $center  centre-aligned (minimal layout)
 */
$center = !empty($center);
$writer = $post['author_box'] ?? null;
?>
<nav class="cms-crumbs<?= $center ? ' cms-center' : '' ?>" aria-label="Breadcrumb">
    <a href="/">Home</a><span aria-hidden="true">/</span><a href="/blogs">Blog</a>
    <?php if (!empty($post['category'])): ?>
        <span aria-hidden="true">/</span><span><?= cms_e($post['category']) ?></span>
    <?php endif ?>
</nav>
<?php if (!empty($post['category'])): ?>
    <span class="cms-pill"><?= cms_e($post['category']) ?></span>
<?php endif ?>
<h1 class="cms-post-title"><?= cms_e($post['title']) ?></h1>
<?php if (!empty($post['excerpt'])): ?>
    <p class="cms-post-lead"><?= cms_e($post['excerpt']) ?></p>
<?php endif ?>
<div class="cms-byline<?= $center ? ' cms-center' : '' ?>">
    <?php if (!empty($post['author'])): ?>
        <span class="cms-byline-who">
            <?php if (!empty($writer['photo'])): ?>
                <img src="<?= cms_e($writer['photo']) ?>" alt="" class="cms-avatar">
            <?php else: ?>
                <span class="cms-avatar cms-avatar-letter" aria-hidden="true"><?= cms_e(preg_match('/./u', $post['author'], $first) ? $first[0] : '?') ?></span>
            <?php endif ?>
            <span><small>Written by</small><b><?= cms_e($post['author']) ?></b></span>
        </span>
    <?php endif ?>
    <span><?= cms_icon('calendar') ?><time datetime="<?= cms_e($post['published_at']) ?>"><?= cms_e(cms_date($post['published_at'])) ?></time></span>
    <span><?= cms_icon('clock') ?><?= cms_reading_time($post['blocks'] ?? []) ?> min read</span>
</div>
