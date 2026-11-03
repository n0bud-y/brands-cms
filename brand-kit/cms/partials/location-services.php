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
        <p class="cms-eyebrow cms-center">What we do</p>
        <h2 class="cms-section-title cms-center"><?= cms_e($title) ?></h2>
        <div class="cms-loc-grid">
            <?php foreach ($services as $i => $service): ?>
                <a class="cms-loc-card" href="/<?= cms_e($service['path']) ?>">
                    <span class="cms-loc-card-num" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
                    <span class="cms-loc-card-icon">
                        <?php if (!empty($service['icon'])): ?>
                            <img src="<?= cms_e($service['icon']) ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <?= cms_service_icon($service['name']) ?>
                        <?php endif ?>
                    </span>
                    <h3 class="cms-loc-card-title"><?= cms_e($service['name']) ?></h3>
                    <?php if ($service['summary'] !== ''): ?>
                        <p class="cms-loc-card-text"><?= cms_e($service['summary']) ?></p>
                    <?php endif ?>
                    <span class="cms-loc-card-more">Learn more <?= cms_icon('arrow') ?></span>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</section>
