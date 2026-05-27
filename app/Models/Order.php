<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status',
        'contact_email',
        'delivery_first_name', 'delivery_last_name',
        'delivery_address', 'delivery_city', 'delivery_phone',
        'payment_method', 'payment_reference',
        'payment_status', 'mpesa_checkout_request_id', 'mpesa_transaction_id',
        'subtotal', 'shipping', 'total', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getCustomerNameAttribute(): string
    {
        return trim($this->delivery_first_name . ' ' . $this->delivery_last_name);
    }
}
