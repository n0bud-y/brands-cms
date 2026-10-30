<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /** "/" opens the brand used last, else the first one the user may access. */
    public function __invoke(Request $request)
    {
        $sites = $request->user()->accessibleSites();
        $site = (clone $sites)->where('slug', $request->session()->get('last_site'))->first()
            ?? $sites->orderBy('name')->first();

        if ($site) {
            return redirect("/s/{$site->slug}/posts");
        }

        return $request->user()->isAdmin() ? redirect('/sites') : Inertia::render('home/no-brands');
    }
}
