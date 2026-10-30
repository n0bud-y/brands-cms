<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Custom JSON-LD added by the SEO team, output in the post's <head>. */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('schema')->nullable()->after('og_image');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('schema');
        });
    }
};
