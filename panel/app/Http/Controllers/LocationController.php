<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Site;
use App\Support\Blocks;
use App\Support\BrandCache;
use App\Support\HtmlSanitizer;
use App\Support\LocationMap;
use App\Support\LocationPages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** A brand's states and cities. Each one has its own page plus a page per service. */
class LocationController extends Controller
{
    public function index(Request $request, Site $site)
    {
        $request->session()->put('last_site', $site->slug);

        $all = $site->locations()->orderBy('sort')->orderBy('name')->get(['id', 'parent_id', 'name', 'slug', 'is_active']);
        $states = $all->whereNull('parent_id')->map(fn (Location $state) => [
            ...$state->toArray(),
            'cities' => $all->where('parent_id', $state->id)->values(),
        ])->values();

        return Inertia::render('locations/index', [
            'site' => $site,
            'states' => $states,
            'services' => $site->services()->where('is_active', true)->count(),
        ]);
    }

    public function create(Request $request, Site $site)
    {
        $parent = $request->integer('parent') ?: null;

        return Inertia::render('locations/editor', [
            'site' => $site,
            'location' => null,
            'parent_id' => $site->locations()->whereNull('parent_id')->whereKey($parent)->exists() ? $parent : null,
            ...$this->choices($site),
        ]);
    }

    public function store(Request $request, Site $site)
    {
        $data = $this->validated($request, $site);
        $location = LocationMap::tracking($site, fn () => $site->locations()->create($data + [
            'sort' => $site->locations()->where('parent_id', $data['parent_id'])->max('sort') + 1,
        ]));
        BrandCache::purgeLater($site);

        $this->saved($site, $location, "{$location->name} added");

        return redirect("/s/{$site->slug}/locations/{$location->id}");
    }

    public function edit(Site $site, Location $location)
    {
        $map = new LocationMap($site);
        $pages = $location->pages()->get()->keyBy('service_id');

        return Inertia::render('locations/editor', [
            'site' => $site,
            'location' => [...$location->toArray(), 'path' => $map->keys["l{$location->id}"] ?? null],
            'parent_id' => $location->parent_id,
            // Its page for every service: where it lives and whether it differs from the template.
            'pages' => $site->services()->get(['id', 'name', 'slug', 'is_active'])->map(fn ($service) => [
                'service_id' => $service->id,
                'name' => $service->name,
                'service_active' => $service->is_active,
                'path' => $map->keys["l{$location->id}s{$service->id}"] ?? null,
                'hidden' => (bool) $pages->get($service->id)?->is_hidden,
                'custom' => $pages->has($service->id) && ! $pages->get($service->id)->is_hidden,
            ]),
            ...$this->choices($site),
        ]);
    }

    public function update(Request $request, Site $site, Location $location)
    {
        $data = $this->validated($request, $site, $location);
        LocationMap::tracking($site, fn () => $location->update($data));
        BrandCache::purgeLater($site);

        $this->saved($site, $location, 'Saved');

        return back();
    }

    public function destroy(Site $site, Location $location)
    {
        $location->delete();
        BrandCache::purgeLater($site);

        $this->toast($location->isCity() ? "{$location->name} deleted" : "{$location->name} deleted, with its cities");

        return redirect("/s/{$site->slug}/locations");
    }

    /** New order of states, or of the cities in one state (menu, boxes and sitemap follow it). */
    public function reorder(Request $request, Site $site)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($site->locations()->whereIn('id', $ids)->get() as $location) {
            $location->update(['sort' => array_search($location->id, $ids, true)]);
        }
        BrandCache::purgeLater($site);

        return back();
    }

    /** The brand-wide texts every state and city page uses unless it sets its own. */
    public function defaults(Site $site)
    {
        return Inertia::render('locations/defaults', [
            'site' => $site,
            'defaults' => LocationPages::defaults($site),
            'fallbacks' => LocationPages::DEFAULTS,
        ]);
    }

    public function updateDefaults(Request $request, Site $site)
    {
        $data = $request->validate([
            'heading' => ['nullable', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:1000'],
            'banner_image' => ['nullable', 'string', 'max:2048'],
            'services_title' => ['nullable', 'string', 'max:255'],
            'cities_title' => ['nullable', 'string', 'max:255'],
            'faqs' => ['nullable', 'array', 'max:50'],
            'contact_title' => ['nullable', 'string', 'max:255'],
            'contact_text' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'hub_heading' => ['nullable', 'string', 'max:255'],
            'hub_text' => ['nullable', 'string', 'max:1000'],
            'hub_intro_title' => ['nullable', 'string', 'max:255'],
            'hub_intro' => ['nullable', 'string', 'max:20000'],
            'hub_image' => ['nullable', 'string', 'max:2048'],
        ]);
        $data['banner_image'] = HtmlSanitizer::safeUrl($data['banner_image'] ?? null, false);
        $data['hub_intro'] = HtmlSanitizer::clean($data['hub_intro'] ?? null);
        $data['hub_image'] = HtmlSanitizer::safeUrl($data['hub_image'] ?? null, false);
        $data['faqs'] = Blocks::faqs($request->input('faqs', []));

        $site->forceFill(['location_defaults' => $data])->save();
        BrandCache::purgeLater($site);

        $this->toast('Defaults saved. Every location page shows the change.');

        return back();
    }

    /** States (for the "in state" picker) and the texts used when a field is left empty. */
    private function choices(Site $site): array
    {
        return [
            'states' => $site->locations()->whereNull('parent_id')->orderBy('sort')->orderBy('name')->get(['id', 'name', 'slug']),
            'defaults' => LocationPages::defaults($site),
        ];
    }

    private function saved(Site $site, Location $location, string $message): void
    {
        $path = (new LocationMap($site))->keys["l{$location->id}"] ?? null;
        $path
            ? $this->toast("{$message}. Live in seconds 🚀", action: ['label' => 'View ↗', 'href' => rtrim($site->url, '/')."/{$path}"])
            : $this->toast("{$message}. It's switched off (or its state is), so it isn't on the website.", 'info');
    }

    private function validated(Request $request, Site $site, ?Location $location = null): array
    {
        $parentId = $request->integer('parent_id') ?: null;

        $data = $request->validate([
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('locations', 'id')->where('site_id', $site->id)->whereNull('parent_id'),
                // A state with cities can't become a city itself (only two levels).
                Rule::prohibitedIf(fn () => $location && $location->children()->exists()),
                Rule::notIn([$location?->id]),
            ],
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                // Unique among the states, or among the cities of one state.
                Rule::unique('locations')->where('site_id', $site->id)->ignore($location)
                    ->where(fn ($q) => $parentId ? $q->where('parent_id', $parentId) : $q->whereNull('parent_id')),
            ],
            'heading' => ['nullable', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:1000'],
            'banner_image' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'string', 'max:2048'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'blocks' => ['nullable', 'array', 'max:200'],
            'blocks.*.type' => ['required', Rule::in(Blocks::TYPES)],
            'faqs' => ['nullable', 'array', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'is_active' => ['boolean'],
        ], [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens only.',
            'slug.unique' => $parentId ? 'This state already has a city with this slug.' : 'There is already a state with this slug.',
            'parent_id.prohibited' => 'This state has cities, so it can\'t be moved into another state.',
        ]);

        $data['parent_id'] = $parentId;
        $data['banner_image'] = HtmlSanitizer::safeUrl($data['banner_image'] ?? null, false) ?: null;
        $data['image'] = HtmlSanitizer::safeUrl($data['image'] ?? null, false) ?: null;
        $data['blocks'] = Blocks::normalize($request->input('blocks', []));
        // null = use the brand's default FAQs.
        $data['faqs'] = $request->input('faqs') === null ? null : Blocks::faqs($request->input('faqs'));

        return $data;
    }
}
