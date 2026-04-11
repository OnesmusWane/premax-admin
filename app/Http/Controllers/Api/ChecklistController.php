<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleChecklist;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ChecklistController extends Controller
{
    /**
     * GET /api/admin/checklists
     */
    public function index(Request $request)
    {
        $checklists = VehicleChecklist::with(['vehicle', 'customer', 'checkedInBy:id,name', 'checkedOutBy:id,name'])
            ->when($request->vehicle_id,  fn($q, $id) => $q->where('vehicle_id',  $id))
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            ->when($request->job_card_id, fn($q, $id) => $q->where('job_card_id', $id))
            ->when($request->status,      fn($q, $s)  => $q->where('status',      $s))
            ->when($request->search, function ($q, $s) {
                $q->where('sn', 'like', "%$s%")
                  ->orWhere('reg_no', 'like', "%$s%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%$s%"));
            })
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($checklists);
    }

    /**
     * POST /api/admin/checklists
     * Creates a new check-in checklist.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'         => 'required|exists:vehicles,id',
            'customer_id'        => 'required|exists:customers,id',
            'job_card_id'        => 'nullable|exists:job_cards,id',
            'fuel_level'         => 'nullable|in:F,3/4,1/2,1/4,E',
            'odometer'           => 'nullable|integer|min:0',
            'payment_option'     => 'nullable|in:mpesa,cash,insurance,cheque,other',
            'colour'             => 'nullable|string|max:50',
            // JSON sections — each value: { status: ok|missing|damaged, note: string }
            'exterior'           => 'nullable|array',
            'interior'           => 'nullable|array',
            'engine_compartment' => 'nullable|array',
            'extras'             => 'nullable|array',
            'exterior_remarks'   => 'nullable|string|max:2000',
            'interior_remarks'   => 'nullable|string|max:2000',
        ]);

        $vehicle  = Vehicle::findOrFail($request->vehicle_id);
        $customer = Customer::findOrFail($request->customer_id);

        $checklist = VehicleChecklist::create([
            'sn'                 => VehicleChecklist::generateSn(),
            'vehicle_id'         => $vehicle->id,
            'customer_id'        => $customer->id,
            'job_card_id'        => $request->job_card_id,
            'checked_in_by'      => $request->user()->id,

            // Vehicle snapshot
            'reg_no'             => $vehicle->registration,
            'make'               => $vehicle->make,
            'model'              => $vehicle->model,
            'colour'             => $request->colour ?? $vehicle->color,

            // Fuel & mileage
            'fuel_level'         => $request->fuel_level,
            'odometer'           => $request->odometer,
            'payment_option'     => $request->payment_option,

            // Checklist sections — use provided data or fall back to all-OK defaults
            'exterior'           => $this->mergeWithDefaults(
                                        $request->exterior,
                                        VehicleChecklist::defaultExterior()
                                    ),
            'interior'           => $this->mergeWithDefaults(
                                        $request->interior,
                                        VehicleChecklist::defaultInterior()
                                    ),
            'engine_compartment' => $this->mergeWithDefaults(
                                        $request->engine_compartment,
                                        VehicleChecklist::defaultEngineCompartment()
                                    ),
            'extras'             => $this->mergeWithDefaults(
                                        $request->extras,
                                        VehicleChecklist::defaultExtras()
                                    ),

            'exterior_remarks'   => $request->exterior_remarks,
            'interior_remarks'   => $request->interior_remarks,

            'status'             => 'check_in',
            'checked_in_at'      => now(),
        ]);

        return response()->json(
            $checklist->load(['vehicle', 'customer', 'checkedInBy']),
            201
        );
    }

    /**
     * GET /api/admin/checklists/{checklist}
     */
    public function show(VehicleChecklist $checklist)
    {
        return response()->json(
            $checklist->load(['vehicle', 'customer', 'jobCard', 'checkedInBy', 'checkedOutBy'])
        );
    }

    /**
     * PUT /api/admin/checklists/{checklist}
     * Updates checklist items and notes.
     */
    public function update(Request $request, VehicleChecklist $checklist)
    {
        $request->validate([
            'fuel_level'         => 'nullable|in:F,3/4,1/2,1/4,E',
            'odometer'           => 'nullable|integer|min:0',
            'payment_option'     => 'nullable|in:mpesa,cash,insurance,cheque,other',
            'exterior'           => 'nullable|array',
            'interior'           => 'nullable|array',
            'engine_compartment' => 'nullable|array',
            'extras'             => 'nullable|array',
            'exterior_remarks'   => 'nullable|string|max:2000',
            'interior_remarks'   => 'nullable|string|max:2000',
        ]);

        // Merge incoming changes with existing data so untouched items are preserved
        $checklist->update([
            'fuel_level'         => $request->fuel_level         ?? $checklist->fuel_level,
            'odometer'           => $request->odometer           ?? $checklist->odometer,
            'payment_option'     => $request->payment_option     ?? $checklist->payment_option,
            'exterior'           => $request->has('exterior')
                                        ? $this->mergeSection($checklist->exterior, $request->exterior)
                                        : $checklist->exterior,
            'interior'           => $request->has('interior')
                                        ? $this->mergeSection($checklist->interior, $request->interior)
                                        : $checklist->interior,
            'engine_compartment' => $request->has('engine_compartment')
                                        ? $this->mergeSection($checklist->engine_compartment, $request->engine_compartment)
                                        : $checklist->engine_compartment,
            'extras'             => $request->has('extras')
                                        ? $this->mergeSection($checklist->extras, $request->extras)
                                        : $checklist->extras,
            'exterior_remarks'   => $request->has('exterior_remarks')
                                        ? $request->exterior_remarks
                                        : $checklist->exterior_remarks,
            'interior_remarks'   => $request->has('interior_remarks')
                                        ? $request->interior_remarks
                                        : $checklist->interior_remarks,
        ]);

        return response()->json($checklist->fresh(['vehicle', 'customer']));
    }

    /**
     * POST /api/admin/checklists/{checklist}/checkout
     * Fills release info and marks as checked out.
     */
    public function checkout(Request $request, VehicleChecklist $checklist)
    {
        $request->validate([
            'released_from'     => 'nullable|string|max:100',
            'released_to'       => 'nullable|string|max:100',
            'released_by'       => 'nullable|string|max:100',
            'received_by'       => 'nullable|string|max:100',
            'released_by_tel'   => 'nullable|string|max:20',
            'received_by_tel'   => 'nullable|string|max:20',
            'received_by_id'    => 'nullable|string|max:20',
            'release_signature' => 'nullable|string',  // base64
            'receive_signature' => 'nullable|string',  // base64
        ]);

        $checklist->update(array_merge(
            $request->only([
                'released_from', 'released_to',
                'released_by',   'received_by',
                'released_by_tel','received_by_tel','received_by_id',
                'release_signature','receive_signature',
            ]),
            [
                'checked_out_by' => $request->user()->id,
                'status'         => 'check_out',
                'checked_out_at' => now(),
            ]
        ));

        return response()->json(
            $checklist->fresh(['vehicle', 'customer', 'checkedInBy', 'checkedOutBy'])
        );
    }

    /**
     * DELETE /api/admin/checklists/{checklist}
     */
    public function destroy(VehicleChecklist $checklist)
    {
        $checklist->delete();
        return response()->json(['message' => 'Checklist deleted.']);
    }

    /**
     * GET /api/admin/checklists/{checklist}/summary
     * Returns a summary of flagged items for quick review.
     */
    public function summary(VehicleChecklist $checklist)
    {
        $flagged = [];

        $sections = [
            'exterior'           => $checklist->exterior           ?? [],
            'interior'           => $checklist->interior           ?? [],
            'engine_compartment' => $checklist->engine_compartment ?? [],
            'extras'             => $checklist->extras             ?? [],
        ];

        foreach ($sections as $section => $items) {
            foreach ($items as $key => $item) {
                if (($item['status'] ?? 'ok') !== 'ok') {
                    $flagged[] = [
                        'section' => $section,
                        'key'     => $key,
                        'status'  => $item['status'],
                        'note'    => $item['note'] ?? null,
                    ];
                }
            }
        }

        return response()->json([
            'sn'            => $checklist->sn,
            'reg_no'        => $checklist->reg_no,
            'total_flagged' => count($flagged),
            'damaged'       => collect($flagged)->where('status', 'damaged')->values(),
            'missing'       => collect($flagged)->where('status', 'missing')->values(),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Merge incoming section data with defaults.
     * Incoming items override defaults; missing keys get defaults.
     */
    private function mergeWithDefaults(?array $incoming, array $defaults): array
    {
        if (!$incoming) return $defaults;

        foreach ($incoming as $key => $value) {
            $defaults[$key] = [
                'status' => $value['status'] ?? 'ok',
                'note'   => $value['note']   ?? '',
            ];
        }

        return $defaults;
    }

    /**
     * Merge new values into existing section data.
     * Preserves existing items not present in the new data.
     */
    private function mergeSection(?array $existing, array $incoming): array
    {
        $existing = $existing ?? [];

        foreach ($incoming as $key => $value) {
            $existing[$key] = [
                'status' => $value['status'] ?? $existing[$key]['status'] ?? 'ok',
                'note'   => $value['note']   ?? $existing[$key]['note']   ?? '',
            ];
        }

        return $existing;
    }
}
