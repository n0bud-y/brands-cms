<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Slugs
{
    /** "{base}", "{base}-2", "{base}-3"… the first one not in $taken, at most $max characters. */
    public static function free(string $base, iterable $taken, int $max = 190): string
    {
        $base = rtrim(Str::limit($base, $max - 4, ''), '-');
        $taken = array_flip(is_array($taken) ? $taken : iterator_to_array($taken));
        for ($slug = $base, $n = 2; isset($taken[$slug]); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }
}
