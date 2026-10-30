<?php

use App\Enums\UserRole;
use App\Models\User;
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
