<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();
        $this->withoutVite();
        Http::fake(['*' => Http::response(['ok' => true])]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function editor(Site ...$sites): User
    {
        $user = User::factory()->create(['role' => 'editor']);
        $user->sites()->attach(collect($sites)->pluck('id'));

        return $user;
    }

    private function site(string $slug = 'brand-a'): Site
    {
        $site = new Site([
            'name' => ucfirst($slug), 'slug' => $slug, 'url' => "https://{$slug}.test",
            'available_layouts' => ['classic', 'hero'],
        ]);
        $site->rotateApiKey();
        $site->rotateCacheSecret();
        $site->save();

        return $site;
    }

    private function postPayload(array $overrides = []): array
    {
        return $overrides + ['title' => 'Hello', 'slug' => 'hello', 'layout' => 'classic', 'status' => 'draft', 'blocks' => []];
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->component('auth/login'));
    }

    public function test_login_and_logout(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'secret-pass']);

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'secret-pass'])->assertRedirect('/');
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_creating_a_brand_flashes_its_api_key_once(): void
    {
        $this->actingAs($this->admin());

        $this->post('/sites', [
            'name' => 'Brand A', 'slug' => 'brand-a', 'url' => 'https://brand-a.test/', 'available_layouts' => ['classic'],
        ])->assertRedirect('/s/brand-a/brand');

        $site = Site::first();
        $this->assertSame('https://brand-a.test', $site->url);

        $page = $this->get('/s/brand-a/brand')->assertInertia(fn (Assert $page) => $page
            ->component('sites/form')
            ->hasFlash('api_key')
            ->missing('site.api_key_hash')
            ->missing('site.cache_secret'))
            ->inertiaPage();
        $this->assertTrue($site->checkApiKey($page['flash']['api_key']));

        $this->get('/s/brand-a/brand')->assertInertia(fn (Assert $page) => $page->missingFlash('api_key'));
    }

    public function test_editor_only_sees_assigned_brands(): void
    {
        $mine = $this->site('mine');
        $other = $this->site('other');
        $this->actingAs($this->editor($mine));

        $this->get('/')->assertRedirect('/s/mine/posts');
        $this->get('/s/mine/posts')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->has('sites', 1)
            ->where('sites.0.slug', 'mine'));
        $this->get('/s/other/posts')->assertForbidden();
        $this->get('/s/other/settings')->assertForbidden();
        $this->post('/s/other/posts', $this->postPayload())->assertForbidden();
        $this->assertSame(0, $other->posts()->count());
    }

    public function test_editor_can_write_posts_and_edit_settings(): void
    {
        $site = $this->site();
        $this->actingAs($this->editor($site));

        $this->post('/s/brand-a/posts', $this->postPayload(['status' => 'published']))->assertRedirect();
        $this->assertSame('published', $site->posts()->first()->status->value);
        $this->put('/s/brand-a/settings', ['settings' => ['phone' => '123']])->assertRedirect();
        $this->assertSame('123', $site->settings()->value('value'));
    }

    public function test_editor_cannot_touch_admin_areas(): void
    {
        $site = $this->site();
        $this->actingAs($this->editor($site));

        $this->get('/s/brand-a/brand')->assertForbidden();
        $this->put('/s/brand-a/brand', ['name' => 'Hacked'])->assertForbidden();
        $this->delete('/s/brand-a/brand')->assertForbidden();
        $this->post('/s/brand-a/rotate-key')->assertForbidden();
        $this->post('/s/brand-a/test-connection')->assertForbidden();
        $this->get('/sites')->assertForbidden();
        $this->post('/sites', [])->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->post('/users', [])->assertForbidden();
        $this->assertSame('Brand-a', $site->fresh()->name);
    }

    public function test_editor_without_brands_sees_a_friendly_page(): void
    {
        $this->actingAs($this->editor());

        $this->get('/')->assertInertia(fn (Assert $page) => $page->component('home/no-brands'));
    }

    public function test_admin_manages_users_and_brand_access(): void
    {
        $site = $this->site();
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post('/users', [
            'name' => 'Writer', 'email' => 'w@example.com', 'password' => 'password1', 'role' => 'editor', 'site_ids' => [$site->id],
        ])->assertRedirect('/users');
        $writer = User::where('email', 'w@example.com')->first();
        $this->assertFalse($writer->isAdmin());
        $this->assertTrue($writer->canAccessSite($site));

        // Password stays when left empty; promoting to admin clears brand assignments.
        $this->put("/users/{$writer->id}", ['name' => 'Writer', 'email' => 'w@example.com', 'password' => '', 'role' => 'admin', 'site_ids' => [$site->id]]);
        $this->assertTrue($writer->fresh()->isAdmin());
        $this->assertSame(0, $writer->sites()->count());
        $this->assertTrue(password_verify('password1', $writer->fresh()->password));

        $this->put("/users/{$admin->id}", ['name' => 'Me', 'email' => $admin->email, 'role' => 'editor'])->assertSessionHasErrors('role');
        $this->delete("/users/{$admin->id}");
        $this->assertModelExists($admin);
        $this->delete("/users/{$writer->id}");
        $this->assertModelMissing($writer);
    }

    public function test_profile_and_password_update(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $this->actingAs($user);

        $this->put('/profile', ['name' => 'New Name', 'email' => 'new@example.com'])->assertRedirect();
        $this->assertSame('New Name', $user->fresh()->name);

        $this->put('/profile/password', ['current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertSessionHasErrors('current_password');
        $this->put('/profile/password', ['current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(password_verify('new-password', $user->fresh()->password));
    }

    public function test_slug_is_unique_per_brand_only(): void
    {
        $this->site('brand-a');
        $this->site('brand-b');
        $this->actingAs($this->admin());

        $this->post('/s/brand-a/posts', $this->postPayload())->assertSessionHasNoErrors();
        $this->post('/s/brand-a/posts', $this->postPayload())->assertSessionHasErrors('slug');
        $this->post('/s/brand-b/posts', $this->postPayload())->assertSessionHasNoErrors();
        $this->post('/s/brand-a/posts', $this->postPayload(['slug' => 'Bad Slug']))->assertSessionHasErrors('slug');
        $this->post('/s/brand-a/posts', $this->postPayload(['slug' => 'x', 'layout' => 'sidebar']))->assertSessionHasErrors('layout');
    }

    public function test_posts_are_scoped_to_their_brand(): void
    {
        $this->site('brand-a');
        $this->site('brand-b');
        $this->actingAs($this->admin());
        $this->post('/s/brand-a/posts', $this->postPayload());
        $id = Post::first()->id;

        $this->get("/s/brand-b/posts/{$id}")->assertNotFound();
        $this->get("/s/brand-a/posts/{$id}")->assertInertia(fn (Assert $page) => $page->component('posts/editor')->where('post.id', $id));
    }

    public function test_blocks_are_normalized_and_sanitized(): void
    {
        $this->site();
        $this->actingAs($this->admin());

        $this->post('/s/brand-a/posts', $this->postPayload([
            'status' => 'published',
            'blocks' => [
                ['type' => 'paragraph', 'html' => '<p onclick="x()">Hi <script>alert(1)</script><a href="javascript:alert(1)">x</a> <a href="https://ok.test" target="_blank">ok</a></p>'],
                ['type' => 'image_text', 'image' => 'java script:alert(1)', 'side' => 'sideways', 'extra' => 'dropped'],
                ['type' => 'faq', 'items' => [['q' => 'Q1', 'a' => 'A1'], ['q' => '', 'a' => 'orphan']]],
            ],
        ]));

        $post = Post::first();
        [$paragraph, $imageText, $faq] = $post->blocks;
        $this->assertSame('<p>Hi <a>x</a> <a href="https://ok.test" target="_blank" rel="noopener noreferrer">ok</a></p>', $paragraph['html']);
        $this->assertSame('', $imageText['image']);
        $this->assertSame('left', $imageText['side']);
        $this->assertArrayNotHasKey('extra', $imageText);
        $this->assertCount(1, $faq['items']);
        $this->assertNotNull($post->published_at);

        $this->post('/s/brand-a/posts', $this->postPayload(['slug' => 'x', 'blocks' => [['type' => 'evil']]]))->assertSessionHasErrors('blocks.0.type');
    }

    public function test_schema_accepts_json_or_script_tags_and_rejects_garbage(): void
    {
        $this->site();
        $this->actingAs($this->admin());

        $this->post('/s/brand-a/posts', $this->postPayload([
            'schema' => '<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage"}</script>'
                .'<script type="application/ld+json">{"@type":"HowTo","name":"</script><script>alert(1)"}</script>',
        ]))->assertSessionHasErrors('schema');

        $this->post('/s/brand-a/posts', $this->postPayload([
            'schema' => '<script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage"}</script>',
        ]))->assertSessionHasNoErrors();
        $this->assertSame(['@context' => 'https://schema.org', '@type' => 'FAQPage'], Post::first()->schema);

        $this->post('/s/brand-a/posts', $this->postPayload(['slug' => 'two', 'schema' => '{not json']))->assertSessionHasErrors('schema');
        $this->post('/s/brand-a/posts', $this->postPayload(['slug' => 'three', 'schema' => '<script>alert(1)</script>']))->assertSessionHasErrors('schema');

        // The brand-wide schema box was retired: a save drops it instead of storing it.
        $site = Site::where('slug', 'brand-a')->firstOrFail();
        $site->settings()->create(['key' => 'schema', 'value' => '{"@type":"Organization"}']);
        $this->put('/s/brand-a/settings', ['settings' => ['schema' => 'nope', 'phone' => '1']])->assertSessionHasNoErrors();
        $this->assertSame(['phone' => '1'], $site->settings()->pluck('value', 'key')->all());
    }

    public function test_post_list_filters_and_counts_by_status(): void
    {
        $this->site();
        $this->actingAs($this->admin());
        $this->post('/s/brand-a/posts', $this->postPayload(['slug' => 'draft']));
        $this->post('/s/brand-a/posts', $this->postPayload(['slug' => 'live', 'status' => 'published']));
        $this->post('/s/brand-a/posts', $this->postPayload([
            'slug' => 'later', 'status' => 'published', 'published_at' => now()->addDay()->toIso8601String(),
        ]));

        $this->get('/s/brand-a/posts')->assertInertia(fn (Assert $page) => $page
            ->where('counts', ['total' => 3, 'draft' => 1, 'published' => 1, 'scheduled' => 1, 'trash' => 0]));
        $this->get('/s/brand-a/posts?status=scheduled')->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 1)->where('posts.data.0.slug', 'later'));
    }

    public function test_saving_content_purges_the_brand_cache(): void
    {
        $site = $this->site();
        $this->actingAs($this->admin());

        $this->post('/s/brand-a/posts', $this->postPayload());
        $this->put('/s/brand-a/settings', ['settings' => ['phone' => '123']]);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://brand-a.test/cms/clear-cache.php'
            && $request->hasHeader('X-Cache-Secret', $site->cache_secret));
    }

    public function test_settings_are_replaced_as_a_set(): void
    {
        $site = $this->site();
        $this->actingAs($this->admin());

        $this->put('/s/brand-a/settings', ['settings' => ['phone' => '123', 'email' => 'a@b.c']]);
        $this->put('/s/brand-a/settings', ['settings' => ['phone' => '456', 'email' => '  ']]);
        $this->assertSame(['phone' => '456'], $site->settings()->pluck('value', 'key')->all());
        $this->put('/s/brand-a/settings', ['settings' => ['Bad-Key' => 'x']])->assertSessionHasErrors('settings');
    }

    public function test_unknown_pages_render_the_friendly_error_page(): void
    {
        $this->actingAs($this->admin());

        $this->get('/s/nope/posts')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('error')->where('status', 404));
    }

    public function test_duplicating_a_post_makes_a_draft_copy_with_a_free_slug(): void
    {
        $site = $this->site();
        $this->actingAs($this->editor($site));
        $this->post('/s/brand-a/posts', $this->postPayload([
            'status' => 'published', 'excerpt' => 'Hi', 'meta_title' => 'SEO',
            'schema' => '{"@type":"FAQPage"}',
            'blocks' => [['id' => 'a', 'type' => 'heading', 'text' => 'Hey', 'level' => 2]],
        ]));
        $original = Post::first();
        // "hello-copy" is kept as a redirect from an earlier rename, so the copy must not take it.
        $site->redirects()->create(['old_slug' => 'hello-copy', 'post_id' => $original->id]);

        $this->post("/s/brand-a/posts/{$original->id}/duplicate")->assertRedirect()->assertInertiaFlash('toast.tone', 'info');
        $this->post("/s/brand-a/posts/{$original->id}/duplicate");

        [$first, $second] = Post::where('id', '!=', $original->id)->orderBy('id')->get();
        $this->assertSame(['hello-copy-2', 'hello-copy-3'], [$first->slug, $second->slug]);
        $this->assertSame('Hello (copy)', $first->title);
        $this->assertSame('draft', $first->status->value);
        $this->assertNull($first->published_at);
        $this->assertSame([$original->blocks, $original->schema, 'SEO'], [$first->blocks, $first->schema, $first->meta_title]);
        $this->assertDatabaseHas('post_redirects', ['old_slug' => 'hello-copy']);

        // Other brands' posts can't be duplicated through this brand.
        $this->site('brand-b');
        $this->post("/s/brand-b/posts/{$original->id}/duplicate")->assertNotFound();
    }

    public function test_expired_session_goes_back_with_a_message(): void
    {
        // The CSRF check is skipped in tests, so raise its error directly.
        Route::middleware('web')->post('/_expired', fn () => abort(419));

        $this->from('/login')->post('/_expired')
            ->assertRedirect('/login')
            ->assertInertiaFlash('toast.tone', 'error');
    }

    public function test_login_is_rate_limited_per_email_not_per_office(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'someone@else.test', 'password' => 'wrong']);
        }
        $this->from('/login')->post('/login', ['email' => 'someone@else.test', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertInertiaFlash('toast.message', 'Too many attempts. Wait a minute and try again.');

        // A colleague on the same IP can still sign in.
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/');
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_editors_manage_authors_and_only_one_is_default(): void
    {
        $site = $this->site();
        $this->actingAs($this->editor($site));

        $this->post('/s/brand-a/authors', ['name' => 'First', 'is_default' => false])->assertRedirect();
        $first = $site->authors()->first();
        $this->assertTrue($first->is_default, 'the first writer becomes the default');

        $this->post('/s/brand-a/authors', ['name' => 'Second', 'bio' => "Line one\nLine two", 'photo' => 'javascript:alert(1)', 'is_default' => true]);
        $second = $site->authors()->where('name', 'Second')->first();
        $this->assertTrue($second->is_default);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertNull($second->photo, 'unsafe photo URLs are dropped');

        $this->put("/s/brand-a/authors/{$first->id}", ['name' => 'First renamed', 'is_default' => true])->assertRedirect();
        $this->assertSame([true, false], [$first->fresh()->is_default, $second->fresh()->is_default]);

        $this->get('/s/brand-a/authors')->assertInertia(fn (Assert $page) => $page->component('authors/index')->has('authors', 2));

        // A post can pick a writer of its own brand only.
        $other = $this->site('brand-b');
        $foreign = $other->authors()->create(['name' => 'Elsewhere']);
        $this->post('/s/brand-a/posts', $this->postPayload(['author_id' => $foreign->id]))->assertSessionHasErrors('author_id');
        $this->post('/s/brand-a/posts', $this->postPayload(['author_id' => $second->id, 'show_author' => false, 'category' => 'Tips']));
        $this->assertDatabaseHas('posts', ['author_id' => $second->id, 'show_author' => false, 'category' => 'Tips']);
        $this->get('/s/brand-b/authors')->assertForbidden();
        $this->delete("/s/brand-a/authors/{$foreign->id}")->assertNotFound();

        $this->delete("/s/brand-a/authors/{$second->id}")->assertRedirect();
        $this->assertDatabaseHas('posts', ['slug' => 'hello', 'author_id' => null]);
    }

    public function test_maintenance_links_are_admin_only(): void
    {
        $this->actingAs($this->editor($this->site()));
        $this->get('/maintenance')->assertForbidden();
        $this->get('/maintenance/storage-link')->assertForbidden();

        $this->actingAs($this->admin());
        $this->get('/maintenance')->assertOk()->assertSee('/maintenance/storage-link');
        $this->get('/maintenance/rm-rf')->assertNotFound();
    }

    public function test_storage_link_replaces_a_folder_left_by_an_upload(): void
    {
        $tmp = sys_get_temp_dir().'/cms-link-'.uniqid();
        mkdir("{$tmp}/public/storage/uploads", 0777, true);
        mkdir("{$tmp}/storage/app/public/uploads", 0777, true);
        file_put_contents("{$tmp}/public/storage/uploads/only-in-upload.png", 'old');
        file_put_contents("{$tmp}/public/storage/uploads/both.png", 'stale copy');
        file_put_contents("{$tmp}/storage/app/public/uploads/both.png", 'real');
        $this->app->usePublicPath("{$tmp}/public");
        $this->app['config']->set('filesystems.links', ["{$tmp}/public/storage" => "{$tmp}/storage/app/public"]);
        $this->app->useStoragePath("{$tmp}/storage");

        $this->actingAs($this->admin());
        $res = $this->get('/maintenance/storage-link');

        if (! @symlink(__FILE__, "{$tmp}/probe")) {
            // No symlinks allowed (e.g. Windows without admin): the uploaded folder must be left untouched.
            $res->assertStatus(500)->assertSee('left as is');
            $this->assertFileExists("{$tmp}/public/storage/uploads/only-in-upload.png");
            $this->markTestSkipped('This OS user cannot create symlinks; checked the safe fallback only.');
        }
        $res->assertOk()->assertSee('kept 1 image');
        $this->assertTrue(is_link("{$tmp}/public/storage"));
        $this->assertSame('old', file_get_contents("{$tmp}/storage/app/public/uploads/only-in-upload.png"));
        $this->assertSame('real', file_get_contents("{$tmp}/storage/app/public/uploads/both.png"), 'existing uploads win');
    }
}
