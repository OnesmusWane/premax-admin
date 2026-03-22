<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Booking, BookingStatus, Invoice, InventoryItem};
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
 
class DashboardController extends Controller
{
    public function index()
    {
        // ── Stat cards ─────────────────────────────────────────────────────────
        $pendingStatus    = BookingStatus::where('slug', 'pending')->value('id');
        $inProgressStatus = BookingStatus::where('slug', 'in_progress')->value('id');
        $confirmedStatus  = BookingStatus::where('slug', 'confirmed')->value('id');
 
        $stats = [
            // Distinct vehicles serviced today (completed or in-progress bookings)
            'cars_today' => Booking::whereDate('scheduled_at', today())
                ->whereHas('status', fn($q) => $q->whereIn('slug', ['complete','inprogress','confirmed']))
                ->distinct('vehicle_id')
                ->count('vehicle_id'),
 
            // Bookings currently in progress or confirmed (active jobs)
            'active_jobs' => Booking::whereHas('status', fn($q) =>
                $q->whereIn('slug', ['inprogress', 'confirmed'])
            )->count(),
 
            // Revenue collected today
            'revenue_today' => Invoice::whereDate('paid_at', today())
                ->where('status', 'paid')
                ->sum('total'),
 
            // Pending bookings
            'pending_bookings' => Booking::where('booking_status_id', $pendingStatus)->count(),
 
            // Items at or below reorder level
            'low_stock' => InventoryItem::whereColumn('stock_qty', '<=', 'reorder_level')
                ->where('is_active', true)
                ->count(),
        ];
 
        // ── Weekly revenue (Mon–Sun of current week) ───────────────────────────
        $weeklyRevenue = collect(range(0, 6))->map(function ($offset) {
            $day   = now()->startOfWeek()->addDays($offset);
            $total = Invoice::whereDate('paid_at', $day->toDateString())
                ->where('status', 'paid')
                ->sum('total');
            return [
                'label' => $day->format('D'),   // Mon, Tue…
                'date'  => $day->toDateString(),
                'total' => $total,
            ];
        });
 
        // ── Service popularity (last 30 days) ──────────────────────────────────
        $servicePopularity = Booking::query()
            ->select('service_id', DB::raw('count(*) as count'))
            ->whereNotNull('service_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->limit(6)
            ->with('service:id,name')
            ->get()
            ->map(fn($b) => [
                'service_name' => $b->service?->name ?? 'Unknown',
                'count'        => $b->count,
            ]);
           
        // ── Recent bookings (today or upcoming) ────────────────────────────────
        $recentBookings = Booking::with(['customer', 'vehicle', 'service', 'status'])
            ->whereDate('scheduled_at', today())
            ->latest('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn($b) => [
                'id'           => $b->id,
                'reference'    => $b->reference,
                'scheduled_at' => $b->scheduled_at?->toISOString(),
                'customer'     => ['name' => $b->customer?->name, 'phone' => $b->customer?->phone],
                'vehicle'      => ['registration' => $b->vehicle?->registration],
                'service'      => ['name' => $b->service?->name ?? '—'],
                'status'       => ['slug' => $b->status?->slug, 'name' => $b->status?->name],
            ]);
 
        // ── Inventory alerts ───────────────────────────────────────────────────
        $inventoryAlerts = InventoryItem::whereColumn('stock_qty', '<=', 'reorder_level')
            ->where('is_active', true)
            ->orderBy('stock_qty')
            ->limit(10)
            ->get(['id', 'name', 'stock_qty', 'reorder_level']);
 
        return response()->json([
            'stats'               => $stats,
            'weekly_revenue'      => $weeklyRevenue,
            'service_popularity'  => $servicePopularity,
            'recent_bookings'     => $recentBookings,
            'inventory_alerts'    => $inventoryAlerts,
        ]);
    }
}