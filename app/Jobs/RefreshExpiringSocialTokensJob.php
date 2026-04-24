<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\Social\FacebookConnector;
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
                    'account_id'     => $account->id,
                    'account_name'   => $account->name,
                    'refreshed'      => $refreshed,
                    'token_expires_at' => $account->token_expires_at?->toIso8601String(),
                ]);
            });
    }
}
