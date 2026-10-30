<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** Props every page receives: the signed-in user and the brands they may switch between. */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ] : null,
            ],
            'sites' => fn () => $user
                ? $user->accessibleSites()->orderBy('name')->get(['id', 'name', 'slug', 'url', 'is_active'])
                : [],
        ];
    }
}
