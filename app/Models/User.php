<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
 
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
 
    protected $fillable = ['name', 'email', 'password', 'email_verified_at', 'is_active', 'last_login_at'];
 
    protected $hidden = ['password', 'remember_token'];
 
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];
 
    // ── Relationships ─────────────────────────────────────────────────────
 
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
 
    // ── Helpers ───────────────────────────────────────────────────────────
 
    /**
     * Get the employee's role — falls back to 'staff' if no employee record.
     */
    public function getRoleAttribute(): string
    {
        return $this->employee?->role ?? 'staff';
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
}
