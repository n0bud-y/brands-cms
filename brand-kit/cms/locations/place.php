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
        <div class="cms-wrap cms-wrap-xl">
            <h2 class="cms-section-title cms-center"><?= cms_e($page['cities_title']) ?></h2>
            <?php cms_partial('location-cities', ['cities' => array_map(function ($city) {
                return $city + ['url' => '/' . $city['path']];
            }, $page['cities'])]) ?>
        </div>
    </section>
<?php endif ?>
<?php
cms_partial('location-faq', ['faqs' => $page['faqs'], 'place' => $page['name']]);
cms_partial('location-contact', ['page' => $page]);
