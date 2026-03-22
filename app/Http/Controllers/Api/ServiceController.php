<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
 
class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with('serviceCategory')
            ->when($request->category_id, fn($q, $id) => $q->where('service_category_id', $id))
            ->when($request->active_only, fn($q) => $q->where('is_active', true))
            ->orderBy('service_category_id')
            ->orderBy('sort_order')
            ->when($request->per_page, fn($q) => $q->paginate($request->per_page),
                fn($q) => $q->get()
            );
 
        return response()->json($services);
    }
 
    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:200',
            'service_category_id' => 'required|exists:service_categories,id',
            'description'         => 'nullable|string|max:1000',
            'price_from'          => 'nullable|integer|min:0',
            'price_to'            => 'nullable|integer|min:0',
            'duration_minutes'    => 'nullable|integer|min:1',
            'is_popular'          => 'boolean',
        ]);
 
        $service = Service::create([
            'name'                => $request->name,
            'slug'                => Str::slug($request->name),
            'service_category_id' => $request->service_category_id,
            'description'         => $request->description,
            'price_from'          => $request->price_from,
            'price_to'            => $request->price_to,
            'price_is_estimate'   => true,
            'duration_minutes'    => $request->duration_minutes,
            'is_popular'          => $request->boolean('is_popular'),
            'is_active'           => true,
            'sort_order'          => Service::where('service_category_id', $request->service_category_id)->max('sort_order') + 1,
        ]);
 
        return response()->json($service->load('serviceCategory'), 201);
    }
 
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name'                => 'sometimes|string|max:200',
            'service_category_id' => 'sometimes|exists:service_categories,id',
            'description'         => 'nullable|string|max:1000',
            'price_from'          => 'nullable|integer|min:0',
            'price_to'            => 'nullable|integer|min:0',
            'duration_minutes'    => 'nullable|integer|min:1',
            'is_popular'          => 'boolean',
            'is_active'           => 'boolean',
        ]);
 
        $service->update($request->only([
            'name', 'service_category_id', 'description',
            'price_from', 'price_to', 'duration_minutes',
            'is_popular', 'is_active',
        ]));
 
        return response()->json($service->fresh('category'));
    }
 
    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['message' => 'Service deleted.']);
    }
}
