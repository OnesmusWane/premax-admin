<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Vehicle;
 
class Customer extends Model {
    use SoftDeletes;
    protected $fillable = ['name','phone','email','initials','avatar_color','avatar_url','member_since','notes','is_active'];
    protected $casts    = ['is_active'=>'boolean','member_since'=>'date'];
 
    public function vehicles(): HasMany { return $this->hasMany(Vehicle::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function jobCards(): HasMany { return $this->hasMany(JobCard::class); }
 
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
 
    public function getTotalVisitsAttribute(): int {
        return $this->invoices()->where('status','paid')->count();
    }
    public function getTotalSpentAttribute(): int {
        return $this->invoices()->where('status','paid')->sum('total');
    }
    public function getFavoriteServiceAttribute(): ?string {
        return $this->jobCards()->select('service_name')
            ->groupBy('service_name')
            ->orderByRaw('COUNT(*) DESC')
            ->value('service_name');
    }
    public function getInitialsAttribute(): string {
        if ($this->attributes['initials'] ?? null) return $this->attributes['initials'];
        return collect(explode(' ', $this->name))->take(2)->map(fn($w)=>strtoupper($w[0] ?? ''))->implode('');
    }
}
