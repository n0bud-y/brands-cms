<?php
/** @var array $block  text, level (2|3), anchor */
if (($block['text'] ?? '') === '') {
    return;
}
$tag = (int) ($block['level'] ?? 2) === 3 ? 'h3' : 'h2';
?>
<<?= $tag ?> <?= $block['anchor'] ? 'id="' . cms_e($block['anchor']) . '" ' : '' ?>class="cms-<?= $tag ?>"><?= cms_e($block['text']) ?></<?= $tag ?>>
