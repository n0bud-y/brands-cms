<?php
// The main locations page at /locations (see htaccess-rules.txt): every state with its cities.
// Heading and text come from the panel (Locations → Page defaults → Main locations page).

require __DIR__ . '/cms/brand.php';

$map = cms_locations();
if ($map === null) {
    cms_unavailable();
}

$hub = (array) ($map['hub'] ?? []) + ['heading' => 'Locations we serve', 'text' => ''];
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

<section class="cms-loc-section">
    <div class="cms-wrap cms-wrap-xl">
        <?php if (!$states): ?>
            <p class="cms-empty">Locations are coming soon.</p>
        <?php else: ?>
            <div class="cms-loc-states">
                <?php foreach ($states as $state): ?>
                    <article class="cms-loc-state">
                        <h2 class="cms-loc-card-title"><a href="<?= cms_e($state['url']) ?>"><?= cms_icon('pin') ?><?= cms_e($state['name']) ?></a></h2>
                        <?php if ($state['cities']): ?>
                            <ul>
                                <?php foreach ($state['cities'] as $city): ?>
                                    <li><a href="<?= cms_e($city['url']) ?>"><?= cms_e($city['name']) ?></a></li>
                                <?php endforeach ?>
                            </ul>
                        <?php endif ?>
                        <a class="cms-more" href="<?= cms_e($state['url']) ?>">All of <?= cms_e($state['name']) ?> <?= cms_icon('arrow') ?></a>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</section>
<?php
cms_footer();
