<?php
/**
 * City boxes: the city's map picture (uploaded in the panel) and its name, linking to its page.
 *
 * @var array $cities  [{name, image, url}]
 */
if (empty($cities)) {
    return;
}
?>
<ul class="cms-city-grid">
    <?php foreach ($cities as $city): ?>
        <li>
            <a class="cms-city" href="<?= cms_e($city['url']) ?>">
                <span class="cms-city-media">
                    <?php if (!empty($city['image'])): ?>
                        <img src="<?= cms_e($city['image']) ?>" alt="Map of <?= cms_e($city['name']) ?>" loading="lazy" decoding="async">
                    <?php else: ?>
                        <?= cms_icon('pin') ?>
                    <?php endif ?>
                </span>
                <span class="cms-city-name"><?= cms_e($city['name']) ?> <?= cms_icon('arrow') ?></span>
            </a>
        </li>
    <?php endforeach ?>
</ul>
