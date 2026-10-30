<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'site_id',
        'key',
        'value',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
