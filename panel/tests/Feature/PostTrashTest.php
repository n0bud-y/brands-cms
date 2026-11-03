<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Posts: the trash (restore / delete forever) and the month and day filters. */
class PostTrashTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();
        $this->withoutVite();
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->site = new Site(['name' => 'A', 'slug' => 'brand-a', 'url' => 'https://brand-a.test']);
        $this->key = $this->site->rotateApiKey();
        $this->site->rotateCacheSecret();
        $this->site->save();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function makePost(string $slug, string $date): Post
    {
        return $this->site->posts()->create(['title' => ucfirst($slug), 'slug' => $slug, 'status' => 'published', 'published_at' => $date]);
    }

    public function test_deleting_moves_a_post_to_the_trash_and_off_the_website(): void
    {
        $post = $this->makePost('hello', '2026-03-10 10:00');

        $this->delete("/s/brand-a/posts/{$post->id}")->assertRedirect('/s/brand-a/posts');

        $this->assertSoftDeleted($post);
        $this->getJson('/api/v1/brand-a/posts/hello', ['X-Api-Key' => $this->key])->assertNotFound();
        $this->get('/s/brand-a/posts')->assertInertia(fn (Assert $page) => $page->where('counts.total', 0)->where('counts.trash', 1));
        $this->get('/s/brand-a/posts?status=trash')->assertInertia(fn (Assert $page) => $page->where('posts.data.0.slug', 'hello'));
        // The address stays taken while the post is in the trash.
        $this->post('/s/brand-a/posts', ['title' => 'X', 'slug' => 'hello', 'layout' => 'classic', 'status' => 'draft'])->assertSessionHasErrors('slug');
    }

    public function test_restore_and_delete_forever(): void
    {
        $post = $this->makePost('hello', '2026-03-10 10:00');
        $post->delete();

        $this->post("/s/brand-a/posts/{$post->id}/restore")->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted($post);
        $this->getJson('/api/v1/brand-a/posts/hello', ['X-Api-Key' => $this->key])->assertOk();

        // Only posts in the trash can be deleted forever.
        $this->delete("/s/brand-a/posts/{$post->id}/forever")->assertNotFound();
        $post->delete();
        $this->delete("/s/brand-a/posts/{$post->id}/forever");
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_filter_by_month_and_by_day(): void
    {
        $this->makePost('march', '2026-03-10 10:00');
        $this->makePost('march-later', '2026-03-21 09:00');
        $this->makePost('april', '2026-04-02 12:00');

        $this->get('/s/brand-a/posts')->assertInertia(fn (Assert $page) => $page
            ->where('months.0', ['value' => '2026-04', 'label' => 'April 2026', 'count' => 1])
            ->where('months.1.count', 2));
        $this->get('/s/brand-a/posts?month=2026-03')->assertInertia(fn (Assert $page) => $page->has('posts.data', 2));
        $this->get('/s/brand-a/posts?day=2026-03-21')->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)->where('posts.data.0.slug', 'march-later')->where('filters.day', '2026-03-21'));
        // Junk values are ignored, not errors.
        $this->get('/s/brand-a/posts?month=nope')->assertInertia(fn (Assert $page) => $page->has('posts.data', 3)->where('filters.month', ''));
    }
}
