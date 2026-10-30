<?php
/**
 * A state or city page: banner with the lead form, service boxes, extra content, cities, FAQs, contact.
 *
 * @var array $page  see the panel's /locations/{key} API (type state|city)
 */
cms_partial('location-banner', [
    'page' => $page,
    'eyebrow' => $page['type'] === 'city' ? $page['name'] . ', ' . $page['breadcrumbs'][0]['name'] : $page['name'],
    'title' => $page['heading'],
    'lead' => $page['subheading'],
]);
cms_partial('location-services', ['title' => $page['services_title'], 'services' => $page['services']]);
?>
<?php if (!empty($page['blocks'])): ?>
    <section class="cms-loc-section">
        <div class="cms-wrap cms-wrap-md cms-post-body">
            <?php cms_render_blocks($page['blocks']) ?>
        </div>
    </section>
<?php endif ?>

<?php if (!empty($page['cities'])): ?>
    <section class="cms-loc-section cms-loc-soft">
        <div class="cms-wrap cms-wrap-xl cms-center">
            <h2 class="cms-section-title"><?= cms_e($page['cities_title']) ?></h2>
            <ul class="cms-loc-cities">
                <?php foreach ($page['cities'] as $city): ?>
                    <li><a href="/<?= cms_e($city['path']) ?>"><?= cms_icon('pin') ?><?= cms_e($city['name']) ?></a></li>
                <?php endforeach ?>
            </ul>
        </div>
    </section>
<?php endif ?>
<?php
cms_partial('location-faq', ['faqs' => $page['faqs']]);
cms_partial('location-contact', ['page' => $page]);
