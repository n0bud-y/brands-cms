<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role' => 'editor',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /** Brands assigned to this user (only meaningful for editors). */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** @return Builder<Site> */
    public function accessibleSites(): Builder
    {
        return $this->isAdmin() ? Site::query() : Site::whereHas('users', fn ($q) => $q->whereKey($this->id));
    }

    public function canAccessSite(Site $site): bool
    {
        return $this->isAdmin() || $this->sites()->whereKey($site->id)->exists();
    }
}
