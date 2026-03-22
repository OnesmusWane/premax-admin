<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class InventoryItem extends Model
{
    use SoftDeletes;
 
    protected $fillable = [
        'name', 'sku', 'category', 'stock_qty',
        'reorder_level', 'unit_price', 'cost_price',
        'notes', 'is_active',
    ];
 
    protected $casts = [
        'is_active' => 'boolean',
    ];
 
    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
 
    // Stock status accessor
    public function getStatusAttribute(): string
    {
        if ($this->stock_qty <= 0)                    return 'out_of_stock';
        if ($this->stock_qty <= $this->reorder_level) return 'low_stock';
        return 'healthy';
    }
}
