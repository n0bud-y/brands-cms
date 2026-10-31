<?php
/**
 * A service page in one state or city: banner with the lead form; the service text beside a sticky card
 * (other services here + call us); the text + image section; FAQs; contact.
 *
 * @var array $page  see the panel's /locations/{key} API (type service)
 */
cms_partial('location-banner', [
    'page' => $page,
    'eyebrow' => $page['name'] . ' · ' . $page['location']['name'],
    'title' => $page['title'],
    'lead' => $page['summary'],
]);
$phone = setting_raw('phone');
?>
<section class="cms-loc-section">
    <div class="cms-wrap cms-wrap-xl cms-svc">
        <div class="cms-post-body cms-svc-main">
            <?php cms_render_blocks($page['blocks']) ?>
        </div>
        <aside class="cms-svc-aside">
            <?php if (!empty($page['services'])): ?>
                <div class="cms-svc-card">
                    <p class="cms-svc-card-title">More services in <?= cms_e($page['location']['name']) ?></p>
                    <ul class="cms-svc-links">
                        <?php foreach ($page['services'] as $service): ?>
                            <li><a href="/<?= cms_e($service['path']) ?>"><?= cms_e($service['name']) ?> <?= cms_icon('arrow') ?></a></li>
                        <?php endforeach ?>
                        <li><a href="/<?= cms_e($page['location']['path']) ?>">All of <?= cms_e($page['location']['name']) ?> <?= cms_icon('arrow') ?></a></li>
                    </ul>
                </div>
            <?php endif ?>
            <div class="cms-svc-card cms-svc-cta">
                <p class="cms-svc-card-title"><?= cms_e($page['contact_title']) ?></p>
                <p><?= cms_e($page['contact_text']) ?></p>
                <a class="cms-button" href="#cms-form">Get a free quote</a>
                <?php if ($phone !== ''): ?>
                    <a class="cms-button cms-button-ghost" href="tel:<?= cms_e(cms_tel($phone)) ?>"><?= cms_icon('phone') ?> <?= setting('phone') ?></a>
                <?php endif ?>
            </div>
        </aside>
    </div>
</section>
<?php
cms_partial('location-feature', ['feature' => $page['feature'] ?? null, 'soft' => true]);
cms_partial('location-faq', ['faqs' => $page['faqs'], 'place' => $page['location']['name']]);
cms_partial('location-contact', ['page' => $page]);
