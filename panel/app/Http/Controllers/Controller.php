<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    /**
     * Flash a snackbar for the dashboard to show after the next page load.
     * $action adds a link button ({label, href}); extra keys ride along (e.g. a one-time API key).
     */
    protected function toast(string $message, string $tone = 'success', array $extra = [], ?array $action = null): void
    {
        Inertia::flash(['toast' => array_filter(compact('message', 'tone', 'action')), ...$extra]);
    }
}
