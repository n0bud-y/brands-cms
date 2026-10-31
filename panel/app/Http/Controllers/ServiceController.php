<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Site;
use App\Support\Blocks;
use App\Support\BrandCache;
use App\Support\HtmlSanitizer;
use App\Support\LocationMap;
use App\Support\LocationPages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** A brand's services. Each one is a template that becomes a page in every state and city. */
class ServiceController extends Controller
{
    public function index(Site $site)
    {
        return Inertia::render('services/index', [
            'site' => $site,
            'services' => $site->services()->get(['id', 'name', 'slug', 'icon', 'summary', 'is_active']),
            'places' => $site->locations()->where('is_active', true)->count(),
        ]);
    }

    public function create(Site $site)
    {
        return Inertia::render('services/editor', ['site' => $site, 'service' => null, ...$this->hints($site)]);
    }

    public function store(Request $request, Site $site)
    {
        $data = $this->validated($request, $site);
        $service = LocationMap::tracking($site, fn () => $site->services()->create($data + ['sort' => $site->services()->max('sort') + 1]));
        BrandCache::purgeLater($site);

        $this->toast("{$service->name} added. It now has a page in every state and city 🗺️");

        return redirect("/s/{$site->slug}/services/{$service->id}");
    }

    public function edit(Site $site, Service $service)
    {
        return Inertia::render('services/editor', ['site' => $site, 'service' => $service, ...$this->hints($site)]);
    }

    public function update(Request $request, Site $site, Service $service)
    {
        $data = $this->validated($request, $site, $service);
        LocationMap::tracking($site, fn () => $service->update($data));
        BrandCache::purgeLater($site);

        $this->toast('Service saved. Every location page shows the change.');

        return back();
    }

    public function destroy(Site $site, Service $service)
    {
        $service->delete();
        BrandCache::purgeLater($site);

        $this->toast("{$service->name} deleted, with its page in every location");

        return redirect("/s/{$site->slug}/services");
    }

    /** New order of the boxes on every location page. */
    public function reorder(Request $request, Site $site)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($site->services()->whereIn('id', $ids)->get() as $service) {
            $service->update(['sort' => array_search($service->id, $ids, true)]);
        }
        BrandCache::purgeLater($site);

        return back();
    }

    /** An example place for the editor's preview, and the texts used when a field is empty. */
    private function hints(Site $site): array
    {
        $map = new LocationMap($site);
        $place = $map->cities->first() ?? $map->states->first();

        return [
            'example' => [
                'location' => $place?->name ?? 'California',
                'state' => $place ? $map->states[$place->parent_id ?? $place->id]->name : 'California',
                'prefix' => $place ? ($place->parent_id ? $map->states[$place->parent_id]->slug.'/' : '').$place->slug : 'california',
            ],
            'fallbacks' => ['title' => LocationPages::SERVICE_TITLE, 'meta_title' => LocationPages::SERVICE_META_TITLE],
        ];
    }

    private function validated(Request $request, Site $site, ?Service $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('services')->where('site_id', $site->id)->ignore($service),
            ],
            'icon' => ['nullable', 'string', 'max:2048'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'title' => ['nullable', 'string', 'max:255'],
            'blocks' => ['nullable', 'array', 'max:200'],
            'blocks.*.type' => ['required', Rule::in(Blocks::TYPES)],
            'feature' => ['nullable', 'array'],
            'faqs' => ['nullable', 'array', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'is_active' => ['boolean'],
        ], [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens only.',
            'slug.unique' => 'This brand already has a service with this slug.',
        ]);

        $data['icon'] = HtmlSanitizer::safeUrl($data['icon'] ?? null, false) ?: null;
        $data['blocks'] = Blocks::normalize($request->input('blocks', []));
        $data['feature'] = Blocks::feature($request->input('feature'));
        $data['faqs'] = Blocks::faqs($request->input('faqs', []));

        return $data;
    }
}
