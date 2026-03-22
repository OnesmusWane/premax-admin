<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
class VehicleInspection extends Model {
    protected $fillable = ['vehicle_id','admin_user_id','notes','checklist','mileage','inspected_at'];
    protected $casts    = ['checklist'=>'array','inspected_at'=>'datetime'];
    public function vehicle()   { return $this->belongsTo(Vehicle::class); }
    public function inspector() { return $this->belongsTo(AdminUser::class,'admin_user_id'); }
}
 
