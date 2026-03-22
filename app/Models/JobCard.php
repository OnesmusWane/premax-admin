<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class JobCard extends Model {
    use SoftDeletes;
    protected $fillable = ['reference','vehicle_id','customer_id','service_id','service_name','assigned_to','stage','notes','estimated_minutes','started_at','completed_at'];
    protected $casts    = ['started_at'=>'datetime','completed_at'=>'datetime'];
 
    public function vehicle()  { return $this->belongsTo(Vehicle::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function service()  { return $this->belongsTo(Service::class); }
    public function assignee() { return $this->belongsTo(AdminUser::class,'assigned_to'); }
 
    public function getElapsedMinutesAttribute(): ?int {
        if (!$this->started_at) return null;
        $end = $this->completed_at ?? now();
        return (int) $this->started_at->diffInMinutes($end);
    }
 
    public static function generateReference(): string {
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->count() + 1;
        return sprintf('JC-%s-%03d', $date, $last);
    }
}
