<?php

namespace App\Http\Middleware;

use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Guards the public brand API: the {site} must be active and the X-Api-Key must match. */
class AuthenticateSite
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = $request->route('site');

        if (! $site instanceof Site || ! $site->is_active || ! $site->checkApiKey($request->header('X-Api-Key'))) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
