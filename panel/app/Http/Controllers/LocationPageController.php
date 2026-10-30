<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationPage;
use App\Models\Site;
use App\Support\Blocks;
use App\Support\BrandCache;
use App\Support\LocationMap;
use App\Support\LocationPages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** One location's page for one service: its own slug, text or SEO, or hidden. Empty fields use the service template. */
class LocationPageController extends Controller
{
    public function edit(Site $site, Location $location, int $serviceId)
    {
        $service = $site->services()->findOrFail($serviceId);
        $map = new LocationMap($site);
        $state = $location->parent_id ? $location->parent : $location;

        return Inertia::render('locations/page', [
            'site' => $site,
            'location' => $location->only(['id', 'name', 'slug', 'parent_id']),
            'service' => $service,
            'page' => $location->pages()->where('service_id', $service->id)->first(),
            'path' => $map->keys["l{$location->id}s{$service->id}"] ?? null,
            // "/california/" for a city's pages, "/" for a state's.
            'prefix' => '/'.($location->parent_id ? $state->slug.'/' : ''),
            'default_slug' => "{$location->slug}-{$service->slug}",
            'vars' => ['location' => $location->name, 'state' => $state->name, 'service' => $service->name, 'brand' => $site->name],
            'fallbacks' => ['title' => LocationPages::SERVICE_TITLE, 'meta_title' => LocationPages::SERVICE_META_TITLE],
        ]);
    }

    public function update(Request $request, Site $site, Location $location, int $serviceId)
    {
        $service = $site->services()->findOrFail($serviceId);
        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'is_hidden' => ['boolean'],
            'title' => ['nullable', 'string', 'max:255'],
            'blocks' => ['nullable', 'array', 'max:200'],
            'blocks.*.type' => ['required', Rule::in(Blocks::TYPES)],
            'faqs' => ['nullable', 'array', 'max:50'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ], ['slug.regex' => 'Use lowercase letters, numbers and single hyphens only.']);

        // The form sends the whole page; anything left out goes back to the template.
        $data += ['slug' => null, 'is_hidden' => false, 'title' => null, 'meta_title' => null, 'meta_description' => null];
        // null = the service's template.
        $data['blocks'] = $request->input('blocks') === null ? null : Blocks::normalize($request->input('blocks'));
        $data['faqs'] = $request->input('faqs') === null ? null : Blocks::faqs($request->input('faqs'));
        if ($data['slug'] === "{$location->slug}-{$service->slug}") {
            $data['slug'] = null;
        }
        $this->ensureFreeUrl($site, $location, $service->id, $data['slug']);

        LocationMap::tracking($site, function () use ($location, $service, $data) {
            $page = LocationPage::firstOrNew(['location_id' => $location->id, 'service_id' => $service->id]);
            $page->fill($data);
            // Nothing differs from the template any more: no row needed.
            $plain = ! $page->is_hidden && collect($page->only(['slug', 'title', 'blocks', 'faqs', 'meta_title', 'meta_description']))->every(fn ($v) => $v === null || $v === '');
            $plain ? ($page->exists && $page->delete()) : $page->save();
        });
        BrandCache::purgeLater($site);

        $this->toast(($data['is_hidden'] ?? false) ? "Hidden: {$location->name} no longer shows {$service->name}" : 'Page saved. Live in seconds 🚀');

        return back();
    }

    /** Back to the service template (and its default URL). */
    public function destroy(Site $site, Location $location, int $serviceId)
    {
        LocationMap::tracking($site, fn () => $location->pages()->where('service_id', $serviceId)->delete());
        BrandCache::purgeLater($site);

        $this->toast('Back to the service template', 'info');

        return back();
    }

    /** A custom slug must not take a URL another page already uses. */
    private function ensureFreeUrl(Site $site, Location $location, int $serviceId, ?string $slug): void
    {
        if ($slug === null) {
            return;
        }

        $path = ($location->parent_id ? $location->parent->slug.'/' : '').$slug;
        $owner = (new LocationMap($site))->paths[$path] ?? null;
        if ($owner !== null && $owner !== "l{$location->id}s{$serviceId}") {
            throw ValidationException::withMessages(['slug' => "/{$path} is already used by another page."]);
        }
    }
}
