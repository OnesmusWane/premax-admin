<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Services\Social\FacebookConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSocialTokenHealth extends Command
{
    protected $signature = 'social:check-token-health';

    protected $description = 'Check Facebook and Instagram token health; refresh tokens expiring within 7 days';

    public function handle(): int
    {
        $accounts = SocialAccount::query()
            ->whereIn('platform', ['facebook', 'instagram'])
            ->where('auth_status', 'connected')
            ->get();

        $this->info("Checking token health for {$accounts->count()} account(s)...");

        foreach ($accounts as $account) {
            $this->checkAccount($account);
        }

        $this->info('Token health check complete.');

        return self::SUCCESS;
    }

    private function checkAccount(SocialAccount $account): void
    {
        try {
            // Instagram tokens are synced from Facebook — only inspect FB accounts directly
            if ($account->platform === 'instagram') {
                $hasToken = filled($account->credentials['access_token'] ?? null);
                if (! $hasToken) {
                    Log::warning('Token health: Instagram access_token missing', ['account_id' => $account->id]);
                }
                return;
            }

            $creds = $account->credentials ?? [];

            if (! filled($creds['app_id'] ?? '') || ! filled($creds['app_secret'] ?? '')) {
                return;
            }

            $hasToken = filled($creds['system_user_token'] ?? '')
                || filled($creds['user_access_token'] ?? '')
                || filled($creds['page_access_token'] ?? '');

            if (! $hasToken) {
                return;
            }

            $connector  = new FacebookConnector($creds);
            $inspection = $connector->inspectToken();

            $account->update(['last_token_checked_at' => now()]);

            if (! ($inspection['is_valid'] ?? false)) {
                Log::warning('Token health: Facebook token invalid — marking expired', [
                    'account_id' => $account->id,
                    'message'    => $inspection['message'] ?? 'unknown',
                ]);

                $account->update([
                    'auth_status' => 'expired',
                    'status'      => 'attention',
                    'is_active'   => false,
                    'sync_error'  => mb_substr($inspection['message'] ?? 'Token is no longer valid.', 0, 500),
                ]);

                return;
            }

            $neverExpires = $inspection['never_expires'] ?? false;
            $tokenType    = strtolower($inspection['type'] ?? 'unknown');
            $expiresAt    = $inspection['expires_at'] ?? null;

            if ($neverExpires) {
                Log::info('Token health: SYSTEM_USER token — healthy, never expires', [
                    'account_id' => $account->id,
                ]);

                $account->update([
                    'token_expires_at' => null,
                    'metadata'         => array_merge($account->metadata ?? [], ['token_type' => $tokenType]),
                ]);

                return;
            }

            if ($expiresAt === null) {
                Log::info('Token health: Facebook token valid, no expiry recorded', ['account_id' => $account->id]);
                return;
            }

            $daysLeft = (int) now()->diffInDays($expiresAt, false);

            if ($daysLeft < 0) {
                Log::warning('Token health: Facebook token expired', [
                    'account_id' => $account->id,
                    'expired_at' => $expiresAt->toIso8601String(),
                ]);

                $account->update([
                    'auth_status'      => 'expired',
                    'status'           => 'attention',
                    'is_active'        => false,
                    'token_expires_at' => $expiresAt,
                    'sync_error'       => 'Facebook token expired on '.$expiresAt->toDateString().'. Reconnect the account.',
                ]);

                return;
            }

            if ($daysLeft <= 7) {
                Log::info("Token health: Facebook token expiring in {$daysLeft} day(s) — attempting refresh", [
                    'account_id' => $account->id,
                    'expires_at' => $expiresAt->toIso8601String(),
                ]);

                $refreshed = FacebookConnector::tryRefreshAccount($account);

                Log::info('Token health: refresh '.($refreshed ? 'succeeded' : 'failed'), [
                    'account_id' => $account->id,
                ]);

                return;
            }

            Log::info("Token health: Facebook token healthy — expires in {$daysLeft} day(s)", [
                'account_id' => $account->id,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

        } catch (\Throwable $e) {
            Log::error('Token health check threw an exception', [
                'account_id' => $account->id,
                'platform'   => $account->platform,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
