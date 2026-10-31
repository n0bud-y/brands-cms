<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Locations get an image (a state's or city's map picture on /locations and the state page) and a short summary
     * (under the state on /locations). Services and their per-place pages get a "feature" section: text left,
     * image right, above the FAQs.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'image')) {
                $table->string('image', 2048)->nullable()->after('banner_image');
            }
            if (! Schema::hasColumn('locations', 'summary')) {
                $table->text('summary')->nullable()->after('image');
            }
        });
        foreach (['services', 'location_pages'] as $name) {
            if (! Schema::hasColumn($name, 'feature')) {
                Schema::table($name, fn (Blueprint $table) => $table->json('feature')->nullable()->after('blocks'));
            }
        }
    }

    public function down(): void
    {
        Schema::table('locations', fn (Blueprint $table) => $table->dropColumn(['image', 'summary']));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('feature'));
        Schema::table('location_pages', fn (Blueprint $table) => $table->dropColumn('feature'));
    }
};
