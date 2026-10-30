<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** How one location's page for one service differs from the service template (slug, text, hidden). */
class LocationPage extends Model
{
    protected $fillable = ['location_id', 'service_id', 'slug', 'is_hidden', 'title', 'blocks', 'faqs', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return ['blocks' => 'array', 'faqs' => 'array', 'is_hidden' => 'boolean'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
