<?php
/**
 * One post card (blog listing and "Keep reading").
 *
 * @var array $post  slug, title, excerpt, cover_image, category, author, published_at
 */
$href = '/blogs/' . cms_e($post['slug']);
?>
<article class="cms-card">
    <a href="<?= $href ?>" class="cms-card-media" tabindex="-1" aria-hidden="true">
        <?php if (!empty($post['cover_image'])): ?>
            <img src="<?= cms_e($post['cover_image']) ?>" alt="" loading="lazy" decoding="async">
        <?php endif ?>
        <?php if (!empty($post['category'])): ?>
            <span class="cms-pill cms-card-badge"><?= cms_e($post['category']) ?></span>
        <?php endif ?>
    </a>
    <div class="cms-card-body">
        <p class="cms-meta">
            <span><?= cms_icon('calendar') ?><time datetime="<?= cms_e($post['published_at']) ?>"><?= cms_e(cms_date($post['published_at'], 'M j, Y')) ?></time></span>
            <?php if (!empty($post['author'])): ?>
                <span><?= cms_icon('user') ?><?= cms_e($post['author']) ?></span>
            <?php endif ?>
        </p>
        <h2 class="cms-card-title"><a href="<?= $href ?>"><?= cms_e($post['title']) ?></a></h2>
        <?php if (!empty($post['excerpt'])): ?>
            <p class="cms-card-excerpt"><?= cms_e($post['excerpt']) ?></p>
        <?php endif ?>
        <a href="<?= $href ?>" class="cms-more">Read more <?= cms_icon('arrow') ?><span class="cms-sr">: <?= cms_e($post['title']) ?></span></a>
    </div>
</article>
