<?php
/** @var array $block  image, alt, html (sanitized by the panel), side (left|right) */
if (($block['image'] ?? '') === '' && ($block['html'] ?? '') === '') {
    return;
}
?>
<div class="cms-block cms-split<?= ($block['side'] ?? 'left') === 'right' ? ' cms-split-flip' : '' ?>">
    <?php if (($block['image'] ?? '') !== ''): ?>
        <img src="<?= cms_e($block['image']) ?>" alt="<?= cms_e($block['alt'] ?? '') ?>" loading="lazy" decoding="async">
    <?php endif ?>
    <div class="cms-prose"><?= $block['html'] ?? '' ?></div>
</div>
