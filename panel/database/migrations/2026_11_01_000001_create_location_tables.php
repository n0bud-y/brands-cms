<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Location pages: a brand's services, its states and cities, and the state/city × service pages between them.
     * Service text is a template ({location}, {service}…); a location_pages row only exists when one page differs.
     */
    public function up(): void
    {
        // Re-runnable after a half-failed run (see the authors migration): nothing references these yet.
        Schema::dropIfExists('location_redirects');
        Schema::dropIfExists('location_pages');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('services');

        Schema::create('services', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 190);
            $table->string('icon', 2048)->nullable();
            $table->text('summary')->nullable();
            $table->string('title')->nullable();
            $table->json('blocks')->nullable();
            $table->json('faqs')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            // null = a state; set = a city in that state.
            $table->foreignId('parent_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 190);
            $table->string('heading')->nullable();
            $table->text('subheading')->nullable();
            $table->string('banner_image', 2048)->nullable();
            $table->json('blocks')->nullable();
            // null = the brand's default FAQs.
            $table->json('faqs')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['site_id', 'parent_id', 'sort']);
        });

        Schema::create('location_pages', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 190)->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->string('title')->nullable();
            // null = the service's template.
            $table->json('blocks')->nullable();
            $table->json('faqs')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'service_id']);
        });

        // Old URL -> page key ("l5" or "l5s3"), so renamed slugs keep working with a 301.
        Schema::create('location_redirects', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('target', 32);
            $table->timestamps();

            $table->unique(['site_id', 'path']);
        });

        if (! Schema::hasColumn('sites', 'location_defaults')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->json('location_defaults')->nullable()->after('available_layouts');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_redirects');
        Schema::dropIfExists('location_pages');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('services');
        Schema::table('sites', fn (Blueprint $table) => $table->dropColumn('location_defaults'));
    }
};
