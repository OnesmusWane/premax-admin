<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items.product:id,name,image', 'user:id,name,email')
            ->when($request->search, fn($q, $s) =>
                $q->where('order_number', 'like', "%$s%")
                  ->orWhere('contact_email', 'like', "%$s%")
                  ->orWhere('delivery_first_name', 'like', "%$s%")
                  ->orWhere('delivery_last_name', 'like', "%$s%")
            )
            ->when($request->status,         fn($q, $s) => $q->where('status', $s))
            ->when($request->payment_status, fn($q, $s) => $q->where('payment_status', $s))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        $order->load('items.product:id,name,slug,image', 'user:id,name,email');
        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $order->update($request->only(['status', 'notes']));

        return response()->json($order->fresh(['items.product:id,name,image', 'user:id,name,email']));
    }
}
