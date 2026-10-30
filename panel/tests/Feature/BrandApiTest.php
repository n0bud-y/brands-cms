<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandApiTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->site = new Site(['name' => 'Brand A', 'slug' => 'brand-a', 'url' => 'https://brand-a.test']);
        $this->key = $this->site->rotateApiKey();
        $this->site->rotateCacheSecret();
        $this->site->save();
    }

    private function brandGet(string $path, ?string $key = null)
    {
        return $this->getJson("/api/v1/brand-a{$path}", ['X-Api-Key' => $key ?? $this->key]);
    }

    public function test_requires_a_valid_api_key(): void
    {
        $this->brandGet('/settings', 'wrong')->assertUnauthorized();
        $this->getJson('/api/v1/brand-a/settings')->assertUnauthorized();
        $this->brandGet('/settings')->assertOk();
    }

    public function test_inactive_site_is_rejected(): void
    {
        $this->site->update(['is_active' => false]);

        $this->brandGet('/settings')->assertUnauthorized();
    }

    public function test_key_of_one_brand_does_not_open_another(): void
    {
        $other = new Site(['name' => 'Brand B', 'slug' => 'brand-b', 'url' => 'https://brand-b.test']);
        $other->rotateApiKey();
        $other->rotateCacheSecret();
        $other->save();

        $this->getJson('/api/v1/brand-b/settings', ['X-Api-Key' => $this->key])->assertUnauthorized();
    }

    public function test_returns_settings_as_a_map(): void
    {
        $this->site->settings()->create(['key' => 'phone', 'value' => '+1 555']);

        $this->brandGet('/settings')->assertOk()->assertJsonPath('settings.phone', '+1 555')->assertJsonPath('name', 'Brand A');
    }

    public function test_lists_only_published_posts(): void
    {
        $this->site->posts()->create(['title' => 'Live', 'slug' => 'live', 'status' => 'published', 'published_at' => now()->subMinute()]);
        $this->site->posts()->create(['title' => 'Draft', 'slug' => 'draft', 'status' => 'draft']);
        $this->site->posts()->create(['title' => 'Scheduled', 'slug' => 'later', 'status' => 'published', 'published_at' => now()->addDay()]);

        $this->brandGet('/posts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'live')->assertJsonPath('meta.total', 1);
        $this->brandGet('/posts/draft')->assertNotFound();
        $this->brandGet('/posts/later')->assertNotFound();
        $this->brandGet('/sitemap')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_post_detail_falls_back_to_title_and_excerpt_for_meta(): void
    {
        $this->site->posts()->create([
            'title' => 'Hello', 'slug' => 'hello', 'excerpt' => 'Short', 'status' => 'published', 'published_at' => now(),
            'blocks' => [['id' => 'a', 'type' => 'heading', 'text' => 'Hi', 'level' => 2]],
            'schema' => ['@context' => 'https://schema.org', '@type' => 'FAQPage'],
        ]);

        $this->brandGet('/posts/hello')->assertOk()
            ->assertJsonPath('meta_title', 'Hello')
            ->assertJsonPath('meta_description', 'Short')
            ->assertJsonPath('blocks.0.type', 'heading')
            ->assertJsonPath('schema.@type', 'FAQPage');
    }

    public function test_renamed_slug_answers_with_a_redirect(): void
    {
        $post = $this->site->posts()->create(['title' => 'T', 'slug' => 'old-slug', 'status' => 'published', 'published_at' => now()]);
        $post->update(['slug' => 'new-slug']);

        $this->brandGet('/posts/old-slug')->assertStatus(301)->assertJsonPath('redirect', 'new-slug');
        $this->brandGet('/posts/new-slug')->assertOk();
    }

    public function test_posts_carry_category_and_their_writer_or_the_default_one(): void
    {
        $default = $this->site->authors()->create(['name' => 'Editorial', 'bio' => 'We edit.', 'is_default' => true]);
        $jane = $this->site->authors()->create(['name' => 'Jane']);
        $post = fn (string $slug, array $extra = []) => $this->site->posts()->create($extra + [
            'title' => $slug, 'slug' => $slug, 'layout' => 'classic', 'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        $post('by-default', ['category' => 'Tips']);
        $post('by-jane', ['author_id' => $jane->id]);
        $post('hidden', ['author_id' => $jane->id, 'show_author' => false]);

        $this->brandGet('/posts/by-default')->assertJsonPath('category', 'Tips')->assertJsonPath('author', 'Editorial')
            ->assertJsonPath('author_box', ['name' => 'Editorial', 'role' => null, 'photo' => null, 'bio' => 'We edit.']);
        $this->brandGet('/posts/by-jane')->assertJsonPath('author_box.name', 'Jane');
        $this->brandGet('/posts/hidden')->assertJsonPath('author', null)->assertJsonPath('author_box', null);

        $authors = collect($this->brandGet('/posts')->json('data'))->pluck('author', 'slug');
        $this->assertSame(['by-default' => 'Editorial', 'by-jane' => 'Jane', 'hidden' => null], $authors->sortKeys()->all());

        // Removing a writer falls back to the default one.
        $jane->delete();
        $this->brandGet('/posts/by-jane')->assertJsonPath('author_box.name', 'Editorial');
        $default->update(['is_default' => false]);
        $this->brandGet('/posts/by-jane')->assertJsonPath('author_box', null);
    }
}
