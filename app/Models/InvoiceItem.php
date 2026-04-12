<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
class InvoiceItem extends Model {
    protected $fillable = ['invoice_id','line_type','service_id','inventory_item_id','description','quantity','unit_price','total','meta'];
    protected $casts = ['meta' => 'array'];
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function inventoryItem() { return $this->belongsTo(InventoryItem::class); }
}
