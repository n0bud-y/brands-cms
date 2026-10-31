<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Normalizes the post "blocks" JSON: known types only, known fields only,
 * plain text trimmed and capped, rich text sanitized, URLs scheme-checked.
 * Brand sites can then render blocks without re-validating them.
 */
final class Blocks
{
    public const TYPES = ['heading', 'paragraph', 'image', 'image_text', 'quote', 'cta', 'two_column', 'gallery', 'faq'];

    private const MAX_BLOCKS = 200;

    private const MAX_ITEMS = 50;

    public static function normalize(mixed $blocks): array
    {
        if (! is_array($blocks)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($blocks), 0, self::MAX_BLOCKS) as $block) {
            if (is_array($block) && in_array($block['type'] ?? null, self::TYPES, true)) {
                $out[] = ['id' => self::id($block['id'] ?? null), 'type' => $block['type']] + self::fields($block);
            }
        }

        return $out;
    }

    private static function fields(array $b): array
    {
        $text = fn (string $key, int $max = 2000) => self::text($b[$key] ?? null, $max);
        $html = fn (string $key) => HtmlSanitizer::clean($b[$key] ?? null);
        $url = fn (string $key) => HtmlSanitizer::safeUrl($b[$key] ?? null);

        return match ($b['type']) {
            'heading' => ['text' => $text('text', 300), 'level' => (int) ($b['level'] ?? 2) === 3 ? 3 : 2],
            'paragraph' => ['html' => $html('html')],
            'image' => ['url' => $url('url'), 'alt' => $text('alt', 300), 'caption' => $text('caption', 500)],
            'image_text' => [
                'image' => $url('image'),
                'alt' => $text('alt', 300),
                'html' => $html('html'),
                'side' => ($b['side'] ?? '') === 'right' ? 'right' : 'left',
            ],
            'quote' => ['text' => $text('text'), 'cite' => $text('cite', 300)],
            'cta' => [
                'title' => $text('title', 300),
                'text' => $text('text'),
                'button' => $text('button', 100),
                'url' => HtmlSanitizer::safeUrl($b['url'] ?? null, true),
            ],
            'two_column' => ['left' => $html('left'), 'right' => $html('right')],
            'gallery' => ['images' => self::items($b['images'] ?? null, 'url', fn (array $i) => [
                'url' => HtmlSanitizer::safeUrl($i['url'] ?? null),
                'alt' => self::text($i['alt'] ?? null, 300),
            ])],
            'faq' => ['items' => self::faqs($b['items'] ?? null)],
        };
    }

    /** A service page's "text left, image right" section: {title, html, image, alt}, or null when it's empty. */
    public static function feature(mixed $f): ?array
    {
        if (! is_array($f)) {
            return null;
        }
        $out = [
            'title' => self::text($f['title'] ?? null, 300),
            'html' => HtmlSanitizer::clean($f['html'] ?? null),
            'image' => HtmlSanitizer::safeUrl($f['image'] ?? null),
            'alt' => self::text($f['alt'] ?? null, 300),
        ];

        return $out['title'] === '' && $out['image'] === '' && trim(strip_tags($out['html'])) === '' ? null : $out;
    }

    /** Questions and answers ([{q, a}]), as in the FAQ block; also used by location pages. */
    public static function faqs(mixed $items): array
    {
        return self::items($items, 'q', fn (array $i) => [
            'q' => self::text($i['q'] ?? null, 500),
            'a' => self::text($i['a'] ?? null, 5000),
        ]);
    }

    /** Maps list items and drops those whose $required field ended up empty. */
    private static function items(mixed $items, string $required, callable $map): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($items), 0, self::MAX_ITEMS) as $item) {
            if (is_array($item) && ($item = $map($item))[$required] !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }

    private static function text(mixed $value, int $max): string
    {
        return is_scalar($value) ? mb_substr(trim((string) $value), 0, $max) : '';
    }

    private static function id(mixed $id): string
    {
        return is_string($id) && preg_match('/^[a-zA-Z0-9]{1,16}$/', $id) ? $id : Str::lower(Str::random(8));
    }
}
