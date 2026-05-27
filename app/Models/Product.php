<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'category', 'description', 'long_description',
        'features', 'gallery', 'price', 'sale_price',
        'image', 'is_featured', 'is_sold_out', 'is_active',
        'sort_order', 'stock_qty', 'reorder_level',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'sale_price'   => 'decimal:2',
        'is_featured'  => 'boolean',
        'is_sold_out'  => 'boolean',
        'is_active'    => 'boolean',
        'features'     => 'array',
        'gallery'      => 'array',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(ProductMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_qty <= 0)                   return 'out_of_stock';
        if ($this->stock_qty <= $this->reorder_level) return 'low_stock';
        return 'healthy';
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public static function generateSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $count = static::withTrashed()->where('slug', 'like', $slug . '%')->count();
        return $count ? $slug . '-' . ($count + 1) : $slug;
    }
}
