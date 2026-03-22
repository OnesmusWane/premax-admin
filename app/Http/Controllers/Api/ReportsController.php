<?php

namespace App\Http\Controllers\Api;

use App\Models\{Invoice, Customer, JobCard};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class ReportsController extends \App\Http\Controllers\Controller {
 
    public function index(Request $request) {
        $days  = $request->days ?? 30;
        $from  = now()->subDays($days)->startOfDay();
 
        $totalRevenue  = Invoice::where('status','paid')->where('paid_at','>=',$from)->sum('total');
        $avgDaily      = $days > 0 ? round($totalRevenue / $days) : 0;
        $totalCustomers= Customer::count();
        $newCustomers  = Customer::where('created_at','>=',$from)->count();
        $mostPopular   = JobCard::where('created_at','>=',$from)->select('service_name',DB::raw('COUNT(*) as count'))->groupBy('service_name')->orderByDesc('count')->first();
 
        // Weekly buckets
        $revenueByWeek = Invoice::where('status','paid')->where('paid_at','>=',$from)
            ->select(DB::raw('WEEK(paid_at) as week'), DB::raw('SUM(total) as total'))
            ->groupBy('week')->orderBy('week')->get();
 
        $serviceBreakdown = JobCard::where('created_at','>=',$from)
            ->select('service_name',DB::raw('COUNT(*) as count'))
            ->groupBy('service_name')->orderByDesc('count')->limit(5)->get();
 
        return response()->json([
            'summary' => [
                'total_revenue'  => $totalRevenue,
                'avg_daily'      => $avgDaily,
                'total_customers'=> $totalCustomers,
                'new_customers'  => $newCustomers,
                'most_popular'   => $mostPopular,
            ],
            'revenue_over_time'  => $revenueByWeek,
            'services_breakdown' => $serviceBreakdown,
        ]);
    }
}