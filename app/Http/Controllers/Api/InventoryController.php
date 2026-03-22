<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * GET /api/admin/inventory
     */
    public function index(Request $request)
    {
        $items = InventoryItem::query()
            ->when($request->search,   fn($q, $s) => $q->where('name', 'like', "%$s%")->orWhere('sku', 'like', "%$s%"))
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->when($request->status, function ($q, $s) {
                match ($s) {
                    'out_of_stock' => $q->where('stock_qty', '<=', 0),
                    'low_stock'    => $q->whereColumn('stock_qty', '<=', 'reorder_level')->where('stock_qty', '>', 0),
                    'healthy'      => $q->whereColumn('stock_qty', '>', 'reorder_level'),
                    default        => null,
                };
            })
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return response()->json($items);
    }

    /**
     * POST /api/admin/inventory
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'sku'           => 'required|string|max:100|unique:inventory_items,sku',
            'category'      => 'required|in:lubricants,cleaning,parts,tools,other',
            'stock_qty'     => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'unit_price'    => 'required|integer|min:0',
            'cost_price'    => 'nullable|integer|min:0',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $item = InventoryItem::create($data);

        // Record initial stock as a movement
        if ($data['stock_qty'] > 0) {
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'user_id'           => $request->user()->id,
                'type'              => 'stock_in',
                'quantity'          => $data['stock_qty'],
                'balance_after'     => $data['stock_qty'],
                'notes'             => 'Initial stock',
            ]);
        }

        return response()->json($item, 201);
    }

    /**
     * GET /api/admin/inventory/{inventoryItem}
     */
    public function show(InventoryItem $inventoryItem)
    {
        return response()->json($inventoryItem);
    }

    /**
     * PUT /api/admin/inventory/{inventoryItem}
     */
    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $request->validate([
            'name'          => 'sometimes|string|max:200',
            'category'      => 'sometimes|in:lubricants,cleaning,parts,tools,other',
            'reorder_level' => 'sometimes|integer|min:0',
            'unit_price'    => 'sometimes|integer|min:0',
            'cost_price'    => 'nullable|integer|min:0',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $inventoryItem->update($request->only([
            'name', 'category', 'reorder_level', 'unit_price', 'cost_price', 'notes',
        ]));

        return response()->json($inventoryItem->fresh());
    }

    /**
     * DELETE /api/admin/inventory/{inventoryItem}
     */
    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();
        return response()->json(['message' => 'Product deleted.']);
    }

    /**
     * POST /api/admin/inventory/{inventoryItem}/stock-in
     */
    public function stockIn(Request $request, InventoryItem $inventoryItem)
    {
        $request->validate([
            'quantity'  => 'required|integer|min:1',
            'reference' => 'nullable|string|max:200',
            'notes'     => 'nullable|string|max:500',
        ]);

        $inventoryItem->increment('stock_qty', $request->quantity);
        $newBalance = $inventoryItem->fresh()->stock_qty;

        InventoryMovement::create([
            'inventory_item_id' => $inventoryItem->id,
            'user_id'           => $request->user()->id,
            'type'              => 'stock_in',
            'quantity'          => $request->quantity,
            'balance_after'     => $newBalance,
            'reference'         => $request->reference,
            'notes'             => $request->notes,
        ]);

        return response()->json($inventoryItem->fresh());
    }

    /**
     * POST /api/admin/inventory/{inventoryItem}/stock-out
     */
    public function stockOut(Request $request, InventoryItem $inventoryItem)
    {
        $request->validate([
            'quantity'  => 'required|integer|min:1',
            'reference' => 'nullable|string|max:200',
            'notes'     => 'nullable|string|max:500',
        ]);

        if ($inventoryItem->stock_qty < $request->quantity) {
            return response()->json([
                'message' => "Insufficient stock. Only {$inventoryItem->stock_qty} units available."
            ], 422);
        }

        $inventoryItem->decrement('stock_qty', $request->quantity);
        $newBalance = $inventoryItem->fresh()->stock_qty;

        InventoryMovement::create([
            'inventory_item_id' => $inventoryItem->id,
            'user_id'           => $request->user()->id,
            'type'              => 'stock_out',
            'quantity'          => -$request->quantity,   // negative for out
            'balance_after'     => $newBalance,
            'reference'         => $request->reference,
            'notes'             => $request->notes,
        ]);

        return response()->json($inventoryItem->fresh());
    }

    /**
     * GET /api/admin/inventory/{inventoryItem}/movements
     * Movement history for a single item.
     */
    public function movements(InventoryItem $inventoryItem)
    {
        $movements = $inventoryItem->movements()
            ->with('user:id,name')
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($movements);
    }

    /**
     * GET /api/admin/inventory/alerts
     * Items at or below reorder level.
     */
    public function alerts()
    {
        $items = InventoryItem::whereColumn('stock_qty', '<=', 'reorder_level')
            ->where('is_active', true)
            ->orderBy('stock_qty')
            ->get();

        return response()->json($items);
    }
}