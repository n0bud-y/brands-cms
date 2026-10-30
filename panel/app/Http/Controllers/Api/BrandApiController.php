<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Page;
use App\Models\Post;
use App\Models\Site;
use App\Support\LocationMap;
use App\Support\LocationPages;
use Illuminate\Http\Request;

/** Read-only API the brand sites call (server-side, via cms/brand.php). Published content only. */
class BrandApiController extends Controller
{
    private const SUMMARY = ['id', 'title', 'slug', 'layout', 'author_id', 'show_author', 'category', 'cover_image', 'excerpt', 'published_at', 'updated_at'];

    /**
     * Everything site-wide in one cached call: settings (contact, logo, theme, site code…) and
     * the SEO the panel sets for the website's own pages (path => title, description, image, noindex).
     */
    public function settings(Site $site)
    {
        return [
            'name' => $site->name,
            'url' => $site->url,
            'settings' => (object) $site->settings()->pluck('value', 'key')->all(),
            'pages' => (object) $site->pages()->get()->mapWithKeys(fn (Page $page) => [$page->path => $page->toSeo()])->filter()->all(),
        ];
    }

    public function posts(Request $request, Site $site)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);
        $posts = $site->posts()->published()->latest('published_at')->select(self::SUMMARY)->with('author')->paginate($perPage);
        $default = $this->defaultAuthor($site);

        return [
            'data' => $posts->getCollection()->map(fn (Post $post) => $this->summary($post, $default)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ];
    }

    public function post(Site $site, string $slug)
    {
        $post = $site->posts()->published()->where('slug', $slug)->with('author')->first();
        if ($post) {
            $author = $this->authorOf($post, $this->defaultAuthor($site));

            return $this->summary($post, $author) + [
                'blocks' => $post->blocks ?? [],
                'meta_title' => $post->meta_title ?: $post->title,
                'meta_description' => $post->meta_description ?: (string) $post->excerpt,
                'og_image' => $post->og_image ?: $post->cover_image,
                'schema' => $post->schema,
                // The writer box under the post: name, role, photo, bio; null when switched off or no writer.
                'author_box' => $author?->toCard(),
            ];
        }

        // Renamed post: tell the brand site where it lives now so it can 301.
        $redirect = $site->redirects()->where('old_slug', $slug)->value('post_id');
        $target = $redirect ? $site->posts()->published()->whereKey($redirect)->value('slug') : null;
        if ($target) {
            return response()->json(['redirect' => $target], 301);
        }

        return response()->json(['message' => 'Not found.'], 404);
    }

    public function sitemap(Site $site)
    {
        return [
            'data' => $site->posts()->published()->latest('published_at')->limit(50000)->get(['slug', 'updated_at'])
                ->map(fn (Post $post) => ['slug' => $post->slug, 'updated_at' => $post->updated_at->toAtomString()]),
        ];
    }

    /**
     * Every location URL in one small map, so the brand site answers unknown URLs itself:
     * paths (URL => page key), redirects (old URL => new URL) and the menu (states with cities).
     */
    public function locations(Site $site)
    {
        $map = new LocationMap($site);
        $defaults = LocationPages::defaults($site);

        return [
            'paths' => (object) $map->paths,
            'redirects' => (object) $map->redirects(),
            'menu' => $map->menu(),
            // Heading and intro of the main /locations page.
            'hub' => LocationPages::fill(['heading' => $defaults['hub_heading'], 'text' => $defaults['hub_text']], ['{brand}' => $site->name]),
        ];
    }

    /** One state, city or service page by its key ("l5", "l5s3"), placeholders filled. */
    public function location(Site $site, string $key)
    {
        return LocationPages::page(new LocationMap($site), $key) ?? response()->json(['message' => 'Not found.'], 404);
    }

    private function defaultAuthor(Site $site): ?Author
    {
        return $site->authors()->where('is_default', true)->first();
    }

    /** The post's own writer, else the brand's default one; none when the post hides its writer. */
    private function authorOf(Post $post, ?Author $default): ?Author
    {
        return $post->show_author ? ($post->author ?? $default) : null;
    }

    private function summary(Post $post, ?Author $default): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'layout' => $post->layout,
            'category' => $post->category,
            'author' => $this->authorOf($post, $default)?->name,
            'cover_image' => $post->cover_image,
            'excerpt' => $post->excerpt,
            'published_at' => $post->published_at?->toAtomString(),
            'updated_at' => $post->updated_at?->toAtomString(),
        ];
    }
}
