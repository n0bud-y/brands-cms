<?php
// Called by the panel (POST + X-Cache-Secret header) whenever this brand's content changes.

require __DIR__ . '/brand.php';

cms_require_panel();

echo json_encode(['ok' => true, 'cleared' => cms_clear_cache()]);
