<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Models\SocialPostTarget;
use App\Services\Social\FacebookConnector;
use App\Services\Social\TikTokConnector;
use App\Support\SocialConnectorRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublishSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $targetId) {}

    public function handle(): void
    {
        $target = SocialPostTarget::with(['post', 'account'])->find($this->targetId);

        if (! $target) {
            return;
        }

        // Skip targets that are already confirmed published on the platform
        if ($target->status === 'published' && filled($target->external_post_id)) {
            return;
        }

        $account = $target->account;
        $post = $target->post;

        if (! $account || ! $post) {
            return;
        }

        $logContext = [
            'platform'     => $account->platform,
            'account_id'   => $account->id,
            'account_name' => $account->name,
            'post_id'      => $post->id,
            'post_title'   => $post->title ?? mb_substr($post->content, 0, 60),
            'target_id'    => $target->id,
        ];

        if (! $account->is_active || $account->auth_status !== 'connected') {
            $reason = "Account \"{$account->name}\" is not connected (status: {$account->auth_status}). Reconnect the account and republish.";

            Log::warning('Social post skipped — account not connected', $logContext + ['reason' => $reason]);

            $target->update([
                'status' => 'failed',
                'failure_reason' => $reason,
                'published_at' => null,
            ]);
            $this->syncPostStatus($post->fresh());

            return;
        }

        try {
            $connector = SocialConnectorRegistry::make($account);
            $externalId = $connector->publish($post, $target);

            $target->update([
                'status' => 'published',
                'external_post_id' => filled($externalId) ? $externalId : null,
                'published_at' => now(),
                'failure_reason' => null,
            ]);

            Log::info('Social post published successfully', $logContext + ['external_post_id' => $externalId]);
        } catch (\Throwable $e) {
            $failureReason = mb_substr($e->getMessage(), 0, 500);

            $isExpiredToken = ($account->platform === 'facebook' && FacebookConnector::isExpiredTokenException($e))
                || ($account->platform === 'tiktok' && TikTokConnector::isExpiredTokenException($e));

            if ($isExpiredToken) {
                // Attempt a silent token refresh and retry once before giving up
                $account->refresh();

                $refreshed = match ($account->platform) {
                    'facebook' => FacebookConnector::tryRefreshAccount($account),
                    'tiktok'   => TikTokConnector::tryRefreshAccount($account),
                    default    => false,
                };

                if ($refreshed) {
                    $account->refresh();

                    try {
                        $freshConnector = SocialConnectorRegistry::make($account);
                        $externalId = $freshConnector->publish($post, $target);

                        $target->update([
                            'status'           => 'published',
                            'external_post_id' => filled($externalId) ? $externalId : null,
                            'published_at'     => now(),
                            'failure_reason'   => null,
                        ]);

                        Log::info('Social post published after token auto-refresh', $logContext + ['external_post_id' => $externalId]);
                        $this->syncPostStatus($post->fresh());

                        return;
                    } catch (\Throwable $retryException) {
                        $failureReason = mb_substr($retryException->getMessage(), 0, 500);
                    }
                }

                $account->update([
                    'auth_status'            => 'expired',
                    'status'                 => 'attention',
                    'is_active'              => false,
                    'sync_status'            => 'error',
                    'sync_error'             => $failureReason,
                    'last_token_checked_at'  => now(),
                    'last_sync_completed_at' => now(),
                ]);
            }

            Log::error('Social post publish failed', $logContext + [
                'error'     => $failureReason,
                'exception' => get_class($e),
            ]);

            $target->update([
                'status' => 'failed',
                'failure_reason' => $failureReason,
                'published_at' => null,
            ]);
        }

        $this->syncPostStatus($post->fresh());
    }

    private function syncPostStatus(SocialPost $post): void
    {
        $targets = $post->targets()->get();

        // Wait until all targets have been processed before updating the post
        $stillPending = $targets->whereIn('status', ['pending', 'scheduled']);
        if ($stillPending->isNotEmpty()) {
            return;
        }

        $hasAnyPublished = $targets->contains(fn ($t) => $t->status === 'published');

        $post->update([
            'status'         => $hasAnyPublished ? 'published' : 'failed',
            'published_at'   => $hasAnyPublished ? ($post->published_at ?? now()) : null,
            'failure_reason' => $hasAnyPublished ? null : 'All publish attempts failed. Check account credentials and try again.',
        ]);

        // Delete local media files once the post is live — the platform copies are the source of truth
        if ($hasAnyPublished) {
            $this->deleteLocalMediaFiles($post);
        }
    }

    private function deleteLocalMediaFiles(SocialPost $post): void
    {
        $media = $post->media ?? [];

        if (empty($media)) {
            return;
        }

        $appUrl  = rtrim(config('app.url'), '/');
        $deleted = [];

        foreach ($media as $url) {
            // Extract the storage path from our own media URLs: /media/{path}
            $parsed = parse_url((string) $url);
            $urlBase = ($parsed['scheme'] ?? '') . '://' . ($parsed['host'] ?? '');

            if ($urlBase && ! str_starts_with($appUrl, $urlBase) && ! str_starts_with($url, $appUrl)) {
                // External URL (already on the platform or a CDN) — skip
                continue;
            }

            $urlPath = $parsed['path'] ?? '';

            // Our media route is /media/{storagePath}
            if (! str_starts_with($urlPath, '/media/')) {
                continue;
            }

            $storagePath = ltrim(substr($urlPath, strlen('/media/')), '/');

            if (filled($storagePath) && Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
                $deleted[] = $storagePath;
            }
        }

        if (! empty($deleted)) {
            // Clear the media array — URLs are now dead, platform is the source of truth
            $post->update(['media' => []]);

            Log::info('Local media files deleted after publish', [
                'post_id' => $post->id,
                'files'   => $deleted,
            ]);
        }
    }
}
