<?php

namespace App\Support;

use App\Enums\PostStatus;
use App\Models\Site;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Loads a brand's content from one JSON file (php artisan cms:import {brand} {file}), e.g. when moving an
 * existing website's static blogs into the panel. Every part is optional; running it again updates by
 * slug/path/name instead of duplicating. Values go through the same cleaning as the dashboard.
 *
 *   settings           {key: value}                    merged into Settings
 *   location_defaults  {heading, faqs…}                merged into Locations → Page defaults
 *   authors            [{name, role, photo, bio, is_default}]
 *   posts              [{title, slug, author, category, cover_image, excerpt, blocks, meta_title, meta_description, published_at}]
 *   services           [{name, slug, icon, summary, title, blocks, faqs, meta_title, meta_description}]
 *   locations          [{name, slug, …, cities: [{name, slug, …}]}]
 *   pages              [{path, found_title, found_description, meta_title, meta_description, og_image, noindex}]
 */
final class BrandImport
{
    /** @return array<string, int> how many of each were saved */
    public static function run(Site $site, array $data): array
    {
        return DB::transaction(function () use ($site, $data) {
            $count = [];

            foreach ((array) ($data['settings'] ?? []) as $key => $value) {
                $site->settings()->updateOrCreate(['key' => (string) $key], ['value' => (string) $value]);
                $count['settings'] = ($count['settings'] ?? 0) + 1;
            }

            if (isset($data['location_defaults'])) {
                $defaults = array_intersect_key((array) $data['location_defaults'], LocationPages::DEFAULTS);
                if (isset($defaults['faqs'])) {
                    $defaults['faqs'] = Blocks::faqs($defaults['faqs']);
                }
                $site->forceFill(['location_defaults' => $defaults + (array) $site->location_defaults])->save();
                $count['location defaults'] = 1;
            }

            foreach ((array) ($data['authors'] ?? []) as $a) {
                $author = $site->authors()->updateOrCreate(['name' => $a['name']], [
                    'role' => $a['role'] ?? null,
                    'photo' => HtmlSanitizer::safeUrl($a['photo'] ?? null) ?: null,
                    'bio' => $a['bio'] ?? null,
                ]);
                if (! empty($a['is_default'])) {
                    $site->authors()->whereKeyNot($author->id)->update(['is_default' => false]);
                    $author->forceFill(['is_default' => true])->save();
                }
                $count['authors'] = ($count['authors'] ?? 0) + 1;
            }

            $authors = $site->authors()->pluck('id', 'name');
            foreach ((array) ($data['posts'] ?? []) as $p) {
                $site->posts()->updateOrCreate(['slug' => $p['slug']], [
                    'title' => $p['title'],
                    'layout' => $p['layout'] ?? 'classic',
                    'author_id' => $authors[$p['author'] ?? ''] ?? null,
                    'category' => $p['category'] ?? null,
                    'cover_image' => HtmlSanitizer::safeUrl($p['cover_image'] ?? null) ?: null,
                    'excerpt' => $p['excerpt'] ?? null,
                    'blocks' => Blocks::normalize($p['blocks'] ?? []),
                    'meta_title' => $p['meta_title'] ?? null,
                    'meta_description' => $p['meta_description'] ?? null,
                    'status' => PostStatus::Published,
                    'published_at' => Carbon::parse($p['published_at'] ?? 'now')->utc(),
                ]);
                $count['posts'] = ($count['posts'] ?? 0) + 1;
            }

            foreach (array_values((array) ($data['services'] ?? [])) as $i => $s) {
                $site->services()->updateOrCreate(['slug' => $s['slug']], [
                    'name' => $s['name'],
                    'icon' => HtmlSanitizer::safeUrl($s['icon'] ?? null) ?: null,
                    'summary' => $s['summary'] ?? null,
                    'title' => $s['title'] ?? null,
                    'blocks' => Blocks::normalize($s['blocks'] ?? []),
                    'feature' => Blocks::feature($s['feature'] ?? null),
                    'faqs' => Blocks::faqs($s['faqs'] ?? []),
                    'meta_title' => $s['meta_title'] ?? null,
                    'meta_description' => $s['meta_description'] ?? null,
                    'sort' => $i + 1,
                ]);
                $count['services'] = ($count['services'] ?? 0) + 1;
            }

            foreach (array_values((array) ($data['locations'] ?? [])) as $i => $state) {
                $parent = $site->locations()->updateOrCreate(['slug' => $state['slug'], 'parent_id' => null], self::place($state, $i));
                foreach (array_values((array) ($state['cities'] ?? [])) as $j => $city) {
                    $site->locations()->updateOrCreate(['slug' => $city['slug'], 'parent_id' => $parent->id], self::place($city, $j));
                }
                $count['locations'] = ($count['locations'] ?? 0) + 1 + count((array) ($state['cities'] ?? []));
            }

            foreach ((array) ($data['pages'] ?? []) as $pg) {
                $site->pages()->updateOrCreate(['path' => $pg['path']], array_intersect_key($pg, array_flip([
                    'found_title', 'found_description', 'meta_title', 'meta_description', 'og_image', 'noindex',
                ])));
                $count['pages'] = ($count['pages'] ?? 0) + 1;
            }

            return $count;
        });
    }

    private static function place(array $p, int $sort): array
    {
        return [
            'name' => $p['name'],
            'heading' => $p['heading'] ?? null,
            'subheading' => $p['subheading'] ?? null,
            'banner_image' => HtmlSanitizer::safeUrl($p['banner_image'] ?? null) ?: null,
            'image' => HtmlSanitizer::safeUrl($p['image'] ?? null) ?: null,
            'summary' => $p['summary'] ?? null,
            'blocks' => Blocks::normalize($p['blocks'] ?? []),
            'faqs' => isset($p['faqs']) ? Blocks::faqs($p['faqs']) : null,
            'sort' => $sort + 1,
        ];
    }
}
