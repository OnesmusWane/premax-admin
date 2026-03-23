<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Invoice, InvoiceItem, Booking, BookingStatus, MpesaTransaction};
use Illuminate\Http\Request;
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

        $invoices->getCollection()->transform(fn($i) => [
            'id'                => $i->id,
            'invoice_number'    => $i->invoice_number,
            'booking_reference' => $i->booking?->reference,
            'customer_name'     => $i->customer?->name ?? '—',
            'vehicle_reg'       => $i->vehicle?->registration ?? '—',
            'total'             => $i->total,
            'payment_method'    => $i->payment_method,
            'mpesa_reference'   => $i->mpesa_reference,
            'status'            => $i->status,
            'paid_at'           => $i->paid_at?->toISOString(),
            'created_at'        => $i->created_at?->toISOString(),
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
            $invoice->load(['customer', 'vehicle', 'booking.service', 'items', 'creator'])
        );
    }

    /**
     * POST /api/admin/pos/checkout
     * Creates invoice, links to booking, auto-updates booking status to completed.
     * If payment_method is mpesa and checkout_request_id is provided,
     * it waits for the confirmed M-Pesa receipt from mpesa_transactions.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'booking_id'              => 'nullable|exists:bookings,id',
            'customer_id'             => 'nullable|exists:customers,id',
            'checklist_id'            => 'nullable|exists:checklists,id',
            'vehicle_reg'             => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.description'     => 'required|string',
            'items.*.quantity'        => 'required|integer|min:1',
            'items.*.unit_price'      => 'required|numeric|min:0',
            'payment_method'          => 'required|in:cash,mpesa,card,bank_transfer,other',
            'mpesa_reference'         => 'nullable|string',
            'checkout_request_id'     => 'nullable|string',
            'card_reference'          => 'nullable|string',
            'discount'                => 'nullable|integer|min:0',
            'vat_percent'             => 'nullable|integer',
            'notes'                   => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {

            // ── Resolve mpesa reference ────────────────────────────────────
            // If checkout_request_id is passed, pull the confirmed receipt from DB
            $mpesaReference = $request->mpesa_reference;
            if ($request->payment_method === 'mpesa' && $request->checkout_request_id && !$mpesaReference) {
                $tx = MpesaTransaction::where('checkout_request_id', $request->checkout_request_id)
                    ->where('status', 'success')
                    ->first();
                $mpesaReference = $tx?->mpesa_receipt_number;
            }

            // ── Resolve booking ────────────────────────────────────────────
            $booking = $request->booking_id
                ? Booking::with(['customer', 'vehicle'])->find($request->booking_id)
                : null;

            // ── Resolve customer & vehicle ─────────────────────────────────
            $customerId = $booking?->customer_id ?? $request->customer_id;
            $vehicleId  = $booking?->vehicle_id  ?? null;

            if (!$vehicleId && $request->vehicle_reg) {
                $vehicleId = \App\Models\Vehicle::where('registration', strtoupper($request->vehicle_reg))
                    ->value('id');
            }

            // ── Calculate totals ───────────────────────────────────────────
            $discount   = (int) ($request->discount   ?? 0);
            $vatPercent = (int) ($request->vat_percent ?? 16);
            $subtotal   = (int) collect($request->items)
                ->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $vatAmount  = (int) round(($subtotal - $discount) * $vatPercent / 100);
            $total      = $subtotal - $discount + $vatAmount;

            // ── Create invoice ─────────────────────────────────────────────
            $invoice = Invoice::create([
                'invoice_number'      => Invoice::generateNumber(),
                'customer_id'         => $customerId,
                'vehicle_id'          => $vehicleId,
                'booking_id'          => $booking?->id,
                'checklist_id'        => $request->checklist_id,
                'payment_method'      => $request->payment_method,
                'mpesa_reference'     => $mpesaReference,
                'card_reference'      => $request->card_reference,
                'subtotal'            => $subtotal,
                'vat_percent'         => $vatPercent,
                'vat_amount'          => $vatAmount,
                'discount'            => $discount,
                'total'               => $total,
                'status'              => 'paid',
                'notes'               => $request->notes,
                'paid_at'             => now(),
                'created_by'          => $request->user()->id,
            ]);

            // ── Create invoice items ───────────────────────────────────────
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total'       => $item['quantity'] * $item['unit_price'],
                ]);
            }

            // ── Link MpesaTransaction to invoice ───────────────────────────
            if ($request->payment_method === 'mpesa' && $request->checkout_request_id) {
                MpesaTransaction::where('checkout_request_id', $request->checkout_request_id)
                    ->update(['invoice_id' => $invoice->id]);
            }

            // ── Auto-update booking status to completed ────────────────────
            if ($booking) {
                $completedStatus = BookingStatus::where('slug', 'completed')->first();
                if ($completedStatus) {
                    $booking->update(['booking_status_id' => $completedStatus->id]);
                }
            }

            return response()->json(
                $invoice->load(['items', 'customer', 'vehicle', 'booking']),
                201
            );
        });
    }

    /**
     * PATCH /api/admin/invoices/{invoice}/mpesa-reference
     * Called by frontend after STK polling confirms payment,
     * to update the invoice with the M-Pesa receipt number.
     */
    public function updateMpesaReference(Request $request, Invoice $invoice)
    {
        $request->validate([
            'mpesa_reference'     => 'required|string',
            'checkout_request_id' => 'nullable|string',
        ]);

        $invoice->update([
            'mpesa_reference' => $request->mpesa_reference,
            'status'          => 'paid',
            'paid_at'         => $invoice->paid_at ?? now(),
        ]);

        // Also link the mpesa_transaction to this invoice
        if ($request->checkout_request_id) {
            MpesaTransaction::where('checkout_request_id', $request->checkout_request_id)
                ->update(['invoice_id' => $invoice->id]);
        }

        return response()->json([
            'message'         => 'Invoice updated with M-Pesa reference.',
            'mpesa_reference' => $invoice->fresh()->mpesa_reference,
        ]);
    }

    /**
     * PATCH /api/admin/invoices/{invoice}/status
     */
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

    /**
     * PATCH /api/admin/invoices/{invoice}/link-booking
     */
    public function linkBooking(Request $request, Invoice $invoice)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = \App\Models\Booking::findOrFail($request->booking_id);
        $invoice->update(['booking_id' => $booking->id]);

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
}