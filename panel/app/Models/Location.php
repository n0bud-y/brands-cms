<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A state (no parent) or a city (parent = its state). Each gets its own page plus one page per service. */
class Location extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'heading', 'subheading', 'banner_image', 'blocks', 'faqs',
        'meta_title', 'meta_description', 'sort', 'is_active',
    ];

    protected function casts(): array
    {
        return ['blocks' => 'array', 'faqs' => 'array', 'is_active' => 'boolean'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(LocationPage::class);
    }

    public function isCity(): bool
    {
        return $this->parent_id !== null;
    }
}
