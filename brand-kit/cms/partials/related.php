<?php
/**
 * "Keep reading": other posts under the article, same category first.
 *
 * @var array $posts
 */
if (!$posts) {
    return;
}
?>
<section class="cms-related" aria-labelledby="cms-related-title">
    <div class="cms-wrap cms-wrap-xl">
        <p class="cms-eyebrow">More from the blog</p>
        <h2 id="cms-related-title" class="cms-section-title">Keep reading</h2>
        <div class="cms-grid">
            <?php foreach ($posts as $item): ?>
                <?php cms_partial('card', ['post' => $item]) ?>
            <?php endforeach ?>
        </div>
    </div>
</section>
