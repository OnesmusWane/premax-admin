<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class Invoice extends Model
{
    use SoftDeletes;
 
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'vehicle_id',
        'booking_id',       // ← add this
        'payment_method',
        'mpesa_reference',
        'card_reference',
        'subtotal',
        'vat_percent',
        'vat_amount',
        'discount',
        'total',
        'status',
        'notes',
        'paid_at',
        'created_by',
    ];
 
    protected $casts = [
        'paid_at'    => 'datetime',
        'subtotal'   => 'integer',
        'vat_amount' => 'integer',
        'discount'   => 'integer',
        'total'      => 'integer',
    ];
 
    // ── Relationships ─────────────────────────────────────────────────────────
 
    public function customer() { return $this->belongsTo(Customer::class); }
    public function vehicle()  { return $this->belongsTo(Vehicle::class); }
    public function booking()  { return $this->belongsTo(Booking::class); }  // ← add this
    public function items()    { return $this->hasMany(InvoiceItem::class); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
 
    public static function generateNumber(): string
    {
        $last = static::withTrashed()->count() + 1;
        return sprintf('INV-%03d', $last);
    }
}
