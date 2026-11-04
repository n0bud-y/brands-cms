<?php
/**
 * Blog page footer = the website's own footer:
 *
 *     include 'includes/footer.php';   (path set by 'site_footer' in cms/config.php)
 *
 * If the website has no such file, the simple footer further down is used instead
 * (every value in it comes from the brand's Settings page in the dashboard).
 */
if ($siteFooter = cms_site_file('site_footer')) {
    echo '</' . ($GLOBALS['cms_wrap'] ?? 'main') . ">\n" . cms_render_site_file($siteFooter);

    return;
}

$socials = array_filter([
    'Facebook' => setting_raw('facebook'),
    'Instagram' => setting_raw('instagram'),
    'LinkedIn' => setting_raw('linkedin'),
    'X' => setting_raw('twitter'),
    'YouTube' => setting_raw('youtube'),
    'TikTok' => setting_raw('tiktok'),
]);
?>
</main>
<footer class="cms-site-footer">
    <div class="cms-wrap cms-wrap-xl">
        <div>
            <p class="cms-site-logo"><?= cms_e(cms_site_name()) ?></p>
<?php if (setting_raw('address') !== ''): ?>
            <p><?= nl2br(setting('address')) ?></p>
<?php endif ?>
<?php if (setting_raw('email') !== ''): ?>
            <a href="mailto:<?= setting('email') ?>"><?= setting('email') ?></a>
<?php endif ?>
<?php if (setting_raw('phone') !== ''): ?>
            <a href="tel:<?= cms_e(cms_tel(setting_raw('phone'))) ?>"><?= setting('phone') ?></a>
<?php endif ?>
<?php if (setting_raw('whatsapp') !== ''): ?>
            <a href="<?= cms_e(cms_whatsapp_url(setting_raw('whatsapp'))) ?>" rel="noopener" target="_blank">WhatsApp</a>
<?php endif ?>
        </div>
<?php if ($socials): ?>
        <div class="cms-site-socials">
<?php foreach ($socials as $label => $url): ?>
            <a href="<?= cms_e($url) ?>" rel="noopener" target="_blank"><?= cms_e($label) ?></a>
<?php endforeach ?>
        </div>
<?php endif ?>
    </div>
<?php if (setting_raw('footer_text') !== ''): ?>
    <p class="cms-wrap cms-wrap-xl cms-site-copy"><?= setting('footer_text') ?></p>
<?php endif ?>
</footer>
<?php cms_footer_code() ?>
</body>
</html>
