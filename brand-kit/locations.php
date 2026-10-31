<?php
// The main locations page at /locations (see htaccess-rules.txt): banner, an intro section (text + image),
// then every state with its write-up and its cities as picture boxes.
// All text comes from the panel (Locations → Page defaults → Main locations page, and each state).

require __DIR__ . '/cms/brand.php';

$map = cms_locations();
if ($map === null) {
    cms_unavailable();
}

$hub = (array) ($map['hub'] ?? []) + ['heading' => 'Locations we serve', 'text' => '', 'intro' => null];
$states = cms_location_menu();

cms_header([
    'title' => $hub['heading'] . ' | ' . cms_site_name(),
    'description' => $hub['text'],
    'url' => cms_url('/locations'),
]);
?>
<section class="cms-loc-hero cms-loc-hero-plain">
    <div class="cms-wrap cms-wrap-md cms-center cms-loc-hub-head">
        <nav class="cms-crumbs cms-center" aria-label="Breadcrumb">
            <a href="/">Home</a><span aria-hidden="true">/</span><span aria-current="page">Locations</span>
        </nav>
        <h1 class="cms-post-title"><?= cms_e($hub['heading']) ?></h1>
        <?php if ($hub['text'] !== ''): ?>
            <p class="cms-post-lead"><?= cms_e($hub['text']) ?></p>
        <?php endif ?>
    </div>
</section>

<?php cms_partial('location-feature', ['feature' => $hub['intro'], 'button' => false]) ?>

<?php if (!$states): ?>
    <p class="cms-empty">Locations are coming soon.</p>
<?php endif ?>
<?php foreach ($states as $i => $state): ?>
    <section class="cms-loc-section<?= $i % 2 === 0 ? ' cms-loc-soft' : '' ?>">
        <div class="cms-wrap cms-wrap-xl">
            <div class="cms-state<?= empty($state['image']) ? ' cms-state-text' : '' ?>">
                <?php if (!empty($state['image'])): ?>
                    <a class="cms-state-media" href="<?= cms_e($state['url']) ?>" tabindex="-1" aria-hidden="true">
                        <img src="<?= cms_e($state['image']) ?>" alt="" loading="lazy" decoding="async">
                    </a>
                <?php endif ?>
                <div>
                    <p class="cms-eyebrow"><?= cms_e(count($state['cities'])) ?> cities</p>
                    <h2 class="cms-section-title"><a href="<?= cms_e($state['url']) ?>"><?= cms_e($state['name']) ?></a></h2>
                    <?php if ($state['summary'] !== ''): ?>
                        <p class="cms-state-text-body"><?= nl2br(cms_e($state['summary'])) ?></p>
                    <?php endif ?>
                    <a class="cms-button" href="<?= cms_e($state['url']) ?>">Explore <?= cms_e($state['name']) ?> <?= cms_icon('arrow') ?></a>
                </div>
            </div>
            <?php cms_partial('location-cities', ['cities' => $state['cities']]) ?>
        </div>
    </section>
<?php endforeach ?>
<?php
cms_footer();
