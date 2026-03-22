<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class VehicleChecklist extends Model
{
    use SoftDeletes;
 
    protected $fillable = [
        'sn', 'vehicle_id', 'customer_id', 'job_card_id',
        'checked_in_by', 'checked_out_by',
        'reg_no', 'make', 'model', 'colour',
        'fuel_level', 'odometer', 'payment_option',
        'exterior', 'interior', 'engine_compartment', 'extras',
        'exterior_remarks', 'interior_remarks',
        'released_from', 'released_to', 'released_by', 'received_by',
        'released_by_tel', 'received_by_tel', 'received_by_id',
        'release_signature', 'receive_signature',
        'status', 'checked_in_at', 'checked_out_at',
    ];
 
    protected $casts = [
        'exterior'          => 'array',
        'interior'          => 'array',
        'engine_compartment'=> 'array',
        'extras'            => 'array',
        'checked_in_at'     => 'datetime',
        'checked_out_at'    => 'datetime',
    ];
 
    public function vehicle()     { return $this->belongsTo(Vehicle::class); }
    public function customer()    { return $this->belongsTo(Customer::class); }
    public function jobCard()     { return $this->belongsTo(JobCard::class); }
    public function checkedInBy() { return $this->belongsTo(AdminUser::class, 'checked_in_by'); }
    public function checkedOutBy(){ return $this->belongsTo(AdminUser::class, 'checked_out_by'); }
 
    public static function generateSn(): string
    {
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->count() + 1;
        return sprintf('CHK-%s-%03d', $date, $last);
    }
 
    /**
     * Default checklist structure matching the physical Premax form.
     */
    public static function defaultExterior(): array
    {
        return array_fill_keys([
            'front_windscreen', 'rear_windscreen', 'insurance_sticker',
            'front_number_plate', 'headlights', 'tail_lights',
            'front_bumper', 'rear_bumper', 'grille', 'grille_badge',
            'front_wiper', 'rear_wiper', 'side_mirror', 'door_glasses',
            'fuel_tank_cap', 'front_tyres', 'rear_tyres',
            'front_rims', 'rear_rims', 'hub_wheel_caps',
            'roof_rails', 'body_moulding', 'emblems',
            'weather_stripes', 'mud_guard',
        ], ['status' => 'ok', 'note' => '']);
    }
 
    public static function defaultInterior(): array
    {
        return array_fill_keys([
            'rear_view_mirror', 'radio', 'radio_face', 'equalizer',
            'amplifier', 'tuner', 'speaker', 'cigar_lighter',
            'door_switches', 'rubber_mats', 'carpets', 'seat_covers',
            'boot_mat', 'boot_board', 'aircon_knobs', 'keys_remotes',
            'seat_belts',
        ], ['status' => 'ok', 'note' => '']);
    }
 
    public static function defaultEngineCompartment(): array
    {
        return array_fill_keys([
            'battery', 'computer_control_box', 'ignition_coils',
            'wiper_panel_finisher_covers', 'horn', 'engine_caps',
            'dip_sticks', 'starter', 'alternator', 'fog_lights',
            'reverse_camera', 'relays', 'radiator',
        ], ['status' => 'ok', 'note' => '']);
    }
 
    public static function defaultExtras(): array
    {
        return array_fill_keys([
            'jack_handle', 'wheel_spanner', 'towing_pin',
            'towing_cable_rope', 'first_aid_kit', 'fire_extinguisher',
            'spare_wheel', 'life_savers',
        ], ['status' => 'ok', 'note' => '']);
    }
}