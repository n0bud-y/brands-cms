<?php
/**
 * FAQs of a location page: heading and a call to action on the left, the questions on the right.
 *
 * @var array  $faqs   [{q, a}]
 * @var string $place  the state or city name
 */
if (empty($faqs)) {
    return;
}
?>
<section class="cms-loc-section">
    <div class="cms-wrap cms-wrap-xl cms-faq-split">
        <div class="cms-faq-intro">
            <p class="cms-eyebrow">FAQs</p>
            <h2 class="cms-section-title">Frequently asked questions</h2>
            <p class="cms-faq-text">Still have a question about working with us<?= !empty($place) ? ' in ' . cms_e($place) : '' ?>? Our team is happy to help.</p>
            <a class="cms-button" href="#cms-form">Ask us anything <?= cms_icon('arrow') ?></a>
        </div>
        <?php cms_include_block(BRAND_CMS . '/blocks/faq.php', ['items' => $faqs]) ?>
    </div>
</section>
