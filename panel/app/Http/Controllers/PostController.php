<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\Site;
use App\Support\Blocks;
use App\Support\BrandCache;
use App\Support\JsonLd;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request, Site $site)
    {
        $request->session()->put('last_site', $site->slug);

        $now = now();
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));

        $posts = $site->posts()
            ->select(['id', 'title', 'slug', 'layout', 'status', 'cover_image', 'published_at', 'updated_at'])
            ->when($status === 'draft', fn ($query) => $query->where('status', PostStatus::Draft))
            ->when($status === 'published', fn ($query) => $query->published())
            ->when($status === 'scheduled', fn ($query) => $query->where('status', PostStatus::Published)->where('published_at', '>', $now))
            ->when($q !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        // Per-status totals for the filter tiles, in one query.
        $counts = $site->posts()->toBase()->selectRaw(
            "count(*) as total,
             sum(case when status = 'draft' then 1 else 0 end) as draft,
             sum(case when status = 'published' and published_at <= ? then 1 else 0 end) as published,
             sum(case when status = 'published' and published_at > ? then 1 else 0 end) as scheduled",
            [$now, $now],
        )->first();

        return Inertia::render('posts/index', [
            'site' => $site,
            'posts' => [
                'data' => $posts->items(),
                'meta' => ['current_page' => $posts->currentPage(), 'last_page' => $posts->lastPage(), 'total' => $posts->total()],
            ],
            'counts' => array_map('intval', (array) $counts),
            'filters' => ['status' => $status ?? '', 'q' => $q],
        ]);
    }

    public function create(Site $site)
    {
        return Inertia::render('posts/editor', ['site' => $site, 'post' => null, ...$this->choices($site)]);
    }

    public function store(Request $request, Site $site)
    {
        $post = $site->posts()->create($this->validated($request, $site));
        BrandCache::purgeLater($site);

        $this->saved($site, $post);

        return redirect("/s/{$site->slug}/posts/{$post->id}");
    }

    public function edit(Site $site, Post $post)
    {
        return Inertia::render('posts/editor', ['site' => $site, 'post' => $this->present($post), ...$this->choices($site)]);
    }

    public function update(Request $request, Site $site, Post $post)
    {
        $post->update($this->validated($request, $site, $post));
        BrandCache::purgeLater($site);

        $this->saved($site, $post);

        return back();
    }

    /** Copy a post as a new draft. Drafts never reach the brand site, so there's no cache to clear. */
    public function duplicate(Site $site, Post $post)
    {
        $copy = $post->replicate();
        $copy->fill([
            'title' => Str::limit($post->title, 245, '').' (copy)',
            'slug' => $this->freeSlug($site, $post->slug.'-copy'),
            'status' => PostStatus::Draft,
            'published_at' => null,
        ])->save();

        $this->toast('Copied as a new draft', 'info');

        return redirect("/s/{$site->slug}/posts/{$copy->id}");
    }

    public function destroy(Site $site, Post $post)
    {
        $post->delete();
        BrandCache::purgeLater($site);

        $this->toast('Post deleted');

        return redirect("/s/{$site->slug}/posts");
    }

    /** Writers and the categories already in use, for the editor's pickers. */
    private function choices(Site $site): array
    {
        return [
            'authors' => $site->authors()->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default']),
            'categories' => $site->posts()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ];
    }

    /** The post as the editor needs it; schema comes back as pretty JSON text for the textarea. */
    private function present(Post $post): array
    {
        return [...$post->toArray(), 'schema' => JsonLd::pretty($post->schema)];
    }

    /** "{base}", "{base}-2", "{base}-3"… skipping slugs used by posts or kept as redirects. */
    private function freeSlug(Site $site, string $base): string
    {
        $base = rtrim(Str::limit($base, 180, ''), '-');
        $taken = $site->posts()->where('slug', 'like', "{$base}%")->pluck('slug')
            ->merge($site->redirects()->where('old_slug', 'like', "{$base}%")->pluck('old_slug'))
            ->flip();

        for ($slug = $base, $n = 2; isset($taken[$slug]); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }

    private function saved(Site $site, Post $post): void
    {
        if ($post->status === PostStatus::Draft) {
            $this->toast('Draft saved');
        } elseif ($post->published_at->isFuture()) {
            $this->toast('Scheduled ⏰ It goes live on its publish date', 'info');
        } else {
            $this->toast('Live! The website updates in seconds 🚀', action: ['label' => 'View ↗', 'href' => rtrim($site->url, '/').'/blogs/'.$post->slug]);
        }
    }

    private function validated(Request $request, Site $site, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts')->where('site_id', $site->id)->ignore($post),
            ],
            // A post may keep a layout the brand has since dropped; the brand site falls back to classic.
            'layout' => ['required', 'string', Rule::in(array_unique([...$site->available_layouts, $post?->layout ?? 'classic']))],
            'cover_image' => ['nullable', 'string', 'max:2048'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'blocks' => ['nullable', 'array', 'max:200'],
            'blocks.*.type' => ['required', Rule::in(Blocks::TYPES)],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'author_id' => ['nullable', 'integer', Rule::exists('authors', 'id')->where('site_id', $site->id)],
            'show_author' => ['boolean'],
            'category' => ['nullable', 'string', 'max:80'],
            'schema' => ['nullable', 'string', 'max:20000', JsonLd::rule()],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'published_at' => ['nullable', 'date'],
        ], [
            'slug.regex' => 'Use lowercase letters, numbers and single hyphens only.',
            'slug.unique' => 'This brand already has a post with this slug.',
        ]);

        // validate() only returns the "type" key of each block, so normalize the raw input.
        $data['blocks'] = Blocks::normalize($request->input('blocks', []));
        $data['schema'] = JsonLd::parse($data['schema'] ?? null);

        $data['published_at'] = isset($data['published_at'])
            ? Carbon::parse($data['published_at'])->utc()
            : ($data['status'] === PostStatus::Published->value ? ($post?->published_at ?? now()) : null);

        return $data;
    }
}
