<?php
// Called by the panel's "Find pages" (POST + X-Cache-Secret header): this website's pages and what their files say.

require __DIR__ . '/brand.php';

cms_require_panel();

echo json_encode(['ok' => true, 'pages' => cms_site_pages()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
