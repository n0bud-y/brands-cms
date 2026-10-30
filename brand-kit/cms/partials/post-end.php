<?php
/**
 * After the content: share links, the writer box and a way back to the blog.
 *
 * @var array $post
 */
$url = rawurlencode(cms_post_url($post['slug']));
$title = rawurlencode($post['title']);
$share = [
    'facebook' => ['Facebook', "https://www.facebook.com/sharer/sharer.php?u={$url}"],
    'x' => ['X', "https://twitter.com/intent/tweet?url={$url}&text={$title}"],
    'linkedin' => ['LinkedIn', "https://www.linkedin.com/sharing/share-offsite/?url={$url}"],
    'whatsapp' => ['WhatsApp', "https://wa.me/?text={$title}%20{$url}"],
    'mail' => ['Email', "mailto:?subject={$title}&body={$url}"],
];
?>
<div class="cms-post-end">
    <div class="cms-share">
        <span class="cms-share-label">Share this article</span>
        <div class="cms-share-links">
            <?php foreach ($share as $icon => [$label, $href]): ?>
                <a href="<?= cms_e($href) ?>" target="_blank" rel="noopener" aria-label="Share on <?= $label ?>" title="<?= $label ?>"><?= cms_icon($icon) ?></a>
            <?php endforeach ?>
        </div>
    </div>
    <?php cms_author_box($post) ?>
    <a href="/blogs" class="cms-more cms-back"><?= cms_icon('back') ?> Back to all articles</a>
</div>
