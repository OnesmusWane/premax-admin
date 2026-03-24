<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\Request;

class StaffMemberController extends Controller
{
    /**
     * GET /api/admin/staff-members
     */
    public function index()
    {
        return response()->json(
            StaffMember::orderBy('sort_order')->orderBy('name')->get()
        );
    }

    /**
     * POST /api/admin/staff-members
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'role'            => 'required|string|max:100',
            'bio'             => 'nullable|string',
            'email'           => 'nullable|email|max:150',
            'phone'           => 'nullable|string|max:30',
            'avatar_color'    => 'nullable|string|max:20',
            'avatar_url'      => 'nullable|url|max:500',
            'sort_order'      => 'nullable|integer|min:1',
            'show_on_website' => 'boolean',
        ]);

        // Auto-derive initials from name
        $data['initials'] = $this->deriveInitials($data['name']);

        $member = StaffMember::create($data);

        return response()->json($member, 201);
    }

    /**
     * GET /api/admin/staff-members/{staffMember}
     */
    public function show(StaffMember $staffMember)
    {
        return response()->json($staffMember);
    }

    /**
     * PUT /api/admin/staff-members/{staffMember}
     */
    public function update(Request $request, StaffMember $staffMember)
    {
        $data = $request->validate([
            'name'            => 'sometimes|required|string|max:100',
            'role'            => 'sometimes|required|string|max:100',
            'bio'             => 'nullable|string',
            'email'           => 'nullable|email|max:150',
            'phone'           => 'nullable|string|max:30',
            'avatar_color'    => 'nullable|string|max:20',
            'avatar_url'      => 'nullable|url|max:500',
            'sort_order'      => 'nullable|integer|min:1',
            'show_on_website' => 'boolean',
        ]);

        // Re-derive initials if name changed
        if (isset($data['name'])) {
            $data['initials'] = $this->deriveInitials($data['name']);
        }

        $staffMember->update($data);

        return response()->json($staffMember->fresh());
    }

    /**
     * DELETE /api/admin/staff-members/{staffMember}
     */
    public function destroy(StaffMember $staffMember)
    {
        $staffMember->delete();
        return response()->json(['message' => 'Staff member removed.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function deriveInitials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->take(2)
            ->map(fn($w) => strtoupper($w[0] ?? ''))
            ->implode('');
    }
}