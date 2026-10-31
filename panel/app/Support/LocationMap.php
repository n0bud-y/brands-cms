<?php

namespace App\Support;

use App\Models\Location;
use App\Models\LocationPage;
use App\Models\LocationRedirect;
use App\Models\Service;
use App\Models\Site;
use Illuminate\Support\Collection;

/**
 * Every live location URL of a brand and the page behind it. Page keys: "l5" (state/city 5) and "l5s3" (its service 3).
 *
 *   /california                         state           l1
 *   /california-ghostwriting            state service   l1s3   (or the page's own slug)
 *   /california/los-angeles             city            l2
 *   /california/los-angeles-ghostwriting city service   l2s3   (or the page's own slug)
 *
 * Only active states, cities (in active states) and services count; hidden pages are left out.
 */
final class LocationMap
{
    /** @var Collection<int, Location> active states by id */
    public Collection $states;

    /** @var Collection<int, Location> active cities (of active states) by id */
    public Collection $cities;

    /** @var Collection<int, Service> active services, in their order */
    public Collection $services;

    /** @var array<string, string> URL path => page key */
    public array $paths = [];

    /** @var array<string, string> page key => URL path */
    public array $keys = [];

    public function __construct(public Site $site)
    {
        $locations = $site->locations()->where('is_active', true)->orderBy('sort')->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'image', 'summary']);
        $this->states = $locations->whereNull('parent_id')->keyBy('id');
        $this->cities = $locations->whereNotNull('parent_id')->filter(fn (Location $c) => $this->states->has($c->parent_id))->keyBy('id');
        $this->services = $site->services()->where('is_active', true)->get(['id', 'name', 'slug', 'icon', 'summary']);

        $pages = LocationPage::whereIn('location_id', $locations->modelKeys())->get(['location_id', 'service_id', 'slug', 'is_hidden'])
            ->keyBy(fn (LocationPage $p) => "l{$p->location_id}s{$p->service_id}");

        // State and city pages first: when two pages want one URL, the place wins.
        foreach ($this->states as $state) {
            $this->add($state->slug, "l{$state->id}");
        }
        foreach ($this->cities as $city) {
            $this->add($this->states[$city->parent_id]->slug.'/'.$city->slug, "l{$city->id}");
        }
        foreach ([...$this->states->all(), ...$this->cities->all()] as $place) {
            $prefix = $place->parent_id ? $this->states[$place->parent_id]->slug.'/' : '';
            foreach ($this->services as $service) {
                $key = "l{$place->id}s{$service->id}";
                $page = $pages[$key] ?? null;
                if (! $page?->is_hidden) {
                    $this->add($prefix.($page?->slug ?: "{$place->slug}-{$service->slug}"), $key);
                }
            }
        }
    }

    private function add(string $path, string $key): void
    {
        if (! isset($this->paths[$path])) {
            $this->paths[$path] = $key;
            $this->keys[$key] = $path;
        }
    }

    public function place(int $id): ?Location
    {
        return $this->states[$id] ?? $this->cities[$id] ?? null;
    }

    /** States with their cities, for the website's Locations menu. */
    public function menu(): array
    {
        return $this->states->map(fn (Location $state) => [
            'name' => $state->name,
            'image' => $state->image,
            'summary' => (string) $state->summary,
            'path' => $this->keys["l{$state->id}"],
            'cities' => $this->cities->where('parent_id', $state->id)
                ->map(fn (Location $city) => ['name' => $city->name, 'image' => $city->image, 'path' => $this->keys["l{$city->id}"]])->values()->all(),
        ])->values()->all();
    }

    /** Old URL => current URL, for pages whose slug changed. */
    public function redirects(): array
    {
        return $this->site->locationRedirects()->pluck('target', 'path')
            ->map(fn (string $key) => $this->keys[$key] ?? null)->filter()->all();
    }

    /**
     * Runs $change (an edit of slugs, services or locations) and keeps every page it moved reachable:
     * its old URL becomes a 301 to the new one. A URL that is live again stops redirecting.
     */
    public static function tracking(Site $site, callable $change): mixed
    {
        $before = (new self($site))->paths;
        $result = $change();
        $after = new self($site);

        foreach ($before as $path => $key) {
            if (! isset($after->paths[$path]) && isset($after->keys[$key])) {
                LocationRedirect::updateOrCreate(['site_id' => $site->id, 'path' => $path], ['target' => $key]);
            }
        }

        $stale = $site->locationRedirects()->pluck('path')->filter(fn (string $path) => isset($after->paths[$path]));
        if ($stale->isNotEmpty()) {
            $site->locationRedirects()->whereIn('path', $stale->all())->delete();
        }

        return $result;
    }
}
