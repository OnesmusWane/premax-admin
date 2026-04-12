<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\BookingSource;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\VehicleChecklist;
use Illuminate\Support\Facades\DB;

class BookingsController extends Controller
{
    /**
     * GET /api/admin/bookings
     */
    public function index(Request $request)
    {
        $bookings = Booking::with(['customer', 'vehicle', 'service', 'status', 'source', 'checklist', 'invoices'])
            ->when($request->search, function ($q, $s) {
                $q->whereHas('customer', fn($q) => $q->where('name', 'like', "%$s%")
                    ->orWhere('phone', 'like', "%$s%"))
                ->orWhere('anonymous_customer_name', 'like', "%$s%")
                ->orWhereHas('vehicle', fn($q) => $q->where('registration', 'like', "%$s%"));
            })
            ->when($request->status, function ($q, $s) {
                $q->whereHas('status', fn($q) => $q->where('slug', $s));
            })
            ->when($request->date, fn($q, $d) => $q->whereDate('scheduled_at', $d))
            ->when($request->service_id, fn($q, $id) => $q->where('service_id', $id))
            ->latest('scheduled_at')
            ->paginate($request->per_page ?? 15);

        // Transform for frontend
        $bookings->getCollection()->transform(fn($b) => $this->transform($b));

        return response()->json($bookings);
    }

    /**
     * POST /api/admin/bookings
     */
    
public function store(Request $request)
{
    $request->validate([
        'is_anonymous'    => 'sometimes|boolean',
        'customer_name'   => 'nullable|string|max:150|required_unless:is_anonymous,true',
        'customer_phone'  => 'nullable|string|max:20|required_unless:is_anonymous,true',
        'customer_email'  => 'nullable|email|max:150',
        'vehicle_reg'     => 'required|string|max:20',
        'vehicle_make'    => 'nullable|string|max:100',
        'vehicle_model'   => 'nullable|string|max:100',
        'service_id'      => 'required|exists:services,id',
        'scheduled_at'    => 'required|date',
        'notes'           => 'nullable|string|max:1000',
        'source'          => 'nullable|string',
        'deposit_payment'                 => 'nullable|array',
        'deposit_payment.payment_method'  => 'nullable|in:cash,mpesa,card,bank_transfer,other',
        'deposit_payment.mpesa_reference' => 'nullable|string|max:100',
        'deposit_payment.card_reference'  => 'nullable|string|max:100',
        'deposit_payment.gateway_reference' => 'nullable|string|max:255',
        'deposit_payment.notes'           => 'nullable|string|max:500',
        // Optional checklist
        'checklist'                      => 'nullable|array',
        'checklist.fuel_level'           => 'nullable|in:F,3/4,1/2,1/4,E',
        'checklist.odometer'             => 'nullable|integer|min:0',
        'checklist.colour'               => 'nullable|string|max:50',
        'checklist.payment_option'       => 'nullable|in:mpesa,cash,insurance,cheque,other',
        'checklist.exterior'             => 'nullable|array',
        'checklist.interior'             => 'nullable|array',
        'checklist.engine_compartment'   => 'nullable|array',
        'checklist.extras'               => 'nullable|array',
        'checklist.exterior_remarks'     => 'nullable|string|max:2000',
        'checklist.interior_remarks'     => 'nullable|string|max:2000',
    ]);
 
    return DB::transaction(function () use ($request) {
        $service = Service::findOrFail($request->service_id);
        $isAnonymous = $request->boolean('is_anonymous');
        $depositPercent = $service->requires_deposit ? (int) $service->deposit_percent : null;
        $depositRequiredAmount = $depositPercent
            ? (int) round(((int) ($service->price_from ?? 0)) * $depositPercent / 100)
            : 0;

        if ($depositRequiredAmount > 0 && !$request->filled('deposit_payment.payment_method')) {
            return response()->json([
                'message' => "This service requires a {$depositPercent}% down payment before the booking can be saved.",
            ], 422);
        }

        $customer = $this->resolveCustomer(
            $request->customer_name,
            $request->customer_phone,
            $request->customer_email,
            $isAnonymous
        );
 
        $reg     = strtoupper(trim($request->vehicle_reg));
        $vehicle = Vehicle::updateOrCreate(
            ['registration' => $reg],
            [
                'customer_id' => $customer->id,
                'make'        => $request->vehicle_make  ?? 'Unknown',
                'model'       => $request->vehicle_model ?? 'Unknown',
                // Update colour if provided in checklist
                'color'       => $request->input('checklist.colour') ?? null,
            ]
        );
 
        if (!$vehicle->wasRecentlyCreated) {
            $vehicle->update(['last_service_at' => now()]);
        }
 
        $status  = BookingStatus::where('slug', 'pending')->first();
        $source  = BookingSource::where('slug', $request->source ?? 'walk_in')->first()
                ?? BookingSource::where('slug', 'walk_in')->first();
 
        $booking = Booking::create([
            'reference'         => Booking::generateReference(),
            'customer_id'       => $customer->id,
            'anonymous_customer_name' => $isAnonymous ? $customer->name : null,
            'vehicle_id'        => $vehicle->id,
            'service_id'        => $service?->id,
            'booking_status_id' => $status?->id,
            'booking_source_id' => $source?->id,
            'scheduled_at'      => Carbon::parse($request->scheduled_at),
            'customer_notes'    => $request->notes,
            'checklist_id'      => null,
            'requires_deposit'  => $depositRequiredAmount > 0,
            'deposit_percent'   => $depositPercent,
            'deposit_required_amount' => $depositRequiredAmount,
            'deposit_paid_amount' => 0,
        ]);
 
        if ($request->has('checklist')) {
            $cl = $request->input('checklist', []);
 
            $checklist = VehicleChecklist::create([
                'sn'                 => VehicleChecklist::generateSn(),
                'vehicle_id'         => $vehicle->id,
                'customer_id'        => $customer->id,
                'checked_in_by'      => $request->user()->id,
                'reg_no'             => $vehicle->registration,
                'make'               => $vehicle->make,
                'model'              => $vehicle->model,
                'colour'             => $cl['colour']         ?? $vehicle->color,
                'fuel_level'         => $cl['fuel_level']     ?? null,
                'odometer'           => $cl['odometer']       ?? null,
                'payment_option'     => $cl['payment_option'] ?? null,
                'exterior'           => $cl['exterior']           ?? VehicleChecklist::defaultExterior(),
                'interior'           => $cl['interior']           ?? VehicleChecklist::defaultInterior(),
                'engine_compartment' => $cl['engine_compartment'] ?? VehicleChecklist::defaultEngineCompartment(),
                'extras'             => $cl['extras']             ?? VehicleChecklist::defaultExtras(),
                'exterior_remarks'   => $cl['exterior_remarks']   ?? null,
                'interior_remarks'   => $cl['interior_remarks']   ?? null,
                'status'             => 'check_in',
                'checked_in_at'      => now(),
            ]);
 
            $booking->update(['checklist_id' => $checklist->id]);
        }

        if ($depositRequiredAmount > 0) {
            $payment = $request->input('deposit_payment', []);

            $invoice = Invoice::create([
                'invoice_number'  => Invoice::generateNumber(),
                'customer_id'     => $customer->id,
                'anonymous_customer_name' => $isAnonymous ? $customer->name : null,
                'vehicle_id'      => $vehicle->id,
                'booking_id'      => $booking->id,
                'sale_type'       => 'booking_deposit',
                'payment_method'  => $payment['payment_method'] ?? null,
                'payment_provider'=> ($payment['payment_method'] ?? null) === 'mpesa' ? 'kopokopo' : null,
                'mpesa_reference' => $payment['mpesa_reference'] ?? null,
                'gateway_reference' => $payment['gateway_reference'] ?? null,
                // 'card_reference'  => $payment['card_reference'] ?? null,
                'subtotal'        => $depositRequiredAmount,
                'vat_percent'     => 0,
                'vat_amount'      => 0,
                'discount'        => 0,
                'total'           => $depositRequiredAmount,
                'status'          => 'paid',
                'notes'           => $payment['notes'] ?? "Booking deposit for {$service->name}",
                'paid_at'         => now(),
                'created_by'      => $request->user()->id,
            ]);

            InvoiceItem::create([
                'invoice_id'   => $invoice->id,
                'line_type'    => 'deposit',
                'service_id'   => $service->id,
                'description'  => "Booking deposit for {$service->name}",
                'quantity'     => 1,
                'unit_price'   => $depositRequiredAmount,
                'total'        => $depositRequiredAmount,
                'meta'         => ['deposit_percent' => $depositPercent],
            ]);

            $booking->update(['deposit_paid_amount' => $depositRequiredAmount]);
        }
 
        return response()->json(
            $this->transform(
                $booking->load(['customer', 'vehicle', 'service', 'status', 'source', 'checklist', 'invoices'])
            ),
            201
        );
    });
}

    /**
     * GET /api/admin/bookings/{booking}
     */
    public function show(Booking $booking)
    {
        return response()->json(
            $this->transform($booking->load(['customer', 'vehicle', 'service', 'status', 'source', 'checklist', 'invoices']))
        );
    }

    /**
     * PATCH /api/admin/bookings/{booking}
     */
    public function update(Request $request, Booking $booking)
    {
        $request->validate([
            'status'      => 'sometimes|string|exists:booking_statuses,slug',
            'notes'       => 'sometimes|nullable|string',
            'staff_notes' => 'sometimes|nullable|string',
            'scheduled_at'=> 'sometimes|date',
            'checklist_id' => 'sometimes|nullable|exists:vehicle_checklists,id',
        ]);

        if ($request->has('status')) {
            $status = BookingStatus::where('slug', $request->status)->first();
            if ($status) $booking->booking_status_id = $status->id;
        }

        if ($request->has('scheduled_at')) {
            $booking->scheduled_at = Carbon::parse($request->scheduled_at);
        }

        if ($request->has('notes'))       $booking->customer_notes = $request->notes;
        if ($request->has('staff_notes')) $booking->staff_notes    = $request->staff_notes;
        if ($request->has('checklist_id')) $booking->checklist_id    = $request->checklist_id;

        $booking->save();

        return response()->json(
            $this->transform($booking->fresh(['customer', 'vehicle', 'service', 'status', 'source', 'checklist', 'invoices']))
        );
    }

    public function collectDeposit(Request $request, Booking $booking)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,mpesa,card,bank_transfer,other',
            'mpesa_reference' => 'nullable|string|max:100',
            'card_reference' => 'nullable|string|max:100',
            'gateway_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!$booking->requires_deposit || $booking->deposit_required_amount <= 0) {
            return response()->json([
                'message' => 'This booking does not require a down payment.',
            ], 422);
        }

        $outstanding = max(0, $booking->deposit_required_amount - $booking->deposit_paid_amount);

        if ($outstanding <= 0) {
            return response()->json([
                'message' => 'The full down payment has already been collected.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $booking, $outstanding) {
            $invoice = Invoice::create([
                'invoice_number'  => Invoice::generateNumber(),
                'customer_id'     => $booking->customer_id,
                'anonymous_customer_name' => $booking->anonymous_customer_name,
                'vehicle_id'      => $booking->vehicle_id,
                'booking_id'      => $booking->id,
                'sale_type'       => 'booking_deposit',
                'payment_method'  => $request->payment_method,
                'payment_provider'=> $request->payment_method === 'mpesa' ? 'kopokopo' : null,
                'mpesa_reference' => $request->mpesa_reference,
                'gateway_reference' => $request->gateway_reference,
                'card_reference'  => $request->card_reference,
                'subtotal'        => $outstanding,
                'vat_percent'     => 0,
                'vat_amount'      => 0,
                'discount'        => 0,
                'total'           => $outstanding,
                'status'          => 'paid',
                'notes'           => $request->notes ?: "Collected booking deposit for {$booking->service?->name}",
                'paid_at'         => now(),
                'created_by'      => $request->user()->id,
            ]);

            InvoiceItem::create([
                'invoice_id'   => $invoice->id,
                'line_type'    => 'deposit',
                'service_id'   => $booking->service_id,
                'description'  => "Booking deposit for {$booking->service?->name}",
                'quantity'     => 1,
                'unit_price'   => $outstanding,
                'total'        => $outstanding,
                'meta'         => ['deposit_percent' => $booking->deposit_percent],
            ]);

            $booking->update([
                'deposit_paid_amount' => min($booking->deposit_required_amount, $booking->deposit_paid_amount + $outstanding),
            ]);

            return response()->json(
                $this->transform($booking->fresh(['customer', 'vehicle', 'service', 'status', 'source', 'checklist', 'invoices']))
            );
        });
    }

    /**
     * DELETE /api/admin/bookings/{booking}
     */
    public function destroy(Booking $booking)
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted.']);
    }

    /**
     * GET /api/admin/bookings/statuses
     * Returns all booking statuses for dropdowns.
     */
    public function statuses()
    {
        return response()->json(BookingStatus::orderBy('id')->get(['id', 'name', 'slug']));
    }

    /**
     * GET /api/admin/bookings/statuses
     * Returns all booking statuses for dropdowns.
     */
    public function services()
    {
        return response()->json(Service::orderBy('id')->get());
    }

    public function sources()
    {
        return response()->json(BookingSource::orderBy('id')->get(['id', 'name', 'slug']));
    }


    /**
     * Consistent shape for the frontend.
     */
   private function transform(Booking $b): array
{
    return [
        'id'            => $b->id,
        'reference'     => $b->reference,
        'customer'      => [
            'id'    => $b->customer?->id,
            'name'  => $b->anonymous_customer_name ?? $b->customer?->name ?? '—',
            'phone' => $b->customer?->phone ?? ($b->customer?->is_anonymous ? 'Anonymous' : '—'),
            'email' => $b->customer?->email,
            'is_anonymous' => (bool) ($b->customer?->is_anonymous ?? $b->anonymous_customer_name),
        ],
        'vehicle'       => [
            'id'           => $b->vehicle?->id,
            'registration' => $b->vehicle?->registration ?? '—',
            'make'         => $b->vehicle?->make,
            'model'        => $b->vehicle?->model,
        ],
        'service'       => [
            'id'   => $b->service?->id,
            'name' => $b->service?->name ?? '—',
        ],
        'scheduled_at'  => $b->scheduled_at?->toISOString(),
        'scheduled_date'=> $b->scheduled_at?->format('M d, Y'),
        'scheduled_time'=> $b->scheduled_at?->format('h:i A'),
        'status'        => [
            'slug' => $b->status?->slug ?? 'pending',
            'name' => $b->status?->name ?? 'Pending',
        ],
        'source'        => $b->source?->name ?? '—',
        'notes'         => $b->customer_notes,
        'staff_notes'   => $b->staff_notes,
        // Checklist summary
        'checklist'     => $b->checklist ? [
            'id'             => $b->checklist->id,
            'sn'             => $b->checklist->sn,
            'status'         => $b->checklist->status,
            'checked_in_at'  => $b->checklist->checked_in_at?->toISOString(),
            'checked_out_at' => $b->checklist->checked_out_at?->toISOString(),
        ] : null,
        // Invoice summary — shows when payment is done
        'invoices' => $b->invoices?->map(fn($inv) => [
            'id'             => $inv->id,
            'invoice_number' => $inv->invoice_number,
            'total'          => $inv->total,
            'sale_type'      => $inv->sale_type,
            'payment_method' => $inv->payment_method,
            'mpesa_reference'=> $inv->mpesa_reference,
            'status'         => $inv->status,
            'paid_at'        => $inv->paid_at?->toISOString(),
        ]) ?? [],
        // Add a convenience flag:
        'is_paid' => $b->invoices?->where('status', 'paid')->count() > 0,
        'deposit' => [
            'required' => (bool) $b->requires_deposit,
            'percent'  => $b->deposit_percent,
            'required_amount' => $b->deposit_required_amount,
            'paid_amount' => $b->deposit_paid_amount,
            'outstanding_amount' => max(0, (int) $b->deposit_required_amount - (int) $b->deposit_paid_amount),
            'is_paid' => $b->deposit_required_amount > 0 && $b->deposit_paid_amount >= $b->deposit_required_amount,
        ],
        'payment_summary' => [
            'paid_total' => (int) ($b->invoices?->where('status', 'paid')->sum('total') ?? 0),
            'status' => $this->paymentStatus($b),
        ],
        'created_at'    => $b->created_at?->toISOString(),
    ];
}

    private function resolveCustomer(?string $name, ?string $phone, ?string $email, bool $isAnonymous): Customer
    {
        if ($isAnonymous) {
            return Customer::create([
                'name' => $name ?: 'Anonymous Client',
                'phone' => null,
                'email' => null,
                'member_since' => now()->toDateString(),
                'is_active' => true,
                'is_anonymous' => true,
            ]);
        }

        return Customer::updateOrCreate(
            ['phone' => $phone],
            [
                'name'         => $name,
                'email'        => $email ?: null,
                'member_since' => now()->toDateString(),
                'is_active'    => true,
                'is_anonymous' => false,
            ]
        );
    }

    private function paymentStatus(Booking $booking): string
    {
        $paidTotal = (int) ($booking->invoices?->where('status', 'paid')->sum('total') ?? 0);

        if ($paidTotal <= 0) {
            return 'unpaid';
        }

        if ($booking->requires_deposit && $booking->deposit_required_amount > 0 && $booking->deposit_paid_amount < $booking->deposit_required_amount) {
            return 'partial';
        }

        return 'paid';
    }
}
