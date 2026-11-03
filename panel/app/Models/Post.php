<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    // Deleting moves a post to the trash; forceDelete() removes it for good.
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'layout',
        'author_id',
        'show_author',
        'category',
        'cover_image',
        'excerpt',
        'blocks',
        'meta_title',
        'meta_description',
        'og_image',
        'schema',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'show_author' => 'boolean',
            'schema' => 'array',
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (! $post->isDirty('slug')) {
                return;
            }

            // A live slug always wins over a redirect left behind by a rename.
            PostRedirect::where('site_id', $post->site_id)->where('old_slug', $post->slug)->delete();

            // Keep old URLs alive: a renamed slug becomes a 301 to the new one.
            if ($post->exists) {
                PostRedirect::updateOrCreate(
                    ['site_id' => $post->site_id, 'old_slug' => $post->getOriginal('slug')],
                    ['post_id' => $post->id],
                );
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(PostRedirect::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published)->where('published_at', '<=', now());
    }
}
