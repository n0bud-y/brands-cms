<?php
/**
 * Service boxes, each linking to that service's page in this place.
 *
 * @var string $title
 * @var array  $services  [{name, icon, summary, path}]
 * @var bool   $soft      grey background (used under a service page)
 */
if (empty($services)) {
    return;
}
?>
<section class="cms-loc-section<?= !empty($soft) ? ' cms-loc-soft' : '' ?>">
    <div class="cms-wrap cms-wrap-xl">
        <h2 class="cms-section-title cms-center"><?= cms_e($title) ?></h2>
        <div class="cms-loc-grid">
            <?php foreach ($services as $service): ?>
                <a class="cms-loc-card" href="/<?= cms_e($service['path']) ?>">
                    <?php if (!empty($service['icon'])): ?>
                        <img class="cms-loc-card-icon" src="<?= cms_e($service['icon']) ?>" alt="" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span class="cms-loc-card-icon"><?= cms_icon('check') ?></span>
                    <?php endif ?>
                    <h3 class="cms-loc-card-title"><?= cms_e($service['name']) ?></h3>
                    <?php if ($service['summary'] !== ''): ?>
                        <p class="cms-loc-card-text"><?= cms_e($service['summary']) ?></p>
                    <?php endif ?>
                    <span class="cms-more">Learn more <?= cms_icon('arrow') ?></span>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</section>
