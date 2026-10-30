<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Site;
use App\Support\BrandCache;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Writer profiles for a brand. Editing one updates the author box on every post that uses it. */
class AuthorController extends Controller
{
    public function index(Site $site)
    {
        return Inertia::render('authors/index', [
            'site' => $site,
            'authors' => $site->authors()->withCount('posts')->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Site $site)
    {
        $author = $site->authors()->create($this->validated($request));
        // The first writer becomes the default, so the box shows up right away.
        $this->keepOneDefault($site, $author->is_default || $site->authors()->count() === 1 ? $author : null);
        BrandCache::purgeLater($site);

        $this->toast("{$author->name} added");

        return back();
    }

    public function update(Request $request, Site $site, Author $author)
    {
        $author->update($this->validated($request));
        $this->keepOneDefault($site, $author->is_default ? $author : null);
        BrandCache::purgeLater($site);

        $this->toast('Author saved. Every post with this writer shows the change.');

        return back();
    }

    public function destroy(Site $site, Author $author)
    {
        // The foreign key does this too; explicit in case a host's tables lack it.
        $author->posts()->update(['author_id' => null]);
        $author->delete();
        BrandCache::purgeLater($site);

        $this->toast("{$author->name} removed. Their posts now show the default writer.");

        return back();
    }

    /** Only one default per brand: the given author, or whichever one already is. */
    private function keepOneDefault(Site $site, ?Author $default): void
    {
        if ($default) {
            $site->authors()->whereKeyNot($default->id)->where('is_default', true)->update(['is_default' => false]);
            $default->forceFill(['is_default' => true])->save();
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'photo' => ['nullable', 'string', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_default' => ['boolean'],
        ]);
        $data['photo'] = HtmlSanitizer::safeUrl($data['photo'] ?? null, false) ?: null;

        return $data;
    }
}
