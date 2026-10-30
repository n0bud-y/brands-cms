<?php
/** Sidebar: content left; "On this page" and a contact card right. @var array $post */

$toc = [];
foreach (cms_heading_anchors($post['blocks']) as $i => $anchor) {
    $toc[] = ['anchor' => $anchor, 'text' => $post['blocks'][$i]['text'], 'sub' => (int) $post['blocks'][$i]['level'] === 3];
}
$phone = setting_raw('phone');
$email = setting_raw('email');
$whatsapp = setting_raw('whatsapp');
?>
<article class="cms-post cms-post-sidebar">
    <header class="cms-wrap cms-wrap-xl cms-post-head">
        <div class="cms-head-narrow"><?php cms_partial('post-header', ['post' => $post]) ?></div>
    </header>

    <div class="cms-wrap cms-wrap-xl cms-with-aside">
        <div class="cms-post-body">
            <?php if ($post['cover_image']): ?>
                <figure class="cms-cover cms-cover-inline">
                    <img src="<?= cms_e($post['cover_image']) ?>" alt="<?= cms_e($post['title']) ?>" fetchpriority="high">
                </figure>
            <?php endif ?>
            <?php cms_render_blocks($post['blocks']) ?>
            <?php cms_post_end($post) ?>
        </div>

        <aside class="cms-aside">
            <div class="cms-sticky">
                <?php if ($toc): ?>
                    <nav class="cms-panel cms-toc" aria-label="On this page">
                        <p class="cms-eyebrow">On this page</p>
                        <ul>
                            <?php foreach ($toc as $item): ?>
                                <li<?= $item['sub'] ? ' class="cms-toc-sub"' : '' ?>><a href="#<?= cms_e($item['anchor']) ?>"><?= cms_e($item['text']) ?></a></li>
                            <?php endforeach ?>
                        </ul>
                    </nav>
                <?php endif ?>

                <?php if ($phone !== '' || $email !== '' || $whatsapp !== ''): ?>
                    <div class="cms-panel cms-contact">
                        <p class="cms-contact-title">Have a question?</p>
                        <p class="cms-contact-text">Our team is happy to help with your book.</p>
                        <?php if ($phone !== ''): ?>
                            <a class="cms-button" href="tel:<?= cms_e(cms_tel($phone)) ?>"><?= cms_icon('phone') ?> <?= cms_e($phone) ?></a>
                        <?php endif ?>
                        <?php if ($whatsapp !== ''): ?>
                            <a class="cms-button cms-button-ghost" href="<?= cms_e(cms_whatsapp_url($whatsapp)) ?>" target="_blank" rel="noopener"><?= cms_icon('whatsapp') ?> WhatsApp us</a>
                        <?php endif ?>
                        <?php if ($email !== ''): ?>
                            <a class="cms-button cms-button-ghost" href="mailto:<?= cms_e($email) ?>"><?= cms_icon('mail') ?> Email us</a>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            </div>
        </aside>
    </div>
</article>
