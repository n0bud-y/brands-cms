<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Writer profiles per brand, shown in a box under each post; plus each post's writer and category.
     *
     * Safe to re-run after a failed attempt: some hosts create new tables as MyISAM, which made the foreign key
     * fail after `authors` and the post columns already existed.
     */
    public function up(): void
    {
        // Only ever a leftover from a failed run (nothing references it yet), so start it clean as InnoDB.
        Schema::dropIfExists('authors');

        Schema::create('authors', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('photo', 2048)->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'author_id')) {
                $table->foreignId('author_id')->nullable()->after('layout');
            }
            if (! Schema::hasColumn('posts', 'show_author')) {
                $table->boolean('show_author')->default(true)->after('author_id');
            }
            if (! Schema::hasColumn('posts', 'category')) {
                $table->string('category', 80)->nullable()->after('show_author');
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('author_id')->references('id')->on('authors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn(['show_author', 'category']);
        });
        Schema::dropIfExists('authors');
    }
};
