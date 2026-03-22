<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
 
class ServiceCategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            ServiceCategory::where('is_active', true)->orderBy('sort_order')->get()
        );
    }
 
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:20',
        ]);
 
        $cat = ServiceCategory::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'color'       => $request->color ?? '#DC2626',
            'icon'        => $request->icon ?? 'wrench',
            'is_active'   => true,
            'sort_order'  => ServiceCategory::max('sort_order') + 1,
        ]);
 
        return response()->json($cat, 201);
    }
 
    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $request->validate([
            'name'        => 'sometimes|string|max:150',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|max:20',
        ]);
 
        $serviceCategory->update($request->only(['name', 'description', 'color', 'icon', 'sort_order']));
 
        return response()->json($serviceCategory->fresh());
    }
}
