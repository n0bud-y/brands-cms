<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One page of a brand website and its SEO. Empty fields keep what the page file itself says (found_*). */
class Page extends Model
{
    protected $fillable = ['path', 'found_title', 'found_description', 'meta_title', 'meta_description', 'og_image', 'noindex'];

    protected function casts(): array
    {
        return ['noindex' => 'boolean'];
    }

    /** What the website gets: only what the panel changes. */
    public function toSeo(): array
    {
        return array_filter([
            'title' => $this->meta_title,
            'description' => $this->meta_description,
            'image' => $this->og_image,
            'noindex' => $this->noindex ?: null,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
