<?php
/**
 * Text on the left, image on the right (service pages above the FAQs; the intro of /locations).
 *
 * @var array $feature  title, html (sanitized by the panel), image, alt
 * @var bool  $soft     light background
 * @var bool  $button   the "Get a free quote" button (only on pages with the lead form; default yes)
 */
if (empty($feature) || (($feature['title'] ?? '') === '' && trim(strip_tags($feature['html'] ?? '')) === '')) {
    return;
}
$image = (string) ($feature['image'] ?? '');
?>
<section class="cms-loc-section<?= !empty($soft) ? ' cms-loc-soft' : '' ?>">
    <div class="cms-wrap cms-wrap-xl cms-feature<?= $image === '' ? ' cms-feature-text' : '' ?>">
        <div class="cms-feature-body">
            <?php if (($feature['title'] ?? '') !== ''): ?>
                <h2 class="cms-section-title"><?= cms_e($feature['title']) ?></h2>
            <?php endif ?>
            <div class="cms-prose"><?= $feature['html'] ?? '' ?></div>
            <?php if ($button ?? true): ?>
                <a class="cms-button" href="#cms-form">Get a free quote <?= cms_icon('arrow') ?></a>
            <?php endif ?>
        </div>
        <?php if ($image !== ''): ?>
            <div class="cms-feature-media">
                <img src="<?= cms_e($image) ?>" alt="<?= cms_e($feature['alt'] ?? '') ?>" loading="lazy" decoding="async">
            </div>
        <?php endif ?>
    </div>
</section>
