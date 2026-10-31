<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Service;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocationTest extends TestCase
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

        $this->site = new Site(['name' => 'Inkwell', 'slug' => 'brand-a', 'url' => 'https://brand-a.test']);
        $this->key = $this->site->rotateApiKey();
        $this->site->rotateCacheSecret();
        $this->site->save();
    }

    private function brandGet(string $path)
    {
        return $this->getJson("/api/v1/brand-a{$path}", ['X-Api-Key' => $this->key]);
    }

    /** California with Los Angeles, and a Ghostwriting service. */
    private function places(): array
    {
        $ca = $this->site->locations()->create(['name' => 'California', 'slug' => 'california']);
        $la = $this->site->locations()->create(['name' => 'Los Angeles', 'slug' => 'los-angeles', 'parent_id' => $ca->id]);
        $ghost = $this->site->services()->create([
            'name' => 'Ghostwriting', 'slug' => 'ghostwriting', 'summary' => 'Ghostwriters in {location}.',
            'blocks' => [['id' => 'a1', 'type' => 'paragraph', 'html' => '<p>We write in {location}, {state}.</p>']],
            'faqs' => [['q' => 'Do you work in {location}?', 'a' => 'Yes.']],
        ]);

        return [$ca, $la, $ghost];
    }

    public function test_map_lists_every_page_url(): void
    {
        [$ca, $la, $ghost] = $this->places();

        $this->brandGet('/locations')->assertOk()
            ->assertJsonPath('paths', [
                'california' => "l{$ca->id}",
                'california/los-angeles' => "l{$la->id}",
                'california-ghostwriting' => "l{$ca->id}s{$ghost->id}",
                'california/los-angeles-ghostwriting' => "l{$la->id}s{$ghost->id}",
            ])
            ->assertJsonPath('menu.0.name', 'California')
            ->assertJsonPath('menu.0.cities.0.path', 'california/los-angeles');
    }

    public function test_inactive_places_and_services_have_no_pages(): void
    {
        [$ca, $la, $ghost] = $this->places();
        $ghost->update(['is_active' => false]);

        $this->brandGet('/locations')->assertJsonCount(2, 'paths');

        $ca->update(['is_active' => false]);
        // Switching a state off takes its cities with it.
        $this->brandGet('/locations')->assertJsonPath('paths', [])->assertJsonPath('menu', []);
        $this->brandGet("/locations/l{$la->id}")->assertNotFound();
    }

    public function test_city_page_fills_placeholders_and_lists_services_and_cities(): void
    {
        [$ca, $la, $ghost] = $this->places();
        $this->site->locations()->create(['name' => 'San Diego', 'slug' => 'san-diego', 'parent_id' => $ca->id]);

        $this->brandGet("/locations/l{$la->id}")->assertOk()
            ->assertJsonPath('type', 'city')
            ->assertJsonPath('heading', 'Inkwell in Los Angeles')
            ->assertJsonPath('services.0.summary', 'Ghostwriters in Los Angeles.')
            ->assertJsonPath('services.0.path', 'california/los-angeles-ghostwriting')
            ->assertJsonPath('cities_title', 'Cities we serve in California')
            ->assertJsonPath('cities', [['name' => 'San Diego', 'image' => null, 'path' => 'california/san-diego']])
            ->assertJsonPath('breadcrumbs.1.name', 'Los Angeles');
    }

    public function test_service_page_uses_the_template_until_customized(): void
    {
        [$ca, $la, $ghost] = $this->places();
        $key = "l{$la->id}s{$ghost->id}";

        $this->brandGet("/locations/{$key}")->assertOk()
            ->assertJsonPath('type', 'service')
            ->assertJsonPath('title', 'Ghostwriting in Los Angeles')
            ->assertJsonPath('blocks.0.html', '<p>We write in Los Angeles, California.</p>')
            ->assertJsonPath('faqs.0.q', 'Do you work in Los Angeles?')
            ->assertJsonPath('meta_title', 'Ghostwriting in Los Angeles | Inkwell');

        $la->pages()->create(['service_id' => $ghost->id, 'title' => 'LA ghostwriters', 'blocks' => []]);

        $this->brandGet("/locations/{$key}")->assertJsonPath('title', 'LA ghostwriters')->assertJsonPath('blocks', []);
    }

    public function test_names_are_escaped_inside_rich_text(): void
    {
        [$ca, $la, $ghost] = $this->places();
        $la->update(['name' => '<b>LA</b>']);

        $this->brandGet("/locations/l{$la->id}s{$ghost->id}")
            ->assertJsonPath('blocks.0.html', '<p>We write in &lt;b&gt;LA&lt;/b&gt;, California.</p>')
            // Plain-text fields stay raw; the brand site escapes them.
            ->assertJsonPath('title', 'Ghostwriting in <b>LA</b>');
    }

    public function test_renaming_a_slug_redirects_the_old_urls(): void
    {
        [$ca, $la, $ghost] = $this->places();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put("/s/brand-a/locations/{$ca->id}", ['name' => 'California', 'slug' => 'ca'])->assertSessionHasNoErrors();

        $this->brandGet('/locations')
            ->assertJsonPath('redirects.california', 'ca')
            ->assertJsonPath('redirects.california/los-angeles', 'ca/los-angeles')
            ->assertJsonPath('redirects.california-ghostwriting', 'ca-ghostwriting');

        // Taking the old slug back makes it live again, without a redirect.
        $this->put("/s/brand-a/locations/{$ca->id}", ['name' => 'California', 'slug' => 'california']);
        $this->brandGet('/locations')->assertJsonMissingPath('redirects.california')->assertJsonPath('redirects.ca', 'california');
    }

    public function test_page_can_get_its_own_slug_or_be_hidden(): void
    {
        [$ca, $la, $ghost] = $this->places();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $url = "/s/brand-a/locations/{$la->id}/services/{$ghost->id}";

        $this->put($url, ['slug' => 'la-ghostwriters'])->assertSessionHasNoErrors();
        $this->brandGet('/locations')
            ->assertJsonPath('paths.california/la-ghostwriters', "l{$la->id}s{$ghost->id}")
            ->assertJsonPath('redirects.california/los-angeles-ghostwriting', 'california/la-ghostwriters');

        // Another page's URL can't be taken.
        $this->put($url, ['slug' => 'los-angeles'])->assertSessionHasErrors('slug');

        $this->put($url, ['is_hidden' => true]);
        $this->brandGet("/locations/l{$la->id}")->assertJsonPath('services', []);

        // Resetting removes the row and brings the default URL back.
        $this->delete($url);
        $this->assertDatabaseCount('location_pages', 0);
        $this->brandGet('/locations')->assertJsonPath('paths.california/los-angeles-ghostwriting', "l{$la->id}s{$ghost->id}");
    }

    public function test_dashboard_creates_states_cities_and_services(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/s/brand-a/locations', ['name' => 'Texas', 'slug' => 'texas', 'is_active' => true])->assertSessionHasNoErrors();
        $tx = Location::where('slug', 'texas')->firstOrFail();
        $this->post('/s/brand-a/locations', ['name' => 'Austin', 'slug' => 'austin', 'parent_id' => $tx->id])->assertSessionHasNoErrors();
        // City slugs are unique per state; state slugs among states.
        $this->post('/s/brand-a/locations', ['name' => 'Austin', 'slug' => 'austin', 'parent_id' => $tx->id])->assertSessionHasErrors('slug');
        $this->post('/s/brand-a/locations', ['name' => 'Texas 2', 'slug' => 'texas'])->assertSessionHasErrors('slug');

        $this->post('/s/brand-a/services', ['name' => 'Editing', 'slug' => 'editing', 'faqs' => [['q' => 'Q?', 'a' => 'A'], ['q' => '']]])
            ->assertSessionHasNoErrors();
        $this->assertSame([['q' => 'Q?', 'a' => 'A']], Service::firstOrFail()->faqs);

        $this->get('/s/brand-a/locations')->assertInertia(fn (Assert $page) => $page
            ->component('locations/index')->where('states.0.name', 'Texas')->where('states.0.cities.0.name', 'Austin'));
        $this->get("/s/brand-a/locations/{$tx->id}")->assertInertia(fn (Assert $page) => $page
            ->component('locations/editor')->where('pages.0.path', 'texas-editing'));
    }

    public function test_a_state_with_cities_cannot_become_a_city(): void
    {
        [$ca] = $this->places();
        $tx = $this->site->locations()->create(['name' => 'Texas', 'slug' => 'texas']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put("/s/brand-a/locations/{$ca->id}", ['name' => 'California', 'slug' => 'california', 'parent_id' => $tx->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_defaults_change_every_page(): void
    {
        [$ca] = $this->places();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->put('/s/brand-a/locations/defaults', ['heading' => 'Book help in {location}', 'faqs' => [['q' => 'Where in {state}?', 'a' => 'All of it.']]])
            ->assertSessionHasNoErrors();

        $this->brandGet("/locations/l{$ca->id}")
            ->assertJsonPath('heading', 'Book help in California')
            ->assertJsonPath('faqs.0.q', 'Where in California?')
            // Empty fields keep the built-in text.
            ->assertJsonPath('services_title', 'Our services in California');
    }

    public function test_editors_only_reach_their_own_brands(): void
    {
        [$ca] = $this->places();
        $other = new Site(['name' => 'B', 'slug' => 'brand-b', 'url' => 'https://b.test']);
        $other->rotateApiKey();
        $other->rotateCacheSecret();
        $other->save();
        $editor = User::factory()->create(['role' => 'editor']);
        $editor->sites()->attach($other->id);
        $this->actingAs($editor);

        $this->get('/s/brand-a/locations')->assertForbidden();
        $this->get('/s/brand-b/locations')->assertOk();
        // A location of brand A can't be reached through brand B.
        $this->get("/s/brand-b/locations/{$ca->id}")->assertNotFound();
    }
}
