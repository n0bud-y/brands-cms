<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A writer profile for one brand. The default one is used by every post that doesn't pick its own. */
class Author extends Model
{
    protected $fillable = ['name', 'role', 'photo', 'bio', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** What the brand site shows in the author box. */
    public function toCard(): array
    {
        return $this->only(['name', 'role', 'photo', 'bio']);
    }
}
