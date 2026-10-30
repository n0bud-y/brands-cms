<?php

use App\Http\Middleware\AuthenticateSite;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->alias(['site.key' => AuthenticateSite::class]);
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Show 403/404 (and 500/503 in production) as a friendly dashboard page instead of Laravel's default.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();
            $friendly = [403, 404, ...(app()->hasDebugModeEnabled() ? [] : [500, 503])];

            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            // Expired CSRF token (tab left open too long) or too many login tries: go back and say so.
            $retry = [
                419 => 'Your session expired. Please try that again.',
                429 => 'Too many attempts. Wait a minute and try again.',
            ];
            if (isset($retry[$status])) {
                Inertia::flash('toast', ['message' => $retry[$status], 'tone' => 'error']);

                return redirect()->back();
            }

            if (! in_array($status, $friendly, true)) {
                return $response;
            }

            return Inertia::render('error', ['status' => $status])->toResponse($request)->setStatusCode($status);
        });
    })->create();
