<?php
/** @var array $block  left, right (html, sanitized by the panel) */
?>
<div class="cms-block cms-columns">
    <div class="cms-prose"><?= $block['left'] ?? '' ?></div>
    <div class="cms-prose"><?= $block['right'] ?? '' ?></div>
</div>
