<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Theme and site code (Settings), Pages & SEO, the /locations hub texts, and cms:import. */
class SiteToolsTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();
        $this->withoutVite();
        Http::fake(['*/cms/clear-cache.php' => Http::response(['ok' => true])]);

        $this->site = new Site(['name' => 'Inkwell', 'slug' => 'brand-a', 'url' => 'https://brand-a.test']);
        $this->key = $this->site->rotateApiKey();
        $this->site->rotateCacheSecret();
        $this->site->save();
    }

    private function brandGet(string $path)
    {
        return $this->getJson("/api/v1/brand-a{$path}", ['X-Api-Key' => $this->key]);
    }

    private function editor(): User
    {
        $user = User::factory()->create(['role' => 'editor']);
        $user->sites()->attach($this->site->id);

        return $user;
    }

    public function test_theme_values_must_be_colours_and_font_names(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put('/s/brand-a/settings', ['settings' => ['theme_primary' => 'red']])->assertSessionHasErrors('settings.theme_primary');
        $this->put('/s/brand-a/settings', ['settings' => ['font_heading' => "Arial'}</style>"]])->assertSessionHasErrors('settings.font_heading');
        $this->put('/s/brand-a/settings', ['settings' => ['theme_primary' => '#140600', 'font_heading' => 'Lexend Deca', 'font_google' => '1']])
            ->assertSessionHasNoErrors();

        $this->brandGet('/settings')->assertJsonPath('settings.theme_primary', '#140600')->assertJsonPath('settings.font_heading', 'Lexend Deca');
    }

    public function test_only_admins_see_and_change_site_code(): void
    {
        $this->site->settings()->create(['key' => 'code_head', 'value' => '<script>gtag()</script>']);

        $this->actingAs($this->editor());
        $this->get('/s/brand-a/settings')->assertInertia(fn (Assert $page) => $page
            ->where('can_edit_code', false)->missing('settings.code_head'));

        // An editor's save neither changes nor wipes the code.
        $this->put('/s/brand-a/settings', ['settings' => ['phone' => '+1 555', 'code_head' => '<script>evil()</script>', 'code_footer' => 'x']]);
        $this->assertSame(['code_head' => '<script>gtag()</script>', 'phone' => '+1 555'], $this->site->settings()->orderBy('key')->pluck('value', 'key')->all());

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put('/s/brand-a/settings', ['settings' => ['code_head' => '<script>ga()</script>']])->assertSessionHasNoErrors();
        $this->brandGet('/settings')->assertJsonPath('settings.code_head', '<script>ga()</script>');
    }

    public function test_find_pages_adds_the_website_pages(): void
    {
        Http::fake(['*/cms/pages.php' => Http::response(['ok' => true, 'pages' => [
            ['path' => '/', 'title' => 'Home | Inkwell', 'description' => 'Welcome'],
            ['path' => '/about', 'title' => 'About us', 'description' => ''],
        ]])]);
        $this->actingAs($this->editor());
        $this->site->pages()->create(['path' => '/about', 'meta_title' => 'Kept']);

        $this->post('/s/brand-a/pages/discover')->assertSessionHasNoErrors();

        $this->assertSame(['/', '/about'], $this->site->pages()->orderBy('path')->pluck('path')->all());
        $about = $this->site->pages()->where('path', '/about')->first();
        $this->assertSame(['About us', 'Kept'], [$about->found_title, $about->meta_title]);
        Http::assertSent(fn ($request) => $request->url() === 'https://brand-a.test/cms/pages.php' && $request->hasHeader('X-Cache-Secret'));
    }

    public function test_page_seo_reaches_the_website(): void
    {
        $this->actingAs($this->editor());
        $this->post('/s/brand-a/pages', ['path' => 'thank-you.php'])->assertSessionHasNoErrors();
        $this->post('/s/brand-a/pages', ['path' => '/thank-you'])->assertSessionHasErrors('path');
        $page = $this->site->pages()->firstOrFail();
        $this->assertSame('/thank-you', $page->path);

        $this->put("/s/brand-a/pages/{$page->id}", ['meta_title' => 'Thanks!', 'noindex' => true])->assertSessionHasNoErrors();

        // Only what the panel changes is sent; untouched pages are left out.
        $this->site->pages()->create(['path' => '/about', 'found_title' => 'About']);
        $this->brandGet('/settings')->assertJsonPath('pages', ['/thank-you' => ['title' => 'Thanks!', 'noindex' => true]]);
    }

    public function test_locations_api_has_the_hub_texts(): void
    {
        $this->brandGet('/locations')->assertJsonPath('hub.heading', 'Locations we serve')
            ->assertJsonPath('hub.text', 'Inkwell works with authors across the country. Pick your state or city to see our services near you.');
    }

    public function test_feature_section_summaries_and_hub_intro_reach_the_website(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $tx = $this->site->locations()->create(['name' => 'Texas', 'slug' => 'texas', 'summary' => 'Authors in {state} love {brand}.', 'image' => 'https://x.test/tx.webp']);
        $this->post('/s/brand-a/services', ['name' => 'Editing', 'slug' => 'editing', 'feature' => [
            'title' => 'Editing in {location}', 'html' => '<p>Hi<script>x</script> {location}</p>', 'image' => 'javascript:alert(1)', 'alt' => '',
        ]])->assertSessionHasNoErrors();
        $this->put('/s/brand-a/locations/defaults', ['hub_intro_title' => 'All of {brand}', 'hub_intro' => '<p>Hello<script>x</script></p>'])->assertSessionHasNoErrors();

        $key = $this->brandGet('/locations')
            ->assertJsonPath('menu.0.summary', 'Authors in Texas love Inkwell.')
            ->assertJsonPath('menu.0.image', 'https://x.test/tx.webp')
            ->assertJsonPath('hub.intro', ['title' => 'All of Inkwell', 'html' => '<p>Hello</p>', 'image' => ''])
            ->json('paths.texas-editing');
        $this->brandGet("/locations/{$key}")->assertJsonPath('feature', ['title' => 'Editing in Texas', 'html' => '<p>Hi Texas</p>', 'image' => '', 'alt' => '']);

        // A page can have its own section; an empty one means "use the service's".
        $this->put("/s/brand-a/locations/{$tx->id}/services/".substr($key, strpos($key, 's') + 1), ['feature' => ['title' => 'Own', 'html' => '', 'image' => '']]);
        $this->brandGet("/locations/{$key}")->assertJsonPath('feature.title', 'Own');
    }

    public function test_import_loads_a_brand_and_can_run_again(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($file, json_encode([
            'settings' => ['phone' => '+1 555'],
            'authors' => [['name' => 'Olivia', 'is_default' => true]],
            'posts' => [['title' => 'Hello', 'slug' => 'hello', 'author' => 'Olivia', 'published_at' => '2026-03-01',
                'blocks' => [['type' => 'paragraph', 'html' => '<p>Hi<script>x</script></p>']]]],
            'services' => [['name' => 'Editing', 'slug' => 'editing', 'faqs' => [['q' => 'Q?', 'a' => 'A']]]],
            'locations' => [['name' => 'Texas', 'slug' => 'texas', 'cities' => [['name' => 'Austin', 'slug' => 'austin']]]],
            'pages' => [['path' => '/thank-you', 'noindex' => true]],
        ]));

        $this->artisan('cms:import', ['brand' => 'brand-a', 'file' => $file])->assertSuccessful();
        $this->artisan('cms:import', ['brand' => 'brand-a', 'file' => $file])->assertSuccessful();
        unlink($file);

        $post = $this->site->posts()->firstOrFail();
        $this->assertSame(1, $this->site->posts()->count());
        $this->assertSame('<p>Hi</p>', $post->blocks[0]['html']);
        $this->assertSame('Olivia', $post->author->name);
        $this->brandGet('/locations')->assertJsonPath('paths.texas/austin-editing', fn ($key) => str_starts_with($key, 'l'));
        $this->brandGet('/settings')->assertJsonPath('settings.phone', '+1 555')->assertJsonPath('pages./thank-you.noindex', true);
        $this->artisan('cms:import', ['brand' => 'nope', 'file' => 'x'])->assertFailed();
    }
}
