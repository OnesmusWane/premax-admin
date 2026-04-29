<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\Social\FacebookConnector;
use App\Services\Social\TikTokConnector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshExpiringSocialTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Refresh Facebook accounts whose token will expire within 7 days
        SocialAccount::query()
            ->where('platform', 'facebook')
            ->where('auth_status', 'connected')
            ->where('is_active', true)
            ->whereBetween('token_expires_at', [now(), now()->addDays(7)])
            ->each(function (SocialAccount $account) {
                $refreshed = FacebookConnector::tryRefreshAccount($account);

                Log::info('Facebook proactive token refresh', [
                    'account_id'       => $account->id,
                    'account_name'     => $account->name,
                    'refreshed'        => $refreshed,
                    'token_expires_at' => $account->token_expires_at?->toIso8601String(),
                ]);
            });

        // Refresh TikTok accounts whose token expires within 8 hours.
        // TikTok access tokens last only 24 hours, so we refresh proactively on every 6-hour run.
        SocialAccount::query()
            ->where('platform', 'tiktok')
            ->where('auth_status', 'connected')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '<=', now()->addHours(8));
            })
            ->each(function (SocialAccount $account) {
                $refreshed = TikTokConnector::tryRefreshAccount($account);

                Log::info('TikTok proactive token refresh', [
                    'account_id'       => $account->id,
                    'account_name'     => $account->name,
                    'refreshed'        => $refreshed,
                    'token_expires_at' => $account->token_expires_at?->toIso8601String(),
                ]);
            });
    }
}
