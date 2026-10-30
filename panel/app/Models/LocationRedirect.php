<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** An old location URL and the page ("l5" or "l5s3") it now 301s to. */
class LocationRedirect extends Model
{
    protected $fillable = ['site_id', 'path', 'target'];
}
