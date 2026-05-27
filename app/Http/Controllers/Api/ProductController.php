<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductMovement;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->search,   fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when($request->category, fn($q, $c) => $q->where('category', $c))
            ->when($request->status, function ($q, $s) {
                match ($s) {
                    'out_of_stock' => $q->where('stock_qty', '<=', 0),
                    'low_stock'    => $q->whereColumn('stock_qty', '<=', 'reorder_level')->where('stock_qty', '>', 0),
                    'healthy'      => $q->whereColumn('stock_qty', '>', 'reorder_level'),
                    default        => null,
                };
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:200',
            'category'         => 'required|in:care_kits,accessories,apparel,lifestyle',
            'description'      => 'nullable|string|max:1000',
            'long_description' => 'nullable|string',
            'features'         => 'nullable|array',
            'features.*'       => 'string|max:300',
            'gallery'          => 'nullable|array',
            'gallery.*'        => 'string|max:500',
            'price'            => 'required|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'image_url'        => 'nullable|url|max:2000',
            'gallery_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'gallery.*'        => 'string|max:2000',
            'is_featured'      => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
            'stock_qty'        => 'nullable|integer|min:0',
            'reorder_level'    => 'nullable|integer|min:0',
        ]);

        $data = [
            'name'             => $request->name,
            'slug'             => Product::generateSlug($request->name),
            'category'         => $request->category,
            'description'      => $request->description,
            'long_description' => $request->long_description,
            'features'         => $request->features,
            'price'            => $request->price,
            'sale_price'       => $request->sale_price,
            'is_featured'      => $request->boolean('is_featured'),
            'is_active'        => $request->boolean('is_active', true),
            'is_sold_out'      => false,
            'sort_order'       => $request->sort_order ?? 0,
            'stock_qty'        => $request->stock_qty ?? 0,
            'reorder_level'    => $request->reorder_level ?? 5,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $this->cloudinary->upload($request->file('image'), 'premax/products');
        } elseif ($request->filled('image_url')) {
            $data['image'] = $request->input('image_url');
        }

        // Handle gallery image uploads
        $gallery = $request->gallery ?? [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $img) {
                $gallery[] = $this->cloudinary->upload($img, 'premax/products');
            }
        }
        $data['gallery'] = $gallery ?: null;

        $product = Product::create($data);

        // Record initial stock movement
        if ($product->stock_qty > 0) {
            ProductMovement::create([
                'product_id'   => $product->id,
                'user_id'      => $request->user()->id,
                'type'         => 'stock_in',
                'quantity'     => $product->stock_qty,
                'balance_after'=> $product->stock_qty,
                'notes'        => 'Initial stock',
            ]);
        }

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'             => 'sometimes|string|max:200',
            'category'         => 'sometimes|in:care_kits,accessories,apparel,lifestyle',
            'description'      => 'nullable|string|max:1000',
            'long_description' => 'nullable|string',
            'features'         => 'nullable|array',
            'features.*'       => 'string|max:300',
            'gallery'          => 'nullable|array',
            'gallery.*'        => 'string|max:500',
            'price'            => 'sometimes|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'image_url'        => 'nullable|url|max:2000',
            'gallery_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'gallery.*'        => 'string|max:2000',
            'is_featured'      => 'boolean',
            'is_sold_out'      => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
            'reorder_level'    => 'nullable|integer|min:0',
        ]);

        $data = $request->only([
            'name', 'category', 'description', 'long_description',
            'features', 'price', 'sale_price',
            'is_featured', 'is_sold_out', 'is_active',
            'sort_order', 'reorder_level',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                $this->cloudinary->delete($product->image);
            }
            $data['image'] = $this->cloudinary->upload($request->file('image'), 'premax/products');
        } elseif ($request->filled('image_url')) {
            $data['image'] = $request->input('image_url');
        }

        // Handle gallery — gallery_replace signals intent to update even when empty
        if ($request->has('gallery_replace') || $request->has('gallery') || $request->hasFile('gallery_images')) {
            $gallery = $request->gallery ?? [];
            if ($request->hasFile('gallery_images')) {
                foreach ($request->file('gallery_images') as $img) {
                    $gallery[] = $this->cloudinary->upload($img, 'premax/products');
                }
            }
            $data['gallery'] = $gallery ?: null;
        }

        $product->update($data);

        return response()->json($product->fresh());
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            $this->cloudinary->delete($product->image);
        }
        foreach ($product->gallery ?? [] as $img) {
            $this->cloudinary->delete($img);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted.']);
    }

    public function stockIn(Request $request, Product $product)
    {
        $request->validate([
            'quantity'   => 'required|integer|min:1',
            'notes'      => 'nullable|string|max:500',
        ]);

        $product->increment('stock_qty', $request->quantity);
        $balance = $product->fresh()->stock_qty;

        // Auto-clear sold_out flag when restocked
        if ($product->is_sold_out && $balance > 0) {
            $product->update(['is_sold_out' => false]);
        }

        ProductMovement::create([
            'product_id'    => $product->id,
            'user_id'       => $request->user()->id,
            'type'          => 'stock_in',
            'quantity'      => $request->quantity,
            'balance_after' => $balance,
            'notes'         => $request->notes,
        ]);

        return response()->json($product->fresh());
    }

    public function stockOut(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes'    => 'nullable|string|max:500',
        ]);

        if ($product->stock_qty < $request->quantity) {
            return response()->json([
                'message' => "Insufficient stock. Only {$product->stock_qty} units available.",
            ], 422);
        }

        $product->decrement('stock_qty', $request->quantity);
        $balance = $product->fresh()->stock_qty;

        if ($balance <= 0) {
            $product->update(['is_sold_out' => true]);
        }

        ProductMovement::create([
            'product_id'    => $product->id,
            'user_id'       => $request->user()->id,
            'type'          => 'stock_out',
            'quantity'      => -$request->quantity,
            'balance_after' => $balance,
            'notes'         => $request->notes,
        ]);

        return response()->json($product->fresh());
    }

    public function sell(Request $request, Product $product)
    {
        $request->validate([
            'quantity'   => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'customer'   => 'nullable|string|max:200',
            'payment'    => 'nullable|in:cash,mpesa,other',
            'notes'      => 'nullable|string|max:500',
        ]);

        if ($product->stock_qty < $request->quantity) {
            return response()->json([
                'message' => "Insufficient stock. Only {$product->stock_qty} units available.",
            ], 422);
        }

        $product->decrement('stock_qty', $request->quantity);
        $balance = $product->fresh()->stock_qty;

        if ($balance <= 0) {
            $product->update(['is_sold_out' => true]);
        }

        $price = (float) ($request->unit_price ?? $product->sale_price ?? $product->price);
        $total = $price * $request->quantity;

        $noteParts = array_filter([
            $request->customer ? 'Customer: ' . $request->customer : null,
            $request->payment  ? 'Payment: '  . strtoupper($request->payment) : null,
            'Total: KES ' . number_format($total, 2),
            $request->notes,
        ]);

        ProductMovement::create([
            'product_id'    => $product->id,
            'user_id'       => $request->user()->id,
            'type'          => 'stock_out',
            'source_ref'    => 'in_store_sale',
            'quantity'      => -$request->quantity,
            'balance_after' => $balance,
            'notes'         => implode(' | ', $noteParts),
        ]);

        return response()->json($product->fresh());
    }

    public function movements(Product $product)
    {
        $movements = $product->movements()
            ->with('user:id,name')
            ->latest()
            ->limit(60)
            ->get();

        return response()->json($movements);
    }

    public function alerts()
    {
        $products = Product::where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'reorder_level')
            ->orderBy('stock_qty')
            ->get(['id', 'name', 'category', 'stock_qty', 'reorder_level', 'image']);

        return response()->json($products);
    }
}
