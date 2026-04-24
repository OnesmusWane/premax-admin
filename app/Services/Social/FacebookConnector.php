<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use App\Models\SocialComment;
use App\Models\SocialPostTarget;
use App\Services\Social\Contracts\SocialPlatformPublisher;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class FacebookConnector implements SocialPlatformPublisher
{
    private const BASE_URL = 'https://graph.facebook.com/v25.0';

    public function __construct(private readonly array $credentials) {}

    public function publish(SocialPost $post, SocialPostTarget $_target): string
    {
        $pageId = trim((string) ($this->credentials['page_id'] ?? ''));

        if (! filled($pageId)) {
            throw new RuntimeException(
                'Facebook page_id is missing. Add the numeric Page ID in account settings.'
            );
        }

        if (! ctype_digit($pageId) || strlen($pageId) < 5) {
            throw new RuntimeException(
                "Facebook page_id \"{$pageId}\" is not a valid Page ID. ".
                'It must be a long numeric string (e.g. "123456789012345"). '.
                'Find it under your Facebook Page → About → Page Transparency.'
            );
        }

        $token = $this->resolvePageToken($pageId);
        $media = $post->media ?? [];

        if (count($media) > 1) {
            return $this->publishMultiPhoto($post, $pageId, $token, $media);
        }

        if (count($media) === 1) {
            return $this->publishSinglePhoto($post, $pageId, $token, $media[0]);
        }

        return $this->publishText($post, $pageId, $token);
    }

    public function updatePublishedPost(SocialPost $post, SocialPostTarget $target): void
    {
        $externalPostId = trim((string) ($target->external_post_id ?? ''));

        if (! filled($externalPostId)) {
            throw new RuntimeException('Facebook external post ID is missing. The post may not have finished publishing yet.');
        }

        $pageId = trim((string) ($this->credentials['page_id'] ?? ''));
        $token = $this->resolvePageToken($pageId);

        $payload = [
            'message' => $post->content,
            'access_token' => $token,
        ];

        if (filled($post->link_url)) {
            $payload['link'] = $post->link_url;
        }

        $response = Http::post(self::BASE_URL."/{$externalPostId}", $payload);
        $this->assertSuccess($response);
    }

    public function deletePublishedPost(SocialPostTarget $target): void
    {
        $externalPostId = trim((string) ($target->external_post_id ?? ''));

        if (! filled($externalPostId)) {
            throw new RuntimeException('Facebook external post ID is missing. The post may not have finished publishing yet.');
        }

        $pageId = trim((string) ($this->credentials['page_id'] ?? ''));
        $token = $this->resolvePageToken($pageId);

        $response = Http::delete(self::BASE_URL."/{$externalPostId}", [
            'access_token' => $token,
        ]);
        $this->assertSuccess($response);
    }

    public function syncPostInteractions(SocialPostTarget $target): array
    {
        $externalPostId = trim((string) ($target->external_post_id ?? ''));

        if (! filled($externalPostId)) {
            throw new RuntimeException('Facebook external post ID is missing. Publish the post first so interactions can be synced.');
        }

        $pageId = trim((string) ($this->credentials['page_id'] ?? ''));
        $token = $this->resolvePageToken($pageId);

        $postResponse = Http::get(self::BASE_URL."/{$externalPostId}", [
            'fields' => 'reactions.summary(true).limit(0),comments.summary(true).limit(0),shares',
            'access_token' => $token,
        ]);
        $this->assertSuccess($postResponse);

        $commentsResponse = Http::get(self::BASE_URL."/{$externalPostId}/comments", [
            'fields' => 'id,from{id,name},message,created_time',
            'filter' => 'stream',
            'limit' => 100,
            'access_token' => $token,
        ]);
        $this->assertSuccess($commentsResponse);

        $likesCount = (int) ($postResponse->json('reactions.summary.total_count') ?? 0);
        $commentsCount = (int) ($postResponse->json('comments.summary.total_count') ?? 0);
        $sharesCount = (int) ($postResponse->json('shares.count') ?? 0);
        $commentRows = collect($commentsResponse->json('data') ?? []);

        DB::transaction(function () use ($target, $likesCount, $commentsCount, $sharesCount, $commentRows) {
            $target->update([
                'likes_count' => $likesCount,
                'comments_count' => $commentsCount,
                'shares_count' => $sharesCount,
            ]);

            foreach ($commentRows as $comment) {
                $commentId = (string) ($comment['id'] ?? '');
                if (! filled($commentId)) {
                    continue;
                }

                SocialComment::updateOrCreate(
                    [
                        'social_account_id' => $target->social_account_id,
                        'platform_comment_id' => $commentId,
                    ],
                    [
                        'social_post_id' => $target->social_post_id,
                        'author_name' => $comment['from']['name'] ?? 'Facebook User',
                        'author_handle' => data_get($comment, 'from.id'),
                        'comment_text' => $comment['message'] ?? '',
                        'status' => 'needs_reply',
                        'received_at' => filled($comment['created_time'] ?? null)
                            ? Carbon::parse($comment['created_time'])
                            : now(),
                        'metadata' => [
                            'facebook_author_id' => data_get($comment, 'from.id'),
                            'facebook_comment_payload' => $comment,
                        ],
                    ],
                );
            }
        });

        return [
            'likes_count' => $likesCount,
            'comments_count' => $commentsCount,
            'shares_count' => $sharesCount,
            'synced_comments' => $commentRows->count(),
        ];
    }

    public function inspectToken(): array
    {
        $userToken = trim((string) ($this->credentials['user_access_token'] ?? ''));
        $pageToken = trim((string) ($this->credentials['page_access_token'] ?? ''));
        $tokenToInspect = filled($userToken) ? $userToken : $pageToken;
        $appId = trim((string) ($this->credentials['app_id'] ?? ''));
        $appSecret = trim((string) ($this->credentials['app_secret'] ?? ''));

        if (! filled($tokenToInspect)) {
            return [
                'checked' => false,
                'is_valid' => false,
                'expires_at' => null,
                'message' => 'Facebook access token is missing. Reconnect the account to continue.',
            ];
        }

        if (! filled($appId) || ! filled($appSecret)) {
            return [
                'checked' => false,
                'is_valid' => false,
                'expires_at' => null,
                'message' => 'Facebook app_id or app_secret is missing. Update the Facebook account credentials.',
            ];
        }

        $response = Http::get(self::BASE_URL.'/debug_token', [
            'input_token' => $tokenToInspect,
            'access_token' => "{$appId}|{$appSecret}",
        ]);
        $this->assertSuccess($response);

        $data = $response->json('data') ?? [];
        $expiresAt = filled($data['expires_at'] ?? null)
            ? Carbon::createFromTimestamp((int) $data['expires_at'])
            : null;

        return [
            'checked' => true,
            'is_valid' => (bool) ($data['is_valid'] ?? false),
            'expires_at' => $expiresAt,
            'scopes' => $data['scopes'] ?? [],
            'message' => $data['is_valid'] ?? false
                ? null
                : ($data['error']['message'] ?? 'Facebook marked this token as invalid. Reconnect the account.'),
        ];
    }

    public static function isExpiredTokenException(Throwable $throwable): bool
    {
        $message = strtolower($throwable->getMessage());

        return str_contains($message, 'session has expired')
            || str_contains($message, 'error validating access token')
            || str_contains($message, 'access token has expired')
            || str_contains($message, 'oauthexception')
            || str_contains($message, 'code: 190');
    }

    /**
     * Exchange any user token (short-lived or long-lived) for a long-lived token (~60 days).
     * Returns the full response array including 'access_token' and 'expires_in'.
     * Requires app_id and app_secret stored in credentials.
     */
    public static function exchangeLongLivedToken(string $appId, string $appSecret, string $token): array
    {
        $response = Http::get(self::BASE_URL.'/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'fb_exchange_token' => $token,
        ]);

        if (! $response->successful() || ! filled($response->json('access_token'))) {
            $error = $response->json('error.message') ?? $response->body();
            throw new RuntimeException("[HTTP {$response->status()}] Facebook token exchange failed: {$error}");
        }

        return $response->json();
    }

    /**
     * Attempt to extend the stored user token for a connected Facebook account.
     * On success, writes the new long-lived user token and (if page_id is present)
     * a fresh non-expiring page access token back to the account's credentials.
     * Returns true if the refresh succeeded.
     */
    public static function tryRefreshAccount(\App\Models\SocialAccount $account): bool
    {
        $creds     = $account->credentials ?? [];
        $appId     = trim((string) ($creds['app_id'] ?? ''));
        $appSecret = trim((string) ($creds['app_secret'] ?? ''));
        $userToken = trim((string) ($creds['user_access_token'] ?? ''));

        if (! filled($appId) || ! filled($appSecret) || ! filled($userToken)) {
            Log::info('Facebook token refresh skipped — missing app_id, app_secret, or user_access_token', [
                'account_id' => $account->id,
            ]);

            return false;
        }

        try {
            $result    = self::exchangeLongLivedToken($appId, $appSecret, $userToken);
            $newToken  = $result['access_token'];
            $expiresIn = (int) ($result['expires_in'] ?? 0);
            $expiresAt = $expiresIn > 0 ? now()->addSeconds($expiresIn) : null;

            // Fetch a fresh non-expiring page access token while we have a valid user token
            $pageId    = trim((string) ($creds['page_id'] ?? ''));
            $pageToken = $creds['page_access_token'] ?? null;

            if (filled($pageId)) {
                $pageResponse = Http::get(self::BASE_URL."/{$pageId}", [
                    'fields'       => 'access_token',
                    'access_token' => $newToken,
                ]);

                if ($pageResponse->successful() && filled($pageResponse->json('access_token'))) {
                    $pageToken = $pageResponse->json('access_token');
                }
            }

            $account->update([
                'credentials' => array_merge($creds, array_filter([
                    'user_access_token' => $newToken,
                    'page_access_token' => $pageToken,
                ], fn ($v) => $v !== null)),
                'token_expires_at'      => $expiresAt,
                'last_token_checked_at' => now(),
                'auth_status'           => 'connected',
                'status'                => 'active',
                'is_active'             => true,
                'sync_error'            => null,
            ]);

            Log::info('Facebook long-lived token refreshed', [
                'account_id' => $account->id,
                'expires_at' => $expiresAt?->toIso8601String(),
                'page_token_refreshed' => filled($pageToken),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('Facebook token refresh failed', [
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function publishCommentReply(string $platformCommentId, string $message): string
    {
        $pageId = trim((string) ($this->credentials['page_id'] ?? ''));
        $token  = $this->resolvePageToken($pageId);

        $response = Http::post(self::BASE_URL."/{$platformCommentId}/comments", [
            'message'      => $message,
            'access_token' => $token,
        ]);
        $this->assertSuccess($response);

        return (string) ($response->json('id') ?? '');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Token resolution
    // v25.0 no longer requires pages_manage_posts. You now obtain a Page token
    // by exchanging a User token (with pages_read_engagement +
    // pages_manage_metadata) via GET /{page-id}?fields=access_token.
    // ──────────────────────────────────────────────────────────────────────────

    private function resolvePageToken(string $pageId): string
    {
        // Option A: caller already stored a ready-to-use Page Access Token
        $direct = $this->credentials['page_access_token'] ?? null;
        if (filled($direct)) {
            return $direct;
        }

        // Option B: exchange a User Access Token for a Page token on the fly
        $userToken = $this->credentials['user_access_token'] ?? null;
        if (! filled($userToken)) {
            throw new RuntimeException(
                'Facebook credentials are incomplete. Provide either a page_access_token or a '.
                'user_access_token (with pages_read_engagement + pages_manage_metadata scopes) '.
                'so the connector can obtain a Page token automatically.'
            );
        }

        return $this->exchangeForPageToken($pageId, $userToken);
    }

    private function exchangeForPageToken(string $pageId, string $userToken): string
    {
        $response = Http::get(self::BASE_URL."/{$pageId}", [
            'fields'       => 'access_token',
            'access_token' => $userToken,
        ]);

        $pageToken = $response->json('access_token');

        if (! $response->successful() || ! filled($pageToken)) {
            $httpStatus = $response->status();
            $error      = $response->json('error.message') ?? $response->body();

            Log::warning('Facebook: failed to exchange user token for page token', [
                'page_id'     => $pageId,
                'http_status' => $httpStatus,
                'error'       => $error,
            ]);

            throw new RuntimeException(
                "[HTTP {$httpStatus}] Could not obtain a Page Access Token for page {$pageId}: {$error}. ".
                'Ensure the user token has pages_read_engagement and pages_manage_metadata scopes.'
            );
        }

        Log::debug('Facebook: user token exchanged for page token', ['page_id' => $pageId]);

        return $pageToken;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Publishing methods
    // ──────────────────────────────────────────────────────────────────────────

    private function publishText(SocialPost $post, string $pageId, string $token): string
    {
        $payload = [
            'message'      => $post->content,
            'access_token' => $token,
        ];

        if (filled($post->link_url)) {
            $payload['link'] = $post->link_url;
        }

        $response = Http::post(self::BASE_URL."/{$pageId}/feed", $payload);
        $this->assertSuccess($response);

        return $response->json('id') ?? '';
    }

    private function publishSinglePhoto(SocialPost $post, string $pageId, string $token, string $photoUrl): string
    {
        $response = Http::post(self::BASE_URL."/{$pageId}/photos", [
            'url'          => $photoUrl,
            'caption'      => $post->content,
            'access_token' => $token,
        ]);
        $this->assertSuccess($response);

        return $response->json('post_id') ?? $response->json('id') ?? '';
    }

    private function publishMultiPhoto(SocialPost $post, string $pageId, string $token, array $photoUrls): string
    {
        // Step 1: Upload each photo as unpublished to get their staging IDs
        $photoIds = [];
        foreach ($photoUrls as $url) {
            $response = Http::post(self::BASE_URL."/{$pageId}/photos", [
                'url'          => $url,
                'published'    => false,
                'access_token' => $token,
            ]);
            $this->assertSuccess($response);
            $photoIds[] = $response->json('id');
        }

        // Step 2: Publish a single feed post referencing all staged photos
        $payload = [
            'message'      => $post->content,
            'access_token' => $token,
        ];

        foreach ($photoIds as $index => $photoId) {
            $payload["attached_media[{$index}]"] = json_encode(['media_fbid' => $photoId]);
        }

        if (filled($post->link_url)) {
            $payload['link'] = $post->link_url;
        }

        $response = Http::post(self::BASE_URL."/{$pageId}/feed", $payload);
        $this->assertSuccess($response);

        return $response->json('id') ?? '';
    }

    private function assertSuccess(Response $response): void
    {
        if (! $response->successful()) {
            $httpStatus = $response->status();
            $error      = $response->json('error.message') ?? $response->body();
            $errorCode  = $response->json('error.code');
            $errorType  = $response->json('error.type');

            Log::warning('Facebook Graph API error', [
                'http_status'   => $httpStatus,
                'error_code'    => $errorCode,
                'error_type'    => $errorType,
                'error_message' => $error,
            ]);

            throw new RuntimeException("[HTTP {$httpStatus}] Facebook API: {$error}");
        }
    }
}
