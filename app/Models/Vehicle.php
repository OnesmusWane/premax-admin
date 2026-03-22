<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class Vehicle extends Model {
    use SoftDeletes;
    protected $fillable = ['customer_id','registration','make','model','year','color','engine_number','chassis_number','photo_url','notes','last_service_at'];
    protected $casts    = ['last_service_at'=>'datetime'];
 
    public function customer()    { return $this->belongsTo(Customer::class); }
    public function inspections() { return $this->hasMany(VehicleInspection::class); }
    public function jobCards()    { return $this->hasMany(JobCard::class); }
    public function invoices()    { return $this->hasMany(Invoice::class); }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
 
    public function getMakeModelAttribute(): string { return "{$this->make} {$this->model}"; }
}
 