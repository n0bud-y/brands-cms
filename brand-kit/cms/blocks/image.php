<?php
/** @var array $block  url, alt, caption */
if (($block['url'] ?? '') === '') {
    return;
}
?>
<figure class="cms-block cms-figure">
    <img src="<?= cms_e($block['url']) ?>" alt="<?= cms_e($block['alt'] ?? '') ?>" loading="lazy" decoding="async">
    <?php if (($block['caption'] ?? '') !== ''): ?>
        <figcaption><?= cms_e($block['caption']) ?></figcaption>
    <?php endif ?>
</figure>
