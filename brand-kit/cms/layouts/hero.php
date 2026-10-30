<?php /** Hero: full-width cover with the title over it, then the content. @var array $post */ ?>
<article class="cms-post cms-post-hero">
    <header class="cms-hero<?= $post['cover_image'] ? '' : ' cms-hero-plain' ?>">
        <?php if ($post['cover_image']): ?>
            <img src="<?= cms_e($post['cover_image']) ?>" alt="" class="cms-hero-img" fetchpriority="high">
        <?php endif ?>
        <div class="cms-wrap cms-wrap-md cms-hero-content">
            <?php cms_partial('post-header', ['post' => $post]) ?>
        </div>
    </header>

    <div class="cms-wrap cms-wrap-sm cms-post-body">
        <?php cms_render_blocks($post['blocks']) ?>
        <?php cms_post_end($post) ?>
    </div>
</article>
