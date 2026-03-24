<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * GET /api/admin/reviews
     */
    public function index(Request $request)
    {
        $reviews = Review::query()
            ->when($request->status,   fn($q, $s) => $q->where('status', $s))
            ->when($request->source,   fn($q, $s) => $q->where('source', $s))
            ->when($request->featured, fn($q)     => $q->where('is_featured', true))
            ->when($request->search,   fn($q, $s) =>
                $q->where('reviewer_name', 'like', "%$s%")
                  ->orWhere('body', 'like', "%$s%")
            )
            ->orderByDesc('reviewed_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reviews);
    }

    /**
     * POST /api/admin/reviews
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'reviewer_name'         => 'required|string|max:100',
            'reviewer_initials'     => 'nullable|string|max:5',
            'reviewer_avatar_color' => 'nullable|string|max:20',
            'reviewer_avatar_url'   => 'nullable|url|max:500',
            'reviewer_email'        => 'nullable|email|max:150',
            'reviewer_phone'        => 'nullable|string|max:30',
            'rating'                => 'required|integer|min:1|max:5',
            'body'                  => 'required|string',
            'source'                => 'nullable|string|in:website,google,facebook,whatsapp,walk_in,other',
            'status'                => 'nullable|string|in:pending,approved,rejected',
            'is_featured'           => 'boolean',
            'is_verified_customer'  => 'boolean',
            'show_on_website'       => 'boolean',
            'reviewed_at'           => 'nullable|date',
            'service_id'            => 'nullable|exists:services,id',
        ]);

        // Auto-derive initials if not supplied
        if (empty($data['reviewer_initials'])) {
            $data['reviewer_initials'] = $this->deriveInitials($data['reviewer_name']);
        }

        // Default reviewed_at to now
        $data['reviewed_at'] = $data['reviewed_at'] ?? now();
        $data['status']      = $data['status']      ?? 'pending';

        $review = Review::create($data);

        return response()->json($review, 201);
    }

    /**
     * GET /api/admin/reviews/{review}
     */
    public function show(Review $review)
    {
        return response()->json($review);
    }

    /**
     * PUT /api/admin/reviews/{review}
     */
    public function update(Request $request, Review $review)
    {
        $data = $request->validate([
            'reviewer_name'         => 'sometimes|required|string|max:100',
            'reviewer_initials'     => 'nullable|string|max:5',
            'reviewer_avatar_color' => 'nullable|string|max:20',
            'reviewer_avatar_url'   => 'nullable|url|max:500',
            'reviewer_email'        => 'nullable|email|max:150',
            'reviewer_phone'        => 'nullable|string|max:30',
            'rating'                => 'sometimes|required|integer|min:1|max:5',
            'body'                  => 'sometimes|required|string',
            'source'                => 'nullable|string|in:website,google,facebook,whatsapp,walk_in,other',
            'status'                => 'nullable|string|in:pending,approved,rejected',
            'is_featured'           => 'boolean',
            'is_verified_customer'  => 'boolean',
            'show_on_website'       => 'boolean',
            'reviewed_at'           => 'nullable|date',
            'service_id'            => 'nullable|exists:services,id',
            'owner_response'        => 'nullable|string',
            'moderation_note'       => 'nullable|string',
        ]);

        // Re-derive initials if name changed and initials not explicitly provided
        if (isset($data['reviewer_name']) && empty($data['reviewer_initials'])) {
            $data['reviewer_initials'] = $this->deriveInitials($data['reviewer_name']);
        }

        // If approving and show_on_website not set, default to true
        if (isset($data['status']) && $data['status'] === 'approved' && !isset($data['show_on_website'])) {
            $data['show_on_website'] = true;
        }

        // Track owner response timestamp
        if (isset($data['owner_response']) && $data['owner_response'] !== $review->owner_response) {
            $data['owner_responded_at'] = now();
        }

        $review->update($data);

        return response()->json($review->fresh());
    }

    /**
     * DELETE /api/admin/reviews/{review}
     */
    public function destroy(Review $review)
    {
        $review->delete();
        return response()->json(['message' => 'Review deleted.']);
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