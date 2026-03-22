<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
 
class AdminUser extends Authenticatable {
    use HasApiTokens, SoftDeletes, Notifiable;
 
    protected $table = 'admin_users';
    protected $fillable = ['name','email','password','role','avatar_url','initials','is_active','last_login_at'];
    protected $hidden   = ['password','remember_token'];
    protected $casts    = ['is_active'=>'boolean','last_login_at'=>'datetime'];
 
    public function getInitialsAttribute(): string {
        if ($this->attributes['initials']) return $this->attributes['initials'];
        return collect(explode(' ', $this->name))->take(2)->map(fn($w)=>strtoupper($w[0]))->implode('');
    }
}
