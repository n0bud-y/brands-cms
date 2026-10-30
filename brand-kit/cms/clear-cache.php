<?php
// Called by the panel (POST + X-Cache-Secret header) whenever this brand's content changes.

require __DIR__ . '/brand.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$secret = (string) cms_config('cache_secret');
$given = (string) ($_SERVER['HTTP_X_CACHE_SECRET'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || strlen($secret) < 16 || !hash_equals($secret, $given)) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

echo json_encode(['ok' => true, 'cleared' => cms_clear_cache()]);
