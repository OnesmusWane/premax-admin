<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\BookingSource;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Service;
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
        'customer_name'   => 'required|string|max:150',
        'customer_phone'  => 'required|string|max:20',
        'customer_email'  => 'nullable|email|max:150',
        'vehicle_reg'     => 'required|string|max:20',
        'vehicle_make'    => 'nullable|string|max:100',
        'vehicle_model'   => 'nullable|string|max:100',
        'service_id'      => 'nullable|exists:services,id',
        'scheduled_at'    => 'required|date',
        'notes'           => 'nullable|string|max:1000',
        'source'          => 'nullable|string',
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
 
        // ── 1. Upsert customer ─────────────────────────────────────────────
        $customer = Customer::updateOrCreate(
            ['phone' => $request->customer_phone],
            [
                'name'         => $request->customer_name,
                'email'        => $request->customer_email ?? null,
                'member_since' => now()->toDateString(),
                'is_active'    => true,
            ]
        );
 
        // ── 2. Upsert vehicle ──────────────────────────────────────────────
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
 
        // ── 3. Resolve service / status / source ───────────────────────────
        $service = $request->service_id ? Service::find($request->service_id) : null;
        $status  = BookingStatus::where('slug', 'pending')->first();
        $source  = BookingSource::where('slug', $request->source ?? 'walk_in')->first()
                ?? BookingSource::where('slug', 'walk_in')->first();
 
        // ── 4. Create booking ──────────────────────────────────────────────
        $booking = Booking::create([
            'reference'         => Booking::generateReference(),
            'customer_id'       => $customer->id,
            'vehicle_id'        => $vehicle->id,
            'service_id'        => $service?->id,
            'booking_status_id' => $status?->id,
            'booking_source_id' => $source?->id,
            'scheduled_at'      => Carbon::parse($request->scheduled_at),
            'customer_notes'    => $request->notes,
            'checklist_id'      => null,
        ]);
 
        // ── 5. Create checklist if provided ───────────────────────────────
        // Checklist is created whether staff fills it in or just toggles it on
        // If toggled on but not filled, defaults to all-OK
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
 
            // ── 6. Link checklist to booking ───────────────────────────────
            $booking->update(['checklist_id' => $checklist->id]);
        }
 
        return response()->json(
            $this->transform(
                $booking->load(['customer', 'vehicle', 'service', 'status', 'source', 'checklist'])
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
            $this->transform($booking->load(['customer', 'vehicle', 'service', 'status', 'source', 'checklist']))
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
            $this->transform($booking->fresh(['customer', 'vehicle', 'service', 'status', 'source', 'checklist']))
        );
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
            'name'  => $b->customer?->name  ?? '—',
            'phone' => $b->customer?->phone ?? '—',
            'email' => $b->customer?->email,
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
            'payment_method' => $inv->payment_method,
            'mpesa_reference'=> $inv->mpesa_reference,
            'status'         => $inv->status,
            'paid_at'        => $inv->paid_at?->toISOString(),
        ]) ?? [],
        // Add a convenience flag:
        'is_paid' => $b->invoices?->where('status', 'paid')->count() > 0,
        'created_at'    => $b->created_at?->toISOString(),
    ];
}
}