<?php
/**
 * Closing band of a location page: heading, text and ways to get in touch (from the brand's Settings).
 *
 * @var array $page  contact_title, contact_text
 */
$phone = setting_raw('phone');
$email = setting_raw('email');
$whatsapp = setting_raw('whatsapp');
?>
<section class="cms-loc-section">
    <div class="cms-wrap cms-wrap-lg">
        <div class="cms-cta">
            <h2 class="cms-cta-title"><?= cms_e($page['contact_title']) ?></h2>
            <?php if ($page['contact_text'] !== ''): ?>
                <p class="cms-cta-text"><?= cms_e($page['contact_text']) ?></p>
            <?php endif ?>
            <div class="cms-loc-actions">
                <a class="cms-button" href="#cms-form">Get a free quote <?= cms_icon('arrow') ?></a>
                <?php if ($phone !== ''): ?>
                    <a class="cms-button cms-button-ghost" href="tel:<?= cms_e(cms_tel($phone)) ?>"><?= cms_icon('phone') ?> <?= setting('phone') ?></a>
                <?php endif ?>
                <?php if ($email !== ''): ?>
                    <a class="cms-button cms-button-ghost" href="mailto:<?= setting('email') ?>"><?= cms_icon('mail') ?> <?= setting('email') ?></a>
                <?php endif ?>
                <?php if ($whatsapp !== ''): ?>
                    <a class="cms-button cms-button-ghost" href="<?= cms_e(cms_whatsapp_url($whatsapp)) ?>" rel="noopener" target="_blank"><?= cms_icon('whatsapp') ?> WhatsApp</a>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>
