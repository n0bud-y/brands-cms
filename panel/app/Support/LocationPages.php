<?php

namespace App\Support;

use App\Models\Location;
use App\Models\LocationPage;
use App\Models\Service;
use App\Models\Site;

/**
 * Builds what a brand site shows on a location page, with every placeholder filled in:
 * {location} (this state or city), {state}, {city}, {service} and {brand}.
 */
final class LocationPages
{
    public const PLACEHOLDERS = ['{location}', '{state}', '{city}', '{service}', '{brand}'];

    /** The brand-wide texts every state and city page starts from (Locations → Page defaults). */
    public const DEFAULTS = [
        'heading' => '{brand} in {location}',
        'subheading' => 'Tell us about your project and get a free consultation today.',
        'banner_image' => '',
        'services_title' => 'Our services in {location}',
        'cities_title' => 'Cities we serve in {state}',
        'faqs' => [],
        'contact_title' => 'Ready to get started in {location}?',
        'contact_text' => 'Talk to our team today. We reply fast.',
        'meta_title' => '{brand} in {location}',
        'meta_description' => '',
        // The main /locations page that lists every state and city.
        'hub_heading' => 'Locations we serve',
        'hub_text' => '{brand} works with authors across the country. Pick your state or city to see our services near you.',
    ];

    /** What a service page is called when the service doesn't set its own title. */
    public const SERVICE_TITLE = '{service} in {location}';

    public const SERVICE_META_TITLE = '{service} in {location} | {brand}';

    public static function defaults(Site $site): array
    {
        $saved = array_filter((array) $site->location_defaults, fn ($v) => $v !== null && $v !== '');

        return array_intersect_key($saved, self::DEFAULTS) + self::DEFAULTS;
    }

    /** The page behind a key ("l5" or "l5s3"), or null when it isn't live. */
    public static function page(LocationMap $map, string $key): ?array
    {
        if (! isset($map->keys[$key]) || ! preg_match('/^l(\d+)(?:s(\d+))?$/', $key, $m)) {
            return null;
        }

        $place = Location::find((int) $m[1]);
        $state = $place->parent_id ? $map->states[$place->parent_id] : $place;
        $service = isset($m[2]) ? Service::find((int) $m[2]) : null;
        $vars = [
            '{location}' => $place->name,
            '{state}' => $state->name,
            '{city}' => $place->parent_id ? $place->name : '',
            '{service}' => $service?->name ?? '',
            '{brand}' => $map->site->name,
        ];

        $page = $service ? self::servicePage($map, $place, $service, $vars) : self::placePage($map, $place, $vars);

        $crumbs = [['name' => $state->name, 'path' => $map->keys["l{$state->id}"]]];
        if ($place->parent_id) {
            $crumbs[] = ['name' => $place->name, 'path' => $map->keys["l{$place->id}"]];
        }
        if ($service) {
            $crumbs[] = ['name' => $service->name, 'path' => $map->keys[$key]];
        }

        return ['key' => $key, 'path' => $map->keys[$key], 'breadcrumbs' => $crumbs] + self::fill($page, $vars);
    }

    private static function placePage(LocationMap $map, Location $place, array $vars): array
    {
        $d = self::defaults($map->site);
        $cities = $place->parent_id
            ? $map->cities->where('parent_id', $place->parent_id)->except($place->id)
            : $map->cities->where('parent_id', $place->id);

        return [
            'type' => $place->parent_id ? 'city' : 'state',
            'name' => $place->name,
            'heading' => $place->heading ?: $d['heading'],
            'subheading' => $place->subheading ?: $d['subheading'],
            'banner_image' => $place->banner_image ?: $d['banner_image'],
            'blocks' => $place->blocks ?? [],
            'services_title' => $d['services_title'],
            'services' => self::serviceCards($map, $place, $vars),
            'cities_title' => $d['cities_title'],
            'cities' => $cities->map(fn (Location $c) => ['name' => $c->name, 'path' => $map->keys["l{$c->id}"]])->values()->all(),
            'faqs' => $place->faqs ?? $d['faqs'],
            'contact_title' => $d['contact_title'],
            'contact_text' => $d['contact_text'],
            'meta_title' => $place->meta_title ?: $d['meta_title'],
            'meta_description' => $place->meta_description ?: ($d['meta_description'] ?: ($place->subheading ?: $d['subheading'])),
        ];
    }

    private static function servicePage(LocationMap $map, Location $place, Service $service, array $vars): array
    {
        $d = self::defaults($map->site);
        $custom = LocationPage::where('location_id', $place->id)->where('service_id', $service->id)->first();

        return [
            'type' => 'service',
            'name' => $service->name,
            'location' => ['name' => $place->name, 'path' => $map->keys["l{$place->id}"]],
            'title' => $custom?->title ?: ($service->title ?: self::SERVICE_TITLE),
            'summary' => (string) $service->summary,
            'icon' => $service->icon,
            'banner_image' => $place->banner_image ?: $d['banner_image'],
            'blocks' => $custom?->blocks ?? $service->blocks ?? [],
            'faqs' => $custom?->faqs ?? $service->faqs ?? [],
            'services_title' => $d['services_title'],
            // The other services here.
            'services' => array_values(array_filter(
                self::serviceCards($map, $place, $vars),
                fn (array $card) => $card['path'] !== $map->keys["l{$place->id}s{$service->id}"],
            )),
            'contact_title' => $d['contact_title'],
            'contact_text' => $d['contact_text'],
            'meta_title' => $custom?->meta_title ?: ($service->meta_title ?: self::SERVICE_META_TITLE),
            'meta_description' => $custom?->meta_description ?: ($service->meta_description ?: (string) $service->summary),
        ];
    }

    /** One box per service this place offers, linking to its page here. */
    private static function serviceCards(LocationMap $map, Location $place, array $vars): array
    {
        $cards = [];
        foreach ($map->services as $service) {
            if ($path = $map->keys["l{$place->id}s{$service->id}"] ?? null) {
                $cards[] = self::fill([
                    'name' => $service->name,
                    'icon' => $service->icon,
                    'summary' => (string) $service->summary,
                    'path' => $path,
                ], ['{service}' => $service->name] + $vars);
            }
        }

        return $cards;
    }

    /** Replaces the placeholders in every text; names are escaped inside rich-text (HTML) fields. */
    public static function fill(mixed $value, array $vars, string|int|null $key = null): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::fill($v, $vars, $k);
            }

            return $value;
        }
        if (! is_string($value) || ! str_contains($value, '{')) {
            return $value;
        }

        $html = in_array($key, ['html', 'left', 'right'], true);

        return strtr($value, $html ? array_map(fn ($v) => e($v), $vars) : $vars);
    }
}
