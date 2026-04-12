<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
 
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
 
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
        'is_active',
        'last_login_at',
        'two_factor_secret',
        'two_factor_confirmed_at',
    ];
 
    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];
 
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
        'last_login_at'     => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
    ];
 
    // ── Relationships ─────────────────────────────────────────────────────
 
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
 
    // ── Helpers ───────────────────────────────────────────────────────────
 
    /**
     * Get the employee's role — falls back to 'staff' if no employee record.
     */
    public function getRoleAttribute(): string
    {
        if ($this->relationLoaded('roles') && $this->roles->isNotEmpty()) {
            return $this->roles->first()->slug;
        }

        return $this->getRawOriginal('role')
            ?? $this->employee?->role
            ?? 'staff';
    }
 
    /**
     * Get display name from employee record or fall back to user name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->employee
            ? $this->employee->first_name . ' ' . $this->employee->last_name
            : $this->name;
    }
 
    public function getInitialsAttribute(): string
    {
        $name = $this->display_name;
        return collect(explode(' ', $name))
            ->take(2)
            ->map(fn($w) => strtoupper($w[0] ?? ''))
            ->implode('');
    }

    public function getTwoFactorEnabledAttribute(): bool
    {
        return ! empty($this->two_factor_secret) && ! empty($this->two_factor_confirmed_at);
    }

    public function getAllPermissionsAttribute()
    {
        return $this->effectivePermissions();
    }

    public function effectivePermissions()
    {
        $rolePermissions = $this->relationLoaded('roles')
            ? $this->roles->loadMissing('permissions')->pluck('permissions')->flatten(1)
            : $this->roles()->with('permissions')->get()->pluck('permissions')->flatten(1);

        $directPermissions = $this->relationLoaded('directPermissions')
            ? $this->directPermissions
            : $this->directPermissions()->get();

        return $rolePermissions
            ->merge($directPermissions)
            ->unique('id')
            ->sortBy('group_name')
            ->values();
    }

    public function permissionSlugs(): array
    {
        return $this->effectivePermissions()
            ->pluck('slug')
            ->values()
            ->all();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return in_array($permission, $this->permissionSlugs(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $slugs = $this->permissionSlugs();

        foreach ($permissions as $permission) {
            if (in_array($permission, $slugs, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string $roleSlug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn ($role) => $role->slug === $roleSlug);
        }

        return $this->roles()->where('slug', $roleSlug)->exists();
    }
}
