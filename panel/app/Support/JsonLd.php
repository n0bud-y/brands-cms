<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Parses schema markup pasted by the SEO team. Accepts raw JSON or one or more
 * <script type="application/ld+json"> blocks. Only the decoded data is stored and
 * brand sites re-encode it, so nothing pasted here can ever run as JavaScript.
 */
final class JsonLd
{
    /** @return array<mixed>|null  null when the input is empty */
    public static function parse(?string $input): ?array
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        if (stripos($input, '<script') !== false) {
            preg_match_all('#<script\b[^>]*>(.*?)</script>#is', $input, $matches);
            $chunks = $matches[1];
            if ($chunks === []) {
                throw new InvalidArgumentException('Could not find a complete <script>…</script> block.');
            }
        } else {
            $chunks = [$input];
        }

        $items = [];
        foreach ($chunks as $i => $chunk) {
            $data = json_decode(trim($chunk), true);
            if (! is_array($data)) {
                $where = count($chunks) > 1 ? ' (script #'.($i + 1).')' : '';
                throw new InvalidArgumentException("Schema must be valid JSON{$where}: ".json_last_error_msg().'.');
            }
            $items[] = $data;
        }

        return count($items) === 1 ? $items[0] : $items;
    }

    /** Validation rule closure for a schema textarea. */
    public static function rule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            try {
                self::parse(is_string($value) ? $value : null);
            } catch (InvalidArgumentException $e) {
                $fail($e->getMessage());
            }
        };
    }

    public static function pretty(?array $data): string
    {
        return $data === null ? '' : (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
