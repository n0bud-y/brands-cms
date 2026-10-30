<?php
/** @var array $block  items: [{q, a}]. Also emits FAQPage structured data for search engines. */
if (empty($block['items'])) {
    return;
}
?>
<section class="cms-block cms-faq">
    <?php foreach ($block['items'] as $i => $item): ?>
        <details<?= $i === 0 ? ' open' : '' ?>>
            <summary><?= cms_e($item['q']) ?></summary>
            <p><?= cms_e($item['a'] ?? '') ?></p>
        </details>
    <?php endforeach ?>
</section>
<?= cms_json_ld([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(function ($item) {
        return ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a'] ?? '']];
    }, $block['items']),
]) ?>
