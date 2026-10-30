<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Support\BrandCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Admin-only: brands, their website connection, activation and deletion. */
class SiteController extends Controller
{
    public function index()
    {
        return Inertia::render('sites/index', [
            'brands' => Site::withCount(['posts', 'users'])->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('sites/form', ['site' => null]);
    }

    public function store(Request $request)
    {
        $site = new Site($this->validated($request));
        $apiKey = $site->rotateApiKey();
        $site->rotateCacheSecret();
        $site->save();

        $this->toast('Brand created 🎉', extra: ['api_key' => $apiKey]);

        return redirect("/s/{$site->slug}/brand");
    }

    public function edit(Site $site)
    {
        return Inertia::render('sites/form', [
            'site' => $site,
            'connection' => [
                'panel_url' => rtrim(config('app.url'), '/'),
                // Unreadable after an APP_KEY change; the page still opens so a new secret can be issued.
                'cache_secret' => rescue(fn () => $site->cache_secret, '', false),
                'cache_clear_url' => $site->cacheClearUrl(),
            ],
        ]);
    }

    public function update(Request $request, Site $site)
    {
        $site->update($this->validated($request, $site));
        BrandCache::purgeLater($site);

        $this->toast('Brand saved');

        return redirect("/s/{$site->slug}/brand");
    }

    public function destroy(Site $site)
    {
        $site->delete();
        Storage::disk('public')->deleteDirectory(UploadController::directory($site));

        $this->toast("{$site->name} deleted");

        return redirect('/sites');
    }

    public function rotateKey(Site $site)
    {
        $apiKey = $site->rotateApiKey();
        $site->save();

        $this->toast('New API key issued', extra: ['api_key' => $apiKey]);

        return back();
    }

    public function rotateSecret(Site $site)
    {
        $site->rotateCacheSecret();
        $site->save();

        $this->toast('New cache secret issued');

        return back();
    }

    public function testConnection(Site $site)
    {
        $problem = BrandCache::clear($site);
        $problem === null
            ? $this->toast('Website reached, cache cleared 🔌')
            : $this->toast($problem, 'error');

        return back();
    }

    private function validated(Request $request, ?Site $site = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('sites')->ignore($site)],
            'url' => ['required', 'url:http,https', 'max:255'],
            'cache_clear_url' => ['nullable', 'url:http,https', 'max:255'],
            'available_layouts' => ['required', 'array', 'min:1', 'max:20'],
            'available_layouts.*' => ['required', 'string', 'distinct', 'regex:/^[a-z0-9_-]{1,32}$/'],
            'is_active' => ['boolean'],
        ]);
        $data['url'] = rtrim($data['url'], '/');

        return $data;
    }
}
