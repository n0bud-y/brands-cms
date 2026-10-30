<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SEO for a brand website's own pages (/, /about…): the panel's title/description win over the page file's.
     * found_* is what the page file itself says, read by "Find pages", shown as the fallback.
     */
    public function up(): void
    {
        Schema::dropIfExists('pages');

        Schema::create('pages', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('found_title')->nullable();
            $table->string('found_description', 500)->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('og_image', 2048)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->unique(['site_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
