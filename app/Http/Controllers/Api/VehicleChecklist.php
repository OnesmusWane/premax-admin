<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\{VehicleChecklist, Vehicle, Customer};
use Illuminate\Http\Request;
 
class ChecklistController extends Controller
{
    /**
     * Create a new check-in checklist for a vehicle.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id'     => 'required|exists:vehicles,id',
            'customer_id'    => 'required|exists:customers,id',
            'job_card_id'    => 'nullable|exists:job_cards,id',
            'fuel_level'     => 'nullable|in:F,3/4,1/2,1/4,E',
            'odometer'       => 'nullable|integer',
            'payment_option' => 'nullable|in:mpesa,cash,insurance,cheque,other',
            'colour'         => 'nullable|string',
        ]);
 
        $vehicle  = Vehicle::findOrFail($request->vehicle_id);
        $customer = Customer::findOrFail($request->customer_id);
 
        $checklist = VehicleChecklist::create([
            'sn'                 => VehicleChecklist::generateSn(),
            'vehicle_id'         => $vehicle->id,
            'customer_id'        => $customer->id,
            'job_card_id'        => $request->job_card_id,
            'checked_in_by'      => $request->user()->id,
            'reg_no'             => $vehicle->registration,
            'make'               => $vehicle->make,
            'model'              => $vehicle->model,
            'colour'             => $request->colour ?? $vehicle->color,
            'fuel_level'         => $request->fuel_level,
            'odometer'           => $request->odometer,
            'payment_option'     => $request->payment_option,
            'exterior'           => VehicleChecklist::defaultExterior(),
            'interior'           => VehicleChecklist::defaultInterior(),
            'engine_compartment' => VehicleChecklist::defaultEngineCompartment(),
            'extras'             => VehicleChecklist::defaultExtras(),
            'status'             => 'check_in',
            'checked_in_at'      => now(),
        ]);
 
        return response()->json($checklist->load(['vehicle', 'customer', 'checkedInBy']), 201);
    }
 
    /**
     * Get a checklist by ID.
     */
    public function show(VehicleChecklist $checklist)
    {
        return response()->json($checklist->load(['vehicle', 'customer', 'jobCard', 'checkedInBy', 'checkedOutBy']));
    }
 
    /**
     * Update checklist items (during inspection).
     */
    public function update(Request $request, VehicleChecklist $checklist)
    {
        $checklist->update($request->only([
            'exterior', 'interior', 'engine_compartment', 'extras',
            'exterior_remarks', 'interior_remarks',
            'fuel_level', 'odometer', 'payment_option',
        ]));
 
        return response()->json($checklist->fresh());
    }
 
    /**
     * Complete check-out — fill release info and mark as checked out.
     */
    public function checkout(Request $request, VehicleChecklist $checklist)
    {
        $request->validate([
            'released_from'    => 'nullable|string',
            'released_to'      => 'nullable|string',
            'released_by'      => 'nullable|string',
            'received_by'      => 'nullable|string',
            'released_by_tel'  => 'nullable|string',
            'received_by_tel'  => 'nullable|string',
            'received_by_id'   => 'nullable|string',
            'release_signature'=> 'nullable|string',
            'receive_signature'=> 'nullable|string',
        ]);
 
        $checklist->update(array_merge(
            $request->only([
                'released_from', 'released_to', 'released_by', 'received_by',
                'released_by_tel', 'received_by_tel', 'received_by_id',
                'release_signature', 'receive_signature',
            ]),
            [
                'checked_out_by' => $request->user()->id,
                'status'         => 'check_out',
                'checked_out_at' => now(),
            ]
        ));
 
        return response()->json($checklist->fresh(['vehicle', 'customer', 'checkedInBy', 'checkedOutBy']));
    }
 
    /**
     * List checklists — filterable by vehicle or job card.
     */
    public function index(Request $request)
    {
        return response()->json(
            VehicleChecklist::with(['vehicle', 'customer'])
                ->when($request->vehicle_id, fn($q, $id) => $q->where('vehicle_id', $id))
                ->when($request->job_card_id, fn($q, $id) => $q->where('job_card_id', $id))
                ->when($request->status, fn($q, $s) => $q->where('status', $s))
                ->latest()
                ->paginate(20)
        );
    }
 
    /**
     * Generate printable PDF for a checklist.
     * Route: GET /api/admin/checklists/{checklist}/pdf
     */
    public function pdf(VehicleChecklist $checklist)
    {
        $checklist->load(['vehicle', 'customer', 'checkedInBy', 'checkedOutBy']);
        return response()->json(['print_url' => route('admin.checklist.print', $checklist->id)]);
    }
}
