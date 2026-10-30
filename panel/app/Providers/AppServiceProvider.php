<?php

namespace App\Providers;

use App\Models\Site;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Admins manage brands, website connections and users.
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Editors may only work on brands an admin assigned to them.
        Gate::define('access-site', fn (User $user, Site $site) => $user->canAccessSite($site));

        // Per email + IP, so one office sharing an IP doesn't lock everyone out.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(6)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));

        // A public/hot file uploaded by mistake would make live pages load scripts from a developer's
        // machine (blank page). In production, always serve the built files from public/build.
        if ($this->app->isProduction()) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }

        // Catch N+1 queries while developing; on the live server, refuse migrate:fresh and friends.
        Model::preventLazyLoading(! $this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
