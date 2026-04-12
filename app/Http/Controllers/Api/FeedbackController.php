<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerFeedback;
use App\Models\FeedbackToken;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * GET /api/admin/feedback
     */
    public function index(Request $request)
    {
        $feedback = CustomerFeedback::query()
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%$s%")
                  ->orWhere('vehicle', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
            )
            ->when($request->rating, fn($q, $r) => $q->where('rating', $r))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($feedback);
    }

    /**
     * GET /api/admin/feedback/stats
     */
    public function stats()
    {
        $total        = CustomerFeedback::count();
        $avgRating    = CustomerFeedback::avg('rating') ?? 0;
        $recommendYes = CustomerFeedback::where('recommend', 'yes')->count();
        $thisMonth    = CustomerFeedback::whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->count();

        return response()->json([
            'total'         => $total,
            'avg_rating'    => round($avgRating, 1),
            'recommend_pct' => $total > 0 ? round(($recommendYes / $total) * 100) : 0,
            'this_month'    => $thisMonth,
        ]);
    }

    /**
     * POST /api/admin/feedback
     * Manual entry by staff.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:120',
            'phone'        => 'nullable|string|max:20',
            'vehicle'      => 'nullable|string|max:30',
            'service'      => 'nullable|string|max:100',
            'service_date' => 'nullable|date',
            'rating'       => 'required|integer|min:1|max:5',
            'liked'        => 'nullable|string|max:1000',
            'recommend'    => 'required|in:yes,no',
        ]);

        // Create a used token for tracking
        $token = FeedbackToken::create([
            'token'         => \Illuminate\Support\Str::random(48),
            'customer_name' => $request->name,
            'customer_phone'=> $request->phone,
            'vehicle_reg'   => $request->vehicle,
            'service'       => $request->service,
            'used'          => true,  // pre-mark as used since staff entered it
            'expires_at'    => now(),
        ]);

        $feedback = CustomerFeedback::create([
            'feedback_token_id' => $token->id,
            'name'              => $request->name,
            'phone'             => $request->phone,
            'vehicle'           => strtoupper($request->vehicle ?? ''),
            'service'           => $request->service,
            'rating'            => $request->rating,
            'liked'             => $request->liked,
            'suggestions'       => $request->suggestions,
            'recommend'         => $request->recommend,
        ]);

        return response()->json($feedback, 201);
    }

    /**
     * DELETE /api/admin/feedback/{feedback}
     */
    public function destroy(CustomerFeedback $feedback)
    {
        $feedback->delete();
        return response()->json(['message' => 'Feedback deleted.']);
    }

    /**
     * POST /api/admin/feedback/generate-token
     * Generate a shareable feedback link.
     */
    public function generateToken(Request $request)
    {
        $token = FeedbackToken::generate([
            'customer_name'  => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'vehicle_reg'    => $request->vehicle_reg,
            'service'        => $request->service,
        ]);

        $link = $this->websiteUrl('/feedback/' . $token->token);

        return response()->json([
            'token' => $token->token,
            'link'  => $link,
        ]);
    }

    private function websiteUrl(string $path = ''): string
    {
        $base = rtrim(env('WEBSITE_URL', config('app.url', 'http://localhost:8006')), '/');
        return $base.'/'.ltrim($path, '/');
    }
}
