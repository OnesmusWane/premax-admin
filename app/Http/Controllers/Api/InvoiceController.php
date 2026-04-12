<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Booking, BookingStatus, Customer, InventoryItem, InventoryMovement, Invoice, InvoiceItem, Vehicle};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
 
class InvoiceController extends Controller
{
    /**
     * GET /api/admin/invoices
     */
    public function index(Request $request)
{
    $invoices = Invoice::with(['customer', 'vehicle', 'booking'])
        ->when($request->search, fn($q, $s) =>
            $q->where('invoice_number', 'like', "%$s%")
              ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))
              ->orWhereHas('booking',  fn($q) => $q->where('reference', 'like', "%$s%"))
        )
        ->when($request->status, fn($q, $s) => $q->where('status', $s))
        ->when($request->method, fn($q, $m) => $q->where('payment_method', $m))
        ->when($request->date,   fn($q, $d) => $q->whereDate('paid_at', $d))
        ->latest()
        ->paginate(15);
 
    // Transform for table
    $invoices->getCollection()->transform(fn($i) => [
        'id'               => $i->id,
        'invoice_number'   => $i->invoice_number,
        'booking_reference'=> $i->booking?->reference,
        'sale_type'        => $i->sale_type,
        'customer_name'    => $i->anonymous_customer_name ?? $i->customer?->name ?? '—',
        'vehicle_reg'      => $i->vehicle?->registration ?? '—',
        'total'            => $i->total,
        'payment_method'   => $i->payment_method,
        'payment_provider' => $i->payment_provider,
        'mpesa_reference'  => $i->mpesa_reference,
        'status'           => $i->status,
        'paid_at'          => $i->paid_at?->toISOString(),
        'created_at'       => $i->created_at?->toISOString(),
    ]);
 
    return response()->json($invoices);
}
 
    /**
     * GET /api/admin/invoices/today
     */
    public function todaySummary()
    {
        $today = Invoice::whereDate('paid_at', today())->where('status', 'paid');
 
        return response()->json([
            'total' => (clone $today)->sum('total'),
            'cash'  => (clone $today)->where('payment_method', 'cash')->sum('total'),
            'mpesa' => (clone $today)->where('payment_method', 'mpesa')->sum('total'),
            'card'  => (clone $today)->where('payment_method', 'card')->sum('total'),
            'count' => (clone $today)->count(),
        ]);
    }
 
    /**
     * GET /api/admin/invoices/{invoice}
     */
    public function show(Invoice $invoice)
    {
        return response()->json(
            $invoice->load(['customer', 'vehicle', 'booking.service', 'items.inventoryItem', 'items.service', 'creator'])
        );
    }
 
    /**
     * POST /api/admin/pos/checkout
     * Creates invoice, links to booking, auto-updates booking status to completed.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'booking_id'              => 'nullable|exists:bookings,id',
            'customer_id'             => 'nullable|exists:customers,id',
            'is_anonymous'            => 'sometimes|boolean',
            'customer_name'           => 'nullable|string|max:150',
            'customer_phone'          => 'nullable|string|max:20',
            'vehicle_reg'             => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.description'     => 'required|string',
            'items.*.quantity'        => 'required|integer|min:1',
            'items.*.unit_price'      => 'required|numeric|min:0',
            'items.*.line_type'       => 'nullable|in:service,inventory,custom,deposit',
            'items.*.service_id'      => 'nullable|exists:services,id',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'payment_method'          => 'required|in:cash,mpesa,card,bank_transfer,other',
            'mpesa_reference'         => 'nullable|string',
            'card_reference'          => 'nullable|string',
            'gateway_reference'       => 'nullable|string',
            'discount'                => 'nullable|integer|min:0',
            'vat_percent'             => 'nullable|integer',
            'notes'                   => 'nullable|string',
        ]);
 
        return DB::transaction(function () use ($request) {
            $booking = $request->booking_id
                ? Booking::with(['customer', 'vehicle'])->find($request->booking_id)
                : null;

            $resolvedCustomer = $this->resolveCustomerForSale($request, $booking);
            $customerId = $resolvedCustomer?->id;
            $anonymousCustomerName = $resolvedCustomer?->is_anonymous ? $resolvedCustomer->name : null;

            $vehicleId = $booking?->vehicle_id;
            if (!$vehicleId && $request->vehicle_reg) {
                $vehicleId = Vehicle::where('registration', strtoupper($request->vehicle_reg))->value('id');
            }

            $manualDiscount = (int) ($request->discount ?? 0);
            $depositCredit = $booking ? min((int) $booking->deposit_paid_amount, (int) collect($request->items)
                ->sum(fn($i) => $i['quantity'] * $i['unit_price'])) : 0;
            $discount   = $manualDiscount + $depositCredit;
            $vatPercent = (int) ($request->vat_percent ?? 16);
            $subtotal   = (int) collect($request->items)
                ->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $vatAmount  = (int) round(($subtotal - $discount) * $vatPercent / 100);
            $total      = $subtotal - $discount + $vatAmount;

            $invoice = Invoice::create([
                'invoice_number'  => Invoice::generateNumber(),
                'customer_id'     => $customerId,
                'anonymous_customer_name' => $anonymousCustomerName,
                'vehicle_id'      => $vehicleId,
                'booking_id'      => $booking?->id,
                'sale_type'       => $booking ? 'booking' : 'direct_sale',
                'payment_method'  => $request->payment_method,
                'payment_provider'=> $request->payment_method === 'mpesa' ? 'kopokopo' : null,
                'mpesa_reference' => $request->mpesa_reference,
                'gateway_reference' => $request->gateway_reference,
                'subtotal'        => $subtotal,
                'vat_percent'     => $vatPercent,
                'vat_amount'      => $vatAmount,
                'discount'        => $discount,
                'total'           => $total,
                'status'          => 'paid',
                'notes'           => trim(implode(' ', array_filter([
                    $request->notes,
                    $depositCredit > 0 ? "Includes booking deposit credit of KES {$depositCredit}." : null,
                ]))),
                'paid_at'         => now(),
                'created_by'      => $request->user()->id,
            ]);

            foreach ($request->items as $item) {
                $inventoryItemId = $item['inventory_item_id'] ?? null;
                $lineType = $item['line_type'] ?? ($inventoryItemId ? 'inventory' : (($item['service_id'] ?? null) ? 'service' : 'custom'));

                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'line_type'   => $lineType,
                    'service_id'  => $item['service_id'] ?? null,
                    'inventory_item_id' => $inventoryItemId,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total'       => $item['quantity'] * $item['unit_price'],
                    'meta'        => $item['meta'] ?? null,
                ]);

                if ($inventoryItemId) {
                    $inventoryItem = InventoryItem::lockForUpdate()->findOrFail($inventoryItemId);

                    if ($inventoryItem->stock_qty < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => "Insufficient stock for {$inventoryItem->name}. Only {$inventoryItem->stock_qty} available.",
                        ]);
                    }

                    $inventoryItem->decrement('stock_qty', $item['quantity']);

                    InventoryMovement::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'user_id'           => $request->user()->id,
                        'type'              => 'stock_out',
                        'quantity'          => -((int) $item['quantity']),
                        'balance_after'     => $inventoryItem->fresh()->stock_qty,
                        'reference'         => $invoice->invoice_number,
                        'notes'             => 'Sold via POS/invoice checkout',
                    ]);
                }
            }

            if ($booking) {
                $completedStatus = BookingStatus::where('slug', 'completed')->first();
                if ($completedStatus) {
                    $booking->update(['booking_status_id' => $completedStatus->id]);
                }
            }

            return response()->json(
                $invoice->load(['items.inventoryItem', 'items.service', 'customer', 'vehicle', 'booking']),
                201
            );
        });
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate([
            'status' => 'required|in:paid,pending,cancelled',
        ]);
    
        $invoice->update([
            'status'  => $request->status,
            'paid_at' => $request->status === 'paid' && !$invoice->paid_at ? now() : $invoice->paid_at,
        ]);
    
        return response()->json(['status' => $invoice->fresh()->status]);
    }

    public function linkBooking(Request $request, Invoice $invoice)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);
    
        $booking = \App\Models\Booking::findOrFail($request->booking_id);
    
        $invoice->update(['booking_id' => $booking->id, 'sale_type' => 'booking']);
    
        // Also update booking's status to completed if invoice is paid
        if ($invoice->status === 'paid') {
            $completed = \App\Models\BookingStatus::where('slug', 'completed')->first();
            if ($completed) {
                $booking->update(['booking_status_id' => $completed->id]);
            }
        }
    
        return response()->json([
            'booking' => [
                'id'           => $booking->id,
                'reference'    => $booking->reference,
                'scheduled_at' => $booking->scheduled_at?->toISOString(),
                'service'      => ['name' => $booking->service?->name],
            ]
        ]);
    }

    private function resolveCustomerForSale(Request $request, ?Booking $booking): ?Customer
    {
        if ($booking?->customer) {
            return $booking->customer;
        }

        if ($request->customer_id) {
            return Customer::find($request->customer_id);
        }

        if ($request->boolean('is_anonymous', true)) {
            return Customer::create([
                'name' => $request->customer_name ?: 'Anonymous POS Client',
                'phone' => null,
                'email' => null,
                'member_since' => now()->toDateString(),
                'is_active' => true,
                'is_anonymous' => true,
            ]);
        }

        if (!$request->customer_phone || !$request->customer_name) {
            return null;
        }

        return Customer::updateOrCreate(
            ['phone' => $request->customer_phone],
            [
                'name' => $request->customer_name,
                'member_since' => now()->toDateString(),
                'is_active' => true,
                'is_anonymous' => false,
            ]
        );
    }
 
}
