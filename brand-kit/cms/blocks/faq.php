<?php
/**
 * @var array $block  items: [{q, a}]. One answer open at a time. Also emits FAQPage structured data for search engines.
 */
if (empty($block['items'])) {
    return;
}
// Same name = an exclusive accordion: opening one closes the others (the script below covers older browsers).
$group = 'cms-faq-' . substr(md5(json_encode($block['items'])), 0, 8);
?>
<section class="cms-block cms-faq">
    <?php foreach ($block['items'] as $i => $item): ?>
        <details name="<?= $group ?>"<?= $i === 0 ? ' open' : '' ?>>
            <summary><?= cms_e($item['q']) ?></summary>
            <p><?= cms_e($item['a'] ?? '') ?></p>
        </details>
    <?php endforeach ?>
</section>
<?php if (cms_once('faq-js')): ?>
<script>
if (!('name' in HTMLDetailsElement.prototype)) document.addEventListener('toggle', function (e) {
    var d = e.target;
    if (d.open && d.parentNode.classList.contains('cms-faq')) [].forEach.call(d.parentNode.children, function (o) { if (o !== d) o.open = false; });
}, true);
</script>
<?php endif ?>
<?= cms_json_ld([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(function ($item) {
        return ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a'] ?? '']];
    }, $block['items']),
]) ?>
