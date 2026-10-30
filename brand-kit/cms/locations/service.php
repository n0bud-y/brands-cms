<?php
/**
 * A service page in one state or city: banner with the lead form, the service content, FAQs,
 * the other services in this place, contact.
 *
 * @var array $page  see the panel's /locations/{key} API (type service)
 */
cms_partial('location-banner', [
    'page' => $page,
    'eyebrow' => $page['name'] . ' · ' . $page['location']['name'],
    'title' => $page['title'],
    'lead' => $page['summary'],
]);
?>
<?php if (!empty($page['blocks'])): ?>
    <section class="cms-loc-section">
        <div class="cms-wrap cms-wrap-md cms-post-body">
            <?php cms_render_blocks($page['blocks']) ?>
        </div>
    </section>
<?php endif ?>
<?php
cms_partial('location-faq', ['faqs' => $page['faqs']]);
cms_partial('location-services', ['title' => $page['services_title'], 'services' => $page['services'], 'soft' => true]);
cms_partial('location-contact', ['page' => $page]);
