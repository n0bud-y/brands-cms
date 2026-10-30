<?php
/** @var array $block  html (sanitized by the panel) */
if (($block['html'] ?? '') === '') {
    return;
}
?>
<div class="cms-prose"><?= $block['html'] ?></div>
