<?php /** Classic: title and byline, a wide cover image, then the content. @var array $post */ ?>
<article class="cms-post cms-post-classic">
    <header class="cms-wrap cms-wrap-md cms-post-head">
        <?php cms_partial('post-header', ['post' => $post]) ?>
    </header>

    <?php if ($post['cover_image']): ?>
        <figure class="cms-wrap cms-wrap-lg cms-cover">
            <img src="<?= cms_e($post['cover_image']) ?>" alt="<?= cms_e($post['title']) ?>" fetchpriority="high">
        </figure>
    <?php endif ?>

    <div class="cms-wrap cms-wrap-sm cms-post-body">
        <?php cms_render_blocks($post['blocks']) ?>
        <?php cms_post_end($post) ?>
    </div>
</article>
