<?php
/**
 * Shown in the banner when the website has no lead form file (config 'location_form', default includes/form.php).
 */
$phone = setting_raw('phone');
$email = setting_raw('email');
?>
<div class="cms-panel cms-contact">
    <p class="cms-contact-title">Get a free quote</p>
    <p class="cms-contact-text">Tell us about your project. We usually reply within one business day.</p>
    <?php if ($phone !== ''): ?>
        <a class="cms-button" href="tel:<?= cms_e(cms_tel($phone)) ?>"><?= cms_icon('phone') ?> <?= setting('phone') ?></a>
    <?php endif ?>
    <?php if ($email !== ''): ?>
        <a class="cms-button cms-button-ghost" href="mailto:<?= setting('email') ?>"><?= cms_icon('mail') ?> <?= setting('email') ?></a>
    <?php endif ?>
</div>
