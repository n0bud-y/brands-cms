<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Image uploads from the editor. Returns JSON (not an Inertia page) so fields can fill in place. */
class UploadController extends Controller
{
    public static function directory(Site $site): string
    {
        return "uploads/{$site->id}";
    }

    public function store(Request $request, Site $site)
    {
        $request->validate([
            // No SVG: it can carry scripts and brand sites serve these files as-is.
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,avif', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->storeAs(
            self::directory($site).'/'.now()->format('Y/m'),
            Str::lower(Str::random(20)).'.'.$file->extension(),
            'public',
        );

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
