<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_item_id', 'user_id', 'type',
        'quantity', 'balance_after', 'reference', 'notes',
    ];
 
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
 
    // References users table (not admin_users)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
