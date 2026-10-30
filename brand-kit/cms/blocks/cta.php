<?php
/** @var array $block  title, text, button, url */
if (($block['title'] ?? '') === '' && ($block['button'] ?? '') === '') {
    return;
}
?>
<aside class="cms-block cms-cta">
    <?php if (($block['title'] ?? '') !== ''): ?>
        <p class="cms-cta-title"><?= cms_e($block['title']) ?></p>
    <?php endif ?>
    <?php if (($block['text'] ?? '') !== ''): ?>
        <p class="cms-cta-text"><?= nl2br(cms_e($block['text'])) ?></p>
    <?php endif ?>
    <?php if (($block['button'] ?? '') !== '' && ($block['url'] ?? '') !== ''): ?>
        <a class="cms-button" href="<?= cms_e($block['url']) ?>"><?= cms_e($block['button']) ?> <?= cms_icon('arrow') ?></a>
    <?php endif ?>
</aside>
