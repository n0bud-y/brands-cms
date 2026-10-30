<?php
/**
 * 404 / 503 page. Rendered by cms_error_page().
 *
 * @var int    $status
 * @var string $title
 * @var string $message
 */
cms_header(['title' => $title, 'robots' => 'noindex']);
?>
<section class="cms-wrap cms-wrap-sm cms-error">
    <p class="cms-error-code"><?= (int) $status ?></p>
    <h1 class="cms-post-title"><?= cms_e($title) ?></h1>
    <p class="cms-post-lead"><?= cms_e($message) ?></p>
    <a class="cms-button" href="/blogs"><?= cms_icon('back') ?> Back to the blog</a>
</section>
<?php cms_footer();
