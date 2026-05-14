<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialPostTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostAnalyticsController extends Controller
{
    /**
     * Return a heatmap showing the best day and hour to post for a given platform.
     * GET /api/analytics/best-time-to-post?platform=facebook&days=90
     */
    public function bestTimeToPost(Request $request)
    {
        $platform = $request->string('platform')->toString() ?: 'all';
        $days     = (int) $request->input('days', 90);
        $days     = min(max($days, 7), 365);
        $since    = now()->subDays($days);

        // Aggregate by day-of-week and hour using MySQL date functions
        $rows = SocialPostTarget::query()
            ->select([
                DB::raw("DATE_FORMAT(social_post_targets.published_at, '%w') as day_of_week"),
                DB::raw('HOUR(social_post_targets.published_at) as hour_of_day'),
                DB::raw('COUNT(*) as post_count'),
                DB::raw('AVG(COALESCE(social_post_targets.likes_count, 0) + COALESCE(social_post_targets.comments_count, 0) + COALESCE(social_post_targets.shares_count, 0)) as avg_engagement'),
            ])
            ->join('social_accounts', 'social_post_targets.social_account_id', '=', 'social_accounts.id')
            ->where('social_post_targets.status', 'published')
            ->whereNotNull('social_post_targets.published_at')
            ->where('social_post_targets.published_at', '>=', $since)
            ->when($platform !== 'all', fn ($q) => $q->where('social_accounts.platform', $platform))
            ->groupBy('day_of_week', 'hour_of_day')
            ->get();

        $totalAnalyzed = SocialPostTarget::query()
            ->join('social_accounts', 'social_post_targets.social_account_id', '=', 'social_accounts.id')
            ->where('social_post_targets.status', 'published')
            ->whereNotNull('social_post_targets.published_at')
            ->where('social_post_targets.published_at', '>=', $since)
            ->when($platform !== 'all', fn ($q) => $q->where('social_accounts.platform', $platform))
            ->count();

        // Build lookup map [day][hour] => {avg_engagement, post_count}
        $dataMap = [];
        foreach ($rows as $row) {
            $dataMap[(int) $row->day_of_week][(int) $row->hour_of_day] = [
                'avg_engagement' => round((float) $row->avg_engagement, 2),
                'post_count'     => (int) $row->post_count,
            ];
        }

        $dayNames        = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $heatmap         = [];
        $bestEngagement  = -1;
        $bestDay         = null;
        $bestHour        = null;
        $allSlots        = [];

        for ($day = 0; $day <= 6; $day++) {
            $hours    = [];
            $dayTotal = 0;
            $dayCount = 0;

            for ($hour = 0; $hour <= 23; $hour++) {
                $slot          = $dataMap[$day][$hour] ?? ['avg_engagement' => 0, 'post_count' => 0];
                $avgEngagement = $slot['avg_engagement'];
                $postCount     = $slot['post_count'];

                $hours[] = [
                    'hour'           => $hour,
                    'label'          => $this->hourLabel($hour),
                    'avg_engagement' => $avgEngagement,
                    'post_count'     => $postCount,
                ];

                if ($postCount > 0) {
                    $dayTotal += $avgEngagement;
                    $dayCount++;

                    $allSlots[] = [
                        'day'            => $dayNames[$day],
                        'hour'           => $this->hourLabel($hour),
                        'avg_engagement' => $avgEngagement,
                        'post_count'     => $postCount,
                    ];

                    if ($avgEngagement > $bestEngagement) {
                        $bestEngagement = $avgEngagement;
                        $bestDay        = $day;
                        $bestHour       = $hour;
                    }
                }
            }

            $heatmap[] = [
                'day'      => $day,
                'day_name' => $dayNames[$day],
                'hours'    => $hours,
                'day_avg'  => $dayCount > 0 ? round($dayTotal / $dayCount, 2) : 0,
            ];
        }

        // Top 5 slots sorted by average engagement
        usort($allSlots, fn ($a, $b) => $b['avg_engagement'] <=> $a['avg_engagement']);
        $topSlots = array_slice($allSlots, 0, 5);

        $bestDayLabel  = $bestDay !== null ? $dayNames[$bestDay] : null;
        $bestHourLabel = $bestHour !== null ? $this->hourLabel($bestHour) : null;
        $bestSlot      = ($bestDayLabel && $bestHourLabel) ? "{$bestDayLabel} at {$bestHourLabel}" : null;

        $recommendations = $this->buildRecommendations($heatmap, $totalAnalyzed, $bestDayLabel, $bestHour);

        Log::info('Best time to post analytics generated', [
            'platform'       => $platform,
            'days'           => $days,
            'total_analyzed' => $totalAnalyzed,
        ]);

        return response()->json([
            'platform'             => $platform,
            'days_analyzed'        => $days,
            'total_posts_analyzed' => $totalAnalyzed,
            'best_day'             => $bestDayLabel,
            'best_hour'            => $bestHourLabel,
            'best_slot'            => $bestSlot,
            'heatmap'              => $heatmap,
            'top_slots'            => $topSlots,
            'recommendations'      => $recommendations,
        ]);
    }

    /**
     * Return engagement totals, averages, top posts, and daily trend.
     * GET /api/analytics/engagement-summary?platform=facebook&days=30
     */
    public function postEngagementSummary(Request $request)
    {
        $platform = $request->string('platform')->toString() ?: 'all';
        $days     = (int) $request->input('days', 30);
        $days     = min(max($days, 1), 365);
        $since    = now()->subDays($days);

        $baseQuery = fn () => SocialPostTarget::query()
            ->join('social_accounts', 'social_post_targets.social_account_id', '=', 'social_accounts.id')
            ->join('social_posts', 'social_post_targets.social_post_id', '=', 'social_posts.id')
            ->where('social_post_targets.status', 'published')
            ->whereNotNull('social_post_targets.published_at')
            ->where('social_post_targets.published_at', '>=', $since)
            ->when($platform !== 'all', fn ($q) => $q->where('social_accounts.platform', $platform));

        $totals = $baseQuery()
            ->select([
                DB::raw('COUNT(*) as total_posts'),
                DB::raw('SUM(COALESCE(social_post_targets.likes_count, 0)) as total_likes'),
                DB::raw('SUM(COALESCE(social_post_targets.comments_count, 0)) as total_comments'),
                DB::raw('SUM(COALESCE(social_post_targets.shares_count, 0)) as total_shares'),
            ])
            ->first();

        $totalPosts    = (int) ($totals->total_posts ?? 0);
        $totalLikes    = (int) ($totals->total_likes ?? 0);
        $totalComments = (int) ($totals->total_comments ?? 0);
        $totalShares   = (int) ($totals->total_shares ?? 0);

        $topPosts = $baseQuery()
            ->select([
                'social_posts.id as post_id',
                'social_posts.title',
                'social_posts.content',
                'social_posts.media',
                'social_accounts.platform',
                'social_post_targets.published_at',
                'social_post_targets.likes_count',
                'social_post_targets.comments_count',
                'social_post_targets.shares_count',
                DB::raw('(COALESCE(social_post_targets.likes_count, 0) + COALESCE(social_post_targets.comments_count, 0) + COALESCE(social_post_targets.shares_count, 0)) as total_engagement'),
            ])
            ->orderByDesc('total_engagement')
            ->limit(5)
            ->get();

        $trend = $baseQuery()
            ->select([
                DB::raw('DATE(social_post_targets.published_at) as date'),
                DB::raw('SUM(COALESCE(social_post_targets.likes_count, 0) + COALESCE(social_post_targets.comments_count, 0) + COALESCE(social_post_targets.shares_count, 0)) as engagement'),
            ])
            ->groupBy(DB::raw('DATE(social_post_targets.published_at)'))
            ->orderBy('date')
            ->get();

        Log::info('Post engagement summary generated', [
            'platform'    => $platform,
            'days'        => $days,
            'total_posts' => $totalPosts,
        ]);

        return response()->json([
            'period_days'           => $days,
            'platform'              => $platform,
            'total_posts'           => $totalPosts,
            'total_likes'           => $totalLikes,
            'total_comments'        => $totalComments,
            'total_shares'          => $totalShares,
            'avg_likes_per_post'    => $totalPosts > 0 ? round($totalLikes / $totalPosts, 1) : 0,
            'avg_comments_per_post' => $totalPosts > 0 ? round($totalComments / $totalPosts, 1) : 0,
            'avg_shares_per_post'   => $totalPosts > 0 ? round($totalShares / $totalPosts, 1) : 0,
            'top_performing_posts'  => $topPosts->map(function ($p) {
                $media     = is_string($p->media) ? json_decode($p->media, true) : ($p->media ?? []);
                $firstMedia = is_array($media) ? ($media[0] ?? null) : null;
                $isVideo    = $firstMedia && preg_match('/\.(mp4|mov|avi|webm|wmv|mkv)(\?|$)/i', $firstMedia);

                return [
                    'post_id'          => $p->post_id,
                    'title'            => $p->title ?: 'Untitled post',
                    'content'          => $p->content ? mb_substr($p->content, 0, 200) : null,
                    'platform'         => $p->platform,
                    'media_url'        => $firstMedia,
                    'media_is_video'   => (bool) $isVideo,
                    'published_at'     => optional(Carbon::parse($p->published_at))?->toISOString(),
                    'likes'            => (int) ($p->likes_count ?? 0),
                    'comments'         => (int) ($p->comments_count ?? 0),
                    'shares'           => (int) ($p->shares_count ?? 0),
                    'total_engagement' => (int) $p->total_engagement,
                ];
            })->values()->all(),
            'engagement_trend'      => $trend->map(fn ($t) => [
                'date'       => $t->date,
                'engagement' => (int) $t->engagement,
            ])->values()->all(),
        ]);
    }

    private function hourLabel(int $hour): string
    {
        if ($hour === 0)  return '12:00 AM';
        if ($hour === 12) return '12:00 PM';
        if ($hour < 12)  return "{$hour}:00 AM";
        return ($hour - 12) . ':00 PM';
    }

    /**
     * Build dynamic textual recommendations based on heatmap data.
     */
    private function buildRecommendations(array $heatmap, int $totalAnalyzed, ?string $bestDay, ?int $bestHour): array
    {
        if ($totalAnalyzed === 0) {
            return ['No data yet. Publish some posts and come back to see recommendations.'];
        }

        $recommendations = [];

        if ($totalAnalyzed < 5) {
            $recommendations[] = 'Data is based on fewer than 5 posts — results may not be statistically significant yet.';
        }

        if ($bestDay) {
            $recommendations[] = "Your best day to post is {$bestDay}.";
        }

        // Compute average engagement per time-of-day bucket
        $buckets = [
            'morning'   => ['label' => 'Morning (6am–11am)',   'total' => 0.0, 'count' => 0],
            'afternoon' => ['label' => 'Afternoon (12pm–4pm)', 'total' => 0.0, 'count' => 0],
            'evening'   => ['label' => 'Evening (5pm–8pm)',    'total' => 0.0, 'count' => 0],
            'night'     => ['label' => 'Night (9pm–5am)',      'total' => 0.0, 'count' => 0],
        ];

        foreach ($heatmap as $dayData) {
            foreach ($dayData['hours'] as $hourData) {
                if ($hourData['post_count'] === 0) {
                    continue;
                }

                $key = $this->timeBucket($hourData['hour']);
                $buckets[$key]['total'] += $hourData['avg_engagement'];
                $buckets[$key]['count']++;
            }
        }

        $bucketAvgs = array_map(
            fn ($b) => ['label' => $b['label'], 'avg' => $b['count'] > 0 ? $b['total'] / $b['count'] : 0.0],
            $buckets
        );
        usort($bucketAvgs, fn ($a, $b) => $b['avg'] <=> $a['avg']);

        $bestBucket  = $bucketAvgs[0];
        $worstBucket = end($bucketAvgs);

        if ($bestBucket['avg'] > 0) {
            $recommendations[] = "{$bestBucket['label']} posts get the most engagement.";

            if ($worstBucket['avg'] > 0 && $bestBucket['avg'] > $worstBucket['avg']) {
                $ratio             = round($bestBucket['avg'] / $worstBucket['avg'], 1);
                $recommendations[] = "{$bestBucket['label']} posts get {$ratio}x more engagement than {$worstBucket['label']}.";
            }
        }

        // Highlight the worst active day
        $activeDays = collect($heatmap)
            ->filter(fn ($d) => $d['day_avg'] > 0)
            ->pluck('day_avg', 'day_name');

        if ($activeDays->count() > 1) {
            $worstDay = $activeDays->sort()->keys()->first();
            if ($worstDay && $worstDay !== $bestDay) {
                $recommendations[] = "Avoid posting on {$worstDay}s — they tend to get lower engagement.";
            }
        }

        return $recommendations;
    }

    private function timeBucket(int $hour): string
    {
        if ($hour >= 6 && $hour <= 11) return 'morning';
        if ($hour >= 12 && $hour <= 16) return 'afternoon';
        if ($hour >= 17 && $hour <= 20) return 'evening';
        return 'night';
    }
}
