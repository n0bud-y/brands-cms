<?php
/**
 * FAQs of a location page, with FAQPage structured data (same markup as the FAQ block).
 *
 * @var array $faqs  [{q, a}]
 */
if (empty($faqs)) {
    return;
}
?>
<section class="cms-loc-section">
    <div class="cms-wrap cms-wrap-md">
        <h2 class="cms-section-title cms-center">Frequently asked questions</h2>
        <?php cms_include_block(BRAND_CMS . '/blocks/faq.php', ['items' => $faqs]) ?>
    </div>
</section>
