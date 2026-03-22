<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * GET /api/admin/vehicles
     */
    public function index(Request $request)
    {
        $vehicles = Vehicle::with(['customer'])
            ->withCount('bookings')
            ->when($request->search, function ($q, $s) {
                $q->where('registration', 'like', "%$s%")
                  ->orWhere('make', 'like', "%$s%")
                  ->orWhere('model', 'like', "%$s%")
                  ->orWhereHas('customer', fn($q) =>
                      $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")
                  );
            })
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($vehicles);
    }

    /**
     * GET /api/admin/vehicles/{vehicle}
     */
    public function show(Vehicle $vehicle)
    {
        return response()->json(
            $vehicle->load(['customer'])
        );
    }

    /**
     * PUT /api/admin/vehicles/{vehicle}
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'registration'   => 'sometimes|string|max:20',
            'make'           => 'sometimes|string|max:100',
            'model'          => 'sometimes|string|max:100',
            'year'           => 'nullable|integer|min:1900|max:2099',
            'color'          => 'nullable|string|max:50',
            'engine_number'  => 'nullable|string|max:100',
            'chassis_number' => 'nullable|string|max:100',
        ]);

        $vehicle->update($request->only([
            'registration', 'make', 'model', 'year',
            'color', 'engine_number', 'chassis_number',
        ]));

        return response()->json($vehicle->fresh(['customer']));
    }

    /**
     * POST /api/admin/vehicles/{vehicle}/inspections
     */
    public function addInspection(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'mileage' => 'nullable|integer',
            'notes'   => 'nullable|string',
        ]);

        $inspection = $vehicle->inspections()->create([
            'admin_user_id' => $request->user()->id,
            'mileage'       => $request->mileage,
            'notes'         => $request->notes,
            'inspected_at'  => now(),
        ]);

        return response()->json($inspection->load('inspector'), 201);
    }

    /**
     * GET /api/admin/vehicles/{vehicle}/history
     * Returns all bookings for this vehicle with checklist and invoice summaries.
     */
    public function history(Vehicle $vehicle)
    {
        $bookings = $vehicle->bookings()
            ->with(['service', 'status', 'source', 'checklist', 'invoices'])
            ->latest('scheduled_at')
            ->get()
            ->map(function ($b) {
                return [
                    'id'           => $b->id,
                    'reference'    => $b->reference,
                    'scheduled_at' => $b->scheduled_at?->toISOString(),
                    'notes'        => $b->customer_notes,
                    'source'       => $b->source?->name,
                    'service'      => [
                        'id'   => $b->service?->id,
                        'name' => $b->service?->name ?? '—',
                    ],
                    'status'       => [
                        'slug' => $b->status?->slug ?? 'pending',
                        'name' => $b->status?->name ?? 'Pending',
                    ],
                    // Checklist summary
                    'checklist'    => $b->checklist ? [
                        'id'             => $b->checklist->id,
                        'sn'             => $b->checklist->sn,
                        'status'         => $b->checklist->status,
                        'fuel_level'     => $b->checklist->fuel_level,
                        'odometer'       => $b->checklist->odometer,
                        'checked_in_at'  => $b->checklist->checked_in_at?->toISOString(),
                        'checked_out_at' => $b->checklist->checked_out_at?->toISOString(),
                        // Count flagged items
                        'flagged_count'  => $this->countFlagged($b->checklist),
                        'exterior'       => $b->checklist->exterior,
                        'interior'       => $b->checklist->interior,
                        'engine_compartment' => $b->checklist->engine_compartment,
                        'extras'         => $b->checklist->extras,
                    ] : null,
                    // Invoice summary
                    'invoices' => $b->invoices->map(fn($inv) => [
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'total'          => $inv->total,
                    'payment_method' => $inv->payment_method,
                    'mpesa_reference'=> $inv->mpesa_reference,
                    'status'         => $inv->status,
                    'paid_at'        => $inv->paid_at?->toISOString(),
                    ]),
                    'total_paid' => $b->invoices->where('status', 'paid')->sum('total'),
                        ];
            });

        return response()->json($bookings);
    }

    /**
     * Count flagged (damaged + missing) items across all checklist sections.
     */
    private function countFlagged($checklist): int
    {
        $count = 0;
        foreach (['exterior', 'interior', 'engine_compartment', 'extras'] as $section) {
            foreach ($checklist->{$section} ?? [] as $item) {
                if (($item['status'] ?? 'ok') !== 'ok') $count++;
            }
        }
        return $count;
    }
}