<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Moving a post to another brand, duplicating services and locations, and posts per page. */
class CopyAndMoveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();
        $this->withoutVite();
        Http::fake(['*' => Http::response(['ok' => true])]);
    }

    private function brand(string $slug): Site
    {
        $site = new Site(['name' => ucfirst($slug), 'slug' => $slug, 'url' => "https://{$slug}.test"]);
        $site->rotateApiKey();
        $site->rotateCacheSecret();
        $site->save();

        return $site;
    }

    public function test_a_post_moves_to_another_brand(): void
    {
        [$a, $b] = [$this->brand('brand-a'), $this->brand('brand-b')];
        $writer = $a->authors()->create(['name' => 'Olivia']);
        $post = $a->posts()->create(['title' => 'Hello', 'slug' => 'hello', 'author_id' => $writer->id, 'status' => 'draft']);
        $b->posts()->create(['title' => 'Taken', 'slug' => 'hello', 'status' => 'draft']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post("/s/brand-a/posts/{$post->id}/move", ['site_id' => $b->id])->assertRedirect("/s/brand-b/posts/{$post->id}");

        $post->refresh();
        $this->assertSame([$b->id, 'hello-2', null], [$post->site_id, $post->slug, $post->author_id]);
        // The move leaves no redirect behind in either brand.
        $this->assertDatabaseCount('post_redirects', 0);
    }

    public function test_editors_can_only_move_posts_to_their_own_brands(): void
    {
        [$a, $b] = [$this->brand('brand-a'), $this->brand('brand-b')];
        $post = $a->posts()->create(['title' => 'Hello', 'slug' => 'hello', 'status' => 'draft']);
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->sites()->attach($a->id);
        $this->actingAs($editor);

        $this->post("/s/brand-a/posts/{$post->id}/move", ['site_id' => $b->id])->assertSessionHasErrors('site_id');
        $this->post("/s/brand-a/posts/{$post->id}/move", ['site_id' => $a->id])->assertSessionHasErrors('site_id');
        $this->assertSame($a->id, $post->fresh()->site_id);
    }

    public function test_services_and_locations_duplicate_switched_off(): void
    {
        $a = $this->brand('brand-a');
        $ca = $a->locations()->create(['name' => 'California', 'slug' => 'california', 'summary' => 'Sunny']);
        $la = $a->locations()->create(['name' => 'Los Angeles', 'slug' => 'los-angeles', 'parent_id' => $ca->id, 'image' => 'https://x.test/la.svg']);
        $ghost = $a->services()->create(['name' => 'Ghostwriting', 'slug' => 'ghostwriting', 'summary' => 'We write']);
        $la->pages()->create(['service_id' => $ghost->id, 'slug' => 'la-ghost', 'title' => 'LA ghostwriters']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post("/s/brand-a/services/{$ghost->id}/duplicate");
        $copy = $a->services()->where('slug', 'ghostwriting-copy')->firstOrFail();
        $this->assertSame(['Ghostwriting (copy)', false, 'We write'], [$copy->name, $copy->is_active, $copy->summary]);
        // Its per-page changes come along, without the custom web address.
        $this->assertSame(['LA ghostwriters', null], [$copy->pages()->first()->title, $copy->pages()->first()->slug]);

        $this->post("/s/brand-a/locations/{$la->id}/duplicate");
        $this->post("/s/brand-a/locations/{$la->id}/duplicate");
        $cities = $ca->children()->where('slug', 'like', 'los-angeles-copy%')->get();
        $this->assertSame(['los-angeles-copy', 'los-angeles-copy-2'], $cities->pluck('slug')->sort()->values()->all());
        $this->assertSame(['https://x.test/la.svg', false], [$cities[0]->image, $cities[0]->is_active]);

        // A state is copied without its cities.
        $this->post("/s/brand-a/locations/{$ca->id}/duplicate");
        $stateCopy = $a->locations()->where('slug', 'california-copy')->firstOrFail();
        $this->assertSame(['Sunny', null, 0], [$stateCopy->summary, $stateCopy->parent_id, $stateCopy->children()->count()]);
    }

    public function test_posts_per_page(): void
    {
        $a = $this->brand('brand-a');
        foreach (range(1, 12) as $i) {
            $a->posts()->create(['title' => "Post {$i}", 'slug' => "post-{$i}", 'status' => 'draft']);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/s/brand-a/posts')->assertInertia(fn (Assert $page) => $page->has('posts.data', 10)->where('posts.meta.last_page', 2));
        $this->get('/s/brand-a/posts?per_page=25')->assertInertia(fn (Assert $page) => $page->has('posts.data', 12)->where('filters.per_page', 25));
        $this->get('/s/brand-a/posts?per_page=999')->assertInertia(fn (Assert $page) => $page->has('posts.data', 10));
    }
}
