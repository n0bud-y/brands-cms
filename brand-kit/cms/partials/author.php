<?php
/**
 * Writer box under each post. The profile is edited once in the panel (Authors) and shows on every
 * post by that writer; each post can hide it. Rendered by cms_author_box().
 *
 * @var array $author  name, role, photo, bio
 */
?>
<aside class="cms-author" aria-label="About the author">
    <?php if (!empty($author['photo'])): ?>
        <img src="<?= cms_e($author['photo']) ?>" alt="<?= cms_e($author['name']) ?>" class="cms-author-photo" loading="lazy" decoding="async">
    <?php else: ?>
        <span class="cms-author-photo cms-avatar-letter" aria-hidden="true"><?= cms_e(preg_match('/./u', $author['name'], $first) ? $first[0] : '?') ?></span>
    <?php endif ?>
    <div class="cms-author-text">
        <p class="cms-eyebrow">Written by</p>
        <p class="cms-author-name"><?= cms_e($author['name']) ?></p>
        <?php if (!empty($author['role'])): ?>
            <p class="cms-author-role"><?= cms_e($author['role']) ?></p>
        <?php endif ?>
        <?php if (!empty($author['bio'])): ?>
            <p class="cms-author-bio"><?= nl2br(cms_e($author['bio'])) ?></p>
        <?php endif ?>
    </div>
</aside>
