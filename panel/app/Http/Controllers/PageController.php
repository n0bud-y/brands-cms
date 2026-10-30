<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Site;
use App\Support\BrandCache;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** SEO for the brand website's own pages: title, description, share image, hidden from Google. */
class PageController extends Controller
{
    public function index(Request $request, Site $site)
    {
        $request->session()->put('last_site', $site->slug);

        return Inertia::render('pages/index', [
            'site' => $site,
            'pages' => $site->pages()->orderByRaw("path = '/' desc")->orderBy('path')->get(),
        ]);
    }

    /** Add a page by hand, e.g. /blogs or a page in a subfolder. */
    public function store(Request $request, Site $site)
    {
        $path = $this->path($request->input('path'));
        $request->merge(['path' => $path])->validate([
            'path' => ['required', 'string', 'max:255', 'regex:#^/[A-Za-z0-9/_.\-]*$#', Rule::unique('pages')->where('site_id', $site->id)],
        ], [
            'path.regex' => 'Use the page address, like /about or /services/editing.',
            'path.unique' => 'This page is already in the list.',
        ]);
        $site->pages()->create(['path' => $path]);

        $this->toast("{$path} added");

        return back();
    }

    public function update(Request $request, Site $site, Page $page)
    {
        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'noindex' => ['boolean'],
        ]);
        $data['og_image'] = HtmlSanitizer::safeUrl($data['og_image'] ?? null, false) ?: null;
        $page->update($data);
        BrandCache::purgeLater($site);

        $this->toast($page->noindex ? "Saved. {$page->path} is hidden from Google." : 'Saved. The website updates in seconds ⚡');

        return back();
    }

    public function destroy(Site $site, Page $page)
    {
        $page->delete();
        BrandCache::purgeLater($site);

        $this->toast("{$page->path} removed from the list. The page itself is untouched.", 'info');

        return back();
    }

    /** Asks the website which pages it has and what their files say; new ones join the list. */
    public function discover(Site $site)
    {
        [$found, $problem] = BrandCache::pages($site);
        if ($problem !== null) {
            $this->toast($problem, 'error');

            return back();
        }

        $known = $site->pages()->get()->keyBy('path');
        $added = 0;
        foreach (array_slice($found, 0, 2000) as $item) {
            $path = is_array($item) ? $this->path($item['path'] ?? null) : '';
            if ($path === '' || mb_strlen($path) > 255) {
                continue;
            }
            $page = $known[$path] ?? new Page(['path' => $path]);
            $added += $page->exists ? 0 : 1;
            $page->fill([
                'found_title' => Str::limit(trim((string) ($item['title'] ?? '')), 250, '') ?: null,
                'found_description' => Str::limit(trim((string) ($item['description'] ?? '')), 495, '') ?: null,
            ]);
            $site->pages()->save($page);
        }

        $this->toast($added ? "Found {$added} new ".Str::plural('page', $added).' 🔍' : 'All pages are already in the list ✓', $added ? 'success' : 'info');

        return back();
    }

    /** "about/" or "/about.php" -> "/about"; "" -> "/". */
    private function path(mixed $value): string
    {
        $path = '/'.trim(strtok(trim((string) $value), '?#') ?: '', '/');

        return (string) preg_replace('#\.php$#', '', $path === '/index.php' ? '/' : $path);
    }
}
