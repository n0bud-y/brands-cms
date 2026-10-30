<?php

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use App\Support\BrandCache;
use App\Support\BrandImport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('cms:user {email} {--name=} {--role=admin : admin or editor}', function (string $email) {
    $role = UserRole::tryFrom((string) $this->option('role'));
    if (! $role) {
        $this->error('Role must be "admin" or "editor".');

        return 1;
    }

    $password = (string) $this->secret('Password (min 8 characters)');
    if (mb_strlen($password) < 8) {
        $this->error('Password must be at least 8 characters.');

        return 1;
    }

    $user = User::updateOrCreate(
        ['email' => $email],
        ['name' => $this->option('name') ?: Str::before($email, '@'), 'password' => $password, 'role' => $role],
    );

    $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated')." {$role->value} {$email}");
})->purpose('Create a dashboard user, or reset an existing user\'s password');

Artisan::command('cms:import {brand : the brand slug} {file : path to the JSON file}', function (string $brand, string $file) {
    $site = Site::where('slug', $brand)->first();
    $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    if (! $site || ! is_array($data)) {
        $this->error($site ? "Can't read {$file} as JSON." : "No brand with the slug \"{$brand}\".");

        return 1;
    }

    foreach (BrandImport::run($site, $data) as $what => $n) {
        $this->line("  ✓ {$n} {$what}");
    }
    $this->info(BrandCache::purge($site) ? 'Imported. The website cache is cleared.' : 'Imported. The website cache clears itself within 10 minutes.');
})->purpose('Load a brand\'s posts, authors, services, locations, pages and settings from a JSON file');
