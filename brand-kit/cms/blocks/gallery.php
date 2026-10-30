<?php
/** @var array $block  images: [{url, alt}] */
if (empty($block['images'])) {
    return;
}
?>
<div class="cms-block cms-gallery">
    <?php foreach ($block['images'] as $image): ?>
        <a href="<?= cms_e($image['url']) ?>" target="_blank" rel="noopener">
            <img src="<?= cms_e($image['url']) ?>" alt="<?= cms_e($image['alt'] ?? '') ?>" loading="lazy" decoding="async">
        </a>
    <?php endforeach ?>
</div>
