<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One of a brand's services. Its text is a template filled in for every state and city ({location}, {service}…). */
class Service extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'summary', 'title', 'blocks', 'faqs', 'meta_title', 'meta_description', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['blocks' => 'array', 'faqs' => 'array', 'is_active' => 'boolean'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(LocationPage::class);
    }
}
