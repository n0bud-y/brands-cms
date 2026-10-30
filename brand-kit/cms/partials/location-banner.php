<?php
/**
 * Top of every location page: breadcrumbs, heading, text, call button, and the website's lead form.
 *
 * @var array  $page
 * @var string $eyebrow
 * @var string $title
 * @var string $lead
 */
$image = (string) ($page['banner_image'] ?? '');
$phone = setting_raw('phone');
$last = count($page['breadcrumbs']) - 1;
?>
<section class="cms-loc-hero<?= $image === '' ? ' cms-loc-hero-plain' : '' ?>">
    <?php if ($image !== ''): ?>
        <img class="cms-loc-hero-img" src="<?= cms_e($image) ?>" alt="" fetchpriority="high">
    <?php endif ?>
    <div class="cms-wrap cms-wrap-xl cms-loc-hero-grid">
        <div class="cms-loc-hero-text">
            <nav class="cms-crumbs" aria-label="Breadcrumb">
                <a href="/">Home</a>
                <?php foreach ($page['breadcrumbs'] as $i => $crumb): ?>
                    <span aria-hidden="true">/</span>
                    <?php if ($i === $last): ?>
                        <span aria-current="page"><?= cms_e($crumb['name']) ?></span>
                    <?php else: ?>
                        <a href="/<?= cms_e($crumb['path']) ?>"><?= cms_e($crumb['name']) ?></a>
                    <?php endif ?>
                <?php endforeach ?>
            </nav>
            <p class="cms-eyebrow"><?= cms_e($eyebrow) ?></p>
            <h1 class="cms-post-title"><?= cms_e($title) ?></h1>
            <?php if ($lead !== ''): ?>
                <p class="cms-post-lead"><?= cms_e($lead) ?></p>
            <?php endif ?>
            <?php if ($phone !== ''): ?>
                <a class="cms-button" href="tel:<?= cms_e(cms_tel($phone)) ?>"><?= cms_icon('phone') ?> <?= setting('phone') ?></a>
            <?php endif ?>
        </div>
        <div class="cms-loc-form" id="cms-form">
            <?php cms_location_form() ?>
        </div>
    </div>
</section>
