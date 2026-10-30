<?php /** Minimal: centred, text only, no cover image. @var array $post */ ?>
<article class="cms-post cms-post-minimal">
    <header class="cms-wrap cms-wrap-sm cms-post-head cms-center">
        <?php cms_partial('post-header', ['post' => $post, 'center' => true]) ?>
    </header>

    <div class="cms-wrap cms-wrap-sm cms-post-body">
        <?php cms_render_blocks($post['blocks']) ?>
        <?php cms_post_end($post) ?>
    </div>
</article>
