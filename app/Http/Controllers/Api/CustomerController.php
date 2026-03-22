<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * GET /api/admin/customers
     */
    public function index(Request $request)
    {
        $customers = Customer::withCount(['vehicles', 'bookings'])
            ->when($request->search, fn($q, $s) =>
                $q->where('name',  'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($customers);
    }

    /**
     * POST /api/admin/customers
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:150',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'email' => 'nullable|email|max:150|unique:customers,email',
            'notes' => 'nullable|string|max:1000',
        ]);

        $customer = Customer::create([
            'name'         => $request->name,
            'phone'        => $request->phone,
            'email'        => $request->email,
            'notes'        => $request->notes,
            'member_since' => now()->toDateString(),
            'is_active'    => true,
        ]);

        return response()->json($customer, 201);
    }

    /**
     * GET /api/admin/customers/{customer}
     * Returns full profile with vehicles, stats.
     */
    public function show(Customer $customer)
    {
        $customer->load(['vehicles']);

        // Total spent across all paid invoices
        $totalSpent = $customer->invoices()
            ->where('status', 'paid')
            ->sum('total');

        // Favourite service — most booked
        $favourite = $customer->bookings()
            ->with('service')
            ->get()
            ->groupBy('service_id')
            ->map(fn($b) => ['name' => $b->first()->service?->name, 'count' => $b->count()])
            ->sortByDesc('count')
            ->first();

        return response()->json([
            ...$customer->toArray(),
            'bookings_count'   => $customer->bookings()->count(),
            'total_spent'      => $totalSpent,
            'favorite_service' => $favourite['name'] ?? null,
        ]);
    }

    /**
     * PUT /api/admin/customers/{customer}
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:150',
            'phone' => 'sometimes|string|max:20|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|max:150|unique:customers,email,' . $customer->id,
            'notes' => 'nullable|string|max:1000',
        ]);

        $customer->update($request->only(['name', 'phone', 'email', 'notes']));

        return response()->json($customer->fresh());
    }

    /**
     * GET /api/admin/customers/{customer}/service-history
     * All bookings with service, status, vehicle, and invoices.
     */
    public function serviceHistory(Customer $customer)
    {
        $bookings = $customer->bookings()
            ->with(['service', 'status', 'vehicle', 'invoices'])
            ->latest('scheduled_at')
            ->get()
            ->map(fn($b) => [
                'id'           => $b->id,
                'reference'    => $b->reference,
                'scheduled_at' => $b->scheduled_at?->toISOString(),
                'notes'        => $b->customer_notes,
                'vehicle'      => ['id' => $b->vehicle?->id, 'registration' => $b->vehicle?->registration],
                'service'      => ['id' => $b->service?->id, 'name' => $b->service?->name ?? '—'],
                'status'       => ['slug' => $b->status?->slug ?? 'pending', 'name' => $b->status?->name ?? 'Pending'],
                'invoices'     => $b->invoices->map(fn($i) => [
                    'id'             => $i->id,
                    'invoice_number' => $i->invoice_number,
                    'total'          => $i->total,
                    'payment_method' => $i->payment_method,
                    'mpesa_reference'=> $i->mpesa_reference,
                    'status'         => $i->status,
                    'paid_at'        => $i->paid_at?->toISOString(),
                ]),
            ]);

        return response()->json($bookings);
    }

    /**
     * GET /api/admin/customers/{customer}/invoices
     * All invoices for this customer with booking reference.
     */
    public function invoices(Customer $customer)
    {
        $invoices = $customer->invoices()
            ->with(['booking'])
            ->latest()
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'total'          => $i->total,
                'subtotal'       => $i->subtotal,
                'vat_amount'     => $i->vat_amount,
                'discount'       => $i->discount,
                'payment_method' => $i->payment_method,
                'mpesa_reference'=> $i->mpesa_reference,
                'status'         => $i->status,
                'paid_at'        => $i->paid_at?->toISOString(),
                'booking'        => $i->booking ? ['id' => $i->booking->id, 'reference' => $i->booking->reference] : null,
            ]);

        return response()->json($invoices);
    }
}