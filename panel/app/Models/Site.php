<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'url',
        'cache_clear_url',
        'available_layouts',
        'is_active',
    ];

    protected $hidden = [
        'api_key_hash',
        'cache_secret',
        // Sent only to the location pages that edit it.
        'location_defaults',
    ];

    protected $attributes = [
        'available_layouts' => '["classic"]',
    ];

    protected function casts(): array
    {
        return [
            'available_layouts' => 'array',
            'location_defaults' => 'array',
            'cache_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(PostRedirect::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort')->orderBy('name');
    }

    /** States and cities; a state has no parent. */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /** The website's own pages and their SEO. */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function locationRedirects(): HasMany
    {
        return $this->hasMany(LocationRedirect::class);
    }

    /** Editors assigned to this brand. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Issue a new API key. Only its hash is stored, so the returned
     * plain key must be shown to the admin now or never.
     */
    public function rotateApiKey(): string
    {
        $key = Str::random(48);
        $this->api_key_hash = hash('sha256', $key);

        return $key;
    }

    public function checkApiKey(?string $key): bool
    {
        return $key !== null && hash_equals($this->api_key_hash, hash('sha256', $key));
    }

    public function rotateCacheSecret(): string
    {
        return $this->cache_secret = Str::random(48);
    }

    public function cacheClearUrl(): string
    {
        return $this->cache_clear_url ?: rtrim($this->url, '/').'/cms/clear-cache.php';
    }
}
