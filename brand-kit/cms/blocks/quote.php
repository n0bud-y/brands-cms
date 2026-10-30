<?php
/** @var array $block  text, cite */
if (($block['text'] ?? '') === '') {
    return;
}
?>
<figure class="cms-block cms-quote">
    <blockquote><p><?= nl2br(cms_e($block['text'])) ?></p></blockquote>
    <?php if (($block['cite'] ?? '') !== ''): ?>
        <figcaption><?= cms_e($block['cite']) ?></figcaption>
    <?php endif ?>
</figure>
