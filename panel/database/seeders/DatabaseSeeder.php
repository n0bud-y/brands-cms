<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Local demo data. Brand A points at the brand-kit dev server (http://localhost:8080). */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password', 'role' => 'admin'],
        );

        if (Site::where('slug', 'brand-a')->exists()) {
            return;
        }

        $site = new Site([
            'name' => 'Brand A',
            'slug' => 'brand-a',
            'url' => 'http://localhost:8080',
            'available_layouts' => ['classic', 'hero', 'sidebar', 'minimal'],
        ]);
        $apiKey = $site->rotateApiKey();
        $cacheSecret = $site->rotateCacheSecret();
        $site->save();

        $site->settings()->createMany(collect([
            'email' => 'hello@brand-a.test',
            'phone' => '+1 555 0100',
            'whatsapp' => '+15550100',
            'address' => '1 Example Street, Springfield',
            'facebook' => 'https://facebook.com/',
            'instagram' => 'https://instagram.com/',
            'footer_text' => '© Brand A. All rights reserved.',
            'blog_title' => 'Brand A Blog',
            'blog_description' => 'Guides and stories about writing and publishing books.',
        ])->map(fn ($value, $key) => compact('key', 'value'))->values()->all());

        $site->posts()->create([
            'title' => 'How to publish your first book',
            'slug' => 'how-to-publish-your-first-book',
            'layout' => 'classic',
            'excerpt' => 'A practical walkthrough from manuscript to bookstore shelf.',
            'status' => 'published',
            'published_at' => now(),
            'blocks' => [
                ['id' => 'b1', 'type' => 'heading', 'text' => 'Why self-publish?', 'level' => 2],
                ['id' => 'b2', 'type' => 'paragraph', 'html' => '<p>Self-publishing gives you <strong>full control</strong> over your book, your rights and your royalties.</p>'],
                ['id' => 'b3', 'type' => 'quote', 'text' => 'The best time to publish was yesterday. The second best is today.', 'cite' => 'Every editor ever'],
                ['id' => 'b4', 'type' => 'faq', 'items' => [
                    ['q' => 'How long does it take?', 'a' => 'Usually 8 to 12 weeks from final manuscript.'],
                    ['q' => 'Do I keep my rights?', 'a' => 'Yes, always.'],
                ]],
                ['id' => 'b5', 'type' => 'cta', 'title' => 'Ready to publish?', 'text' => 'Talk to our team about your manuscript.', 'button' => 'Get a quote', 'url' => '/contact'],
            ],
        ]);

        $this->command?->info("brand-a API key:      {$apiKey}");
        $this->command?->info("brand-a cache secret: {$cacheSecret}");
    }
}
