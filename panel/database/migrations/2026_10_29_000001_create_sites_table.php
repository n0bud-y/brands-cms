<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 64)->unique();
            $table->string('url'); // base URL incl. scheme, e.g. https://brand-a.com
            // sha256 of the key brand sites send to the read API; the plain key is shown once.
            $table->char('api_key_hash', 64)->unique();
            // Sent by the panel to the brand site's clear-cache.php (stored encrypted).
            $table->text('cache_secret');
            $table->string('cache_clear_url')->nullable();
            $table->json('available_layouts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
