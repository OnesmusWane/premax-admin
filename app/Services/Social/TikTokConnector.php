<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use App\Models\SocialPostTarget;
use App\Services\Social\Contracts\SocialPlatformPublisher;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TikTokConnector implements SocialPlatformPublisher
{
    private const API_BASE  = 'https://open.tiktokapis.com/v2';
    private const AUTH_BASE = 'https://www.tiktok.com/v2/auth/authorize';

    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'webm', 'mkv'];

    // TikTok title max length
    private const TITLE_MAX_LENGTH = 2200;

    // Poll every 3s, max 20 attempts = 60s before giving up
    private const POLL_ATTEMPTS      = 20;
    private const POLL_DELAY_SECONDS = 3;

    public function __construct(private readonly array $credentials) {}

    // ──────────────────────────────────────────────────────────────────────────
    // SocialPlatformPublisher interface
    // ──────────────────────────────────────────────────────────────────────────

    public function publish(SocialPost $post, SocialPostTarget $_target): string
    {
        $token = trim((string) ($this->credentials['access_token'] ?? ''));

        if (! filled($token)) {
            throw new RuntimeException(
                'TikTok access_token is required to publish. '.
                'Authorize the account via OAuth to obtain a token.'
            );
        }

        $media    = $post->media ?? [];
        $videoUrl = collect($media)->first(fn ($url) => $this->isVideo($url));

        if (empty($media)) {
            throw new RuntimeException(
                'TikTok requires a video file. Add a publicly-accessible MP4 or MOV URL to the post.'
            );
        }

        if (! filled($videoUrl)) {
            throw new RuntimeException(
                'TikTok only supports video posts. The attached media does not contain a recognized video file. '.
                'Supported formats: MP4, MOV, AVI, WMV, WEBM, MKV.'
            );
        }

        Log::info('TikTok publish: starting video upload', [
            'post_id'   => $post->id,
            'video_url' => $videoUrl,
        ]);

        return $this->publishVideo($post, $token, $videoUrl);
    }

    public function updatePublishedPost(SocialPost $post, SocialPostTarget $target): void
    {
        throw new RuntimeException(
            'TikTok does not allow editing published posts via the API. Create a new post instead.'
        );
    }

    public function deletePublishedPost(SocialPostTarget $target): void
    {
        throw new RuntimeException(
            'TikTok does not support deleting published posts via the Content Posting API.'
        );
    }

    public function publishCommentReply(string $platformCommentId, string $message): string
    {
        throw new RuntimeException(
            'TikTok comment replies are not supported by the Content Posting API.'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // OAuth
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build the TikTok OAuth authorization URL.
     * Redirect the user's browser to this URL to start the OAuth flow.
     */
    public function getOAuthUrl(string $redirectUri, string $state): string
    {
        return self::AUTH_BASE.'?'.http_build_query([
            'client_key'    => $this->credentials['client_key'],
            // video.publish (direct post) requires TikTok app review approval before it can be
            // requested. Use video.upload (inbox/draft) until the app is approved for production.
            // Once approved, change this to: 'user.info.basic,video.publish,video.upload'
            'scope'         => 'user.info.basic,video.upload',
            'response_type' => 'code',
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
        ]);
    }

    /**
     * Exchange an authorization code for access + refresh tokens.
     * Returns the full token response array including access_token, refresh_token,
     * open_id, expires_in, and refresh_expires_in.
     */
    public function exchangeCodeForTokens(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->post(self::API_BASE.'/oauth/token/', [
            'client_key'    => $this->credentials['client_key'],
            'client_secret' => $this->credentials['client_secret'],
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'TikTok code exchange failed: '.($response->json('error_description') ?? $response->body())
            );
        }

        $data = $response->json();

        if (filled($data['error'] ?? null) && ($data['error'] !== 'ok')) {
            throw new RuntimeException(
                'TikTok code exchange error: '.($data['error_description'] ?? $data['error'])
            );
        }

        Log::info('TikTok OAuth code exchanged for tokens', [
            'open_id'            => $data['open_id'] ?? null,
            'expires_in'         => $data['expires_in'] ?? null,
            'refresh_expires_in' => $data['refresh_expires_in'] ?? null,
        ]);

        return $data;
    }

    /**
     * Use the stored refresh_token to get a new access_token.
     * TikTok access tokens expire in ~24 hours; refresh tokens last ~365 days.
     * Returns the full token response including the new access_token and refresh_token.
     */
    public function refreshAccessToken(): array
    {
        $clientKey    = trim((string) ($this->credentials['client_key'] ?? ''));
        $clientSecret = trim((string) ($this->credentials['client_secret'] ?? ''));
        $refreshToken = trim((string) ($this->credentials['refresh_token'] ?? ''));

        if (! filled($clientKey) || ! filled($clientSecret)) {
            throw new RuntimeException('TikTok client_key and client_secret are required to refresh the token.');
        }

        if (! filled($refreshToken)) {
            throw new RuntimeException('TikTok refresh_token is missing. Re-authorize the account via OAuth.');
        }

        $response = Http::asForm()->post(self::API_BASE.'/oauth/token/', [
            'client_key'    => $clientKey,
            'client_secret' => $clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'TikTok token refresh failed: '.($response->json('error_description') ?? $response->body())
            );
        }

        $data = $response->json();

        if (filled($data['error'] ?? null) && ($data['error'] !== 'ok')) {
            throw new RuntimeException(
                'TikTok token refresh error: '.($data['error_description'] ?? $data['error'])
            );
        }

        return $data;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Token management
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return true if the exception signals an expired or invalid TikTok token.
     * Error codes: 10010 = access_token expired, 10012 = access_token invalid.
     */
    public static function isExpiredTokenException(Throwable $throwable): bool
    {
        $message = strtolower($throwable->getMessage());

        return str_contains($message, '10010')
            || str_contains($message, '10012')
            || str_contains($message, 'access_token expired')
            || str_contains($message, 'access token expired');
    }

    /**
     * Attempt to refresh the stored access token for a TikTok account.
     * Writes the new access_token and refresh_token back to account credentials.
     * Returns true on success.
     */
    public static function tryRefreshAccount(\App\Models\SocialAccount $account): bool
    {
        $creds        = $account->credentials ?? [];
        $clientKey    = trim((string) ($creds['client_key'] ?? ''));
        $clientSecret = trim((string) ($creds['client_secret'] ?? ''));
        $refreshToken = trim((string) ($creds['refresh_token'] ?? ''));

        if (! filled($clientKey) || ! filled($clientSecret) || ! filled($refreshToken)) {
            Log::info('TikTok token refresh skipped — missing client_key, client_secret, or refresh_token', [
                'account_id' => $account->id,
            ]);

            return false;
        }

        try {
            $data            = (new self($creds))->refreshAccessToken();
            $newToken        = $data['access_token'] ?? null;
            $newRefreshToken = $data['refresh_token'] ?? $refreshToken;
            $expiresIn       = (int) ($data['expires_in'] ?? 86400);
            $expiresAt       = now()->addSeconds($expiresIn);

            if (! filled($newToken)) {
                throw new RuntimeException('TikTok refresh response did not contain an access_token.');
            }

            $account->update([
                'credentials' => array_merge($creds, [
                    'access_token'  => $newToken,
                    'refresh_token' => $newRefreshToken,
                ]),
                'token_expires_at'      => $expiresAt,
                'last_token_checked_at' => now(),
                'auth_status'           => 'connected',
                'status'                => 'active',
                'is_active'             => true,
                'sync_error'            => null,
            ]);

            Log::info('TikTok access token auto-refreshed', [
                'account_id' => $account->id,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('TikTok token refresh failed', [
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
            ]);

            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // User info & platform sync
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Fetch TikTok account info for the authenticated user.
     */
    public function getUserInfo(): array
    {
        $token = trim((string) ($this->credentials['access_token'] ?? ''));

        if (! filled($token)) {
            throw new RuntimeException('TikTok access_token is required to fetch user info.');
        }

        $response = Http::withToken($token)
            ->get(self::API_BASE.'/user/info/', [
                'fields' => 'open_id,union_id,avatar_url,display_name,follower_count',
            ]);

        $this->assertSuccess($response);

        return $response->json('data.user') ?? [];
    }

    /**
     * Fetch recent TikTok videos for platform post sync.
     */
    public function syncPlatformPosts(int $limit = 20): array
    {
        $token = trim((string) ($this->credentials['access_token'] ?? ''));

        if (! filled($token)) {
            throw new RuntimeException('TikTok access_token is required to sync posts.');
        }

        $response = Http::withToken($token)
            ->contentType('application/json; charset=UTF-8')
            ->post(self::API_BASE.'/video/list/', [
                'max_count' => min($limit, 20),
                'fields'    => ['id', 'title', 'video_description', 'create_time', 'cover_image_url', 'share_url', 'like_count', 'comment_count', 'share_count'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'TikTok video list failed: '.($response->json('error.message') ?? $response->body())
            );
        }

        return collect($response->json('data.videos') ?? [])
            ->filter(fn ($item) => filled($item['id'] ?? ''))
            ->map(fn ($item) => [
                'external_post_id' => (string) $item['id'],
                'content'          => $item['video_description'] ?? $item['title'] ?? '',
                'media'            => array_filter([$item['cover_image_url'] ?? null]),
                'platform_url'     => $item['share_url'] ?? null,
                'published_at'     => filled($item['create_time'] ?? null)
                    ? \Carbon\Carbon::createFromTimestamp((int) $item['create_time'])
                    : null,
            ])
            ->values()
            ->all();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Publishing internals
    // ──────────────────────────────────────────────────────────────────────────

    private function publishVideo(SocialPost $post, string $token, string $videoUrl): string
    {
        $payload = [
            'post_info' => [
                'title'                    => mb_substr($post->content, 0, self::TITLE_MAX_LENGTH),
                'privacy_level'            => 'PUBLIC_TO_EVERYONE',
                'disable_duet'             => false,
                'disable_comment'          => false,
                'disable_stitch'           => false,
                'video_cover_timestamp_ms' => 1000,
            ],
            'source_info' => [
                'source'    => 'PULL_FROM_URL',
                'video_url' => $videoUrl,
            ],
        ];

        // Try direct post first (video.publish scope — requires TikTok app review approval).
        // If the app is sandboxed or pending review, fall back to inbox upload
        // (video.upload scope) which sends the video to the creator's TikTok inbox as a draft.
        $endpoints = [
            self::API_BASE.'/post/publish/video/init/',
            self::API_BASE.'/post/publish/inbox/video/init/',
        ];

        foreach ($endpoints as $endpoint) {
            $response = Http::withToken($token)
                ->contentType('application/json; charset=UTF-8')
                ->post($endpoint, $payload);

            // scope_not_authorized means this endpoint needs a scope the token lacks —
            // try the next endpoint instead of throwing immediately.
            if ($response->json('error.code') === 'scope_not_authorized') {
                Log::warning('TikTok scope_not_authorized — trying fallback endpoint', [
                    'endpoint' => $endpoint,
                    'post_id'  => $post->id,
                ]);
                continue;
            }

            $this->assertSuccess($response);

            $publishId = $response->json('data.publish_id') ?? '';

            if (! filled($publishId)) {
                throw new RuntimeException('TikTok did not return a publish_id after initializing the upload.');
            }

            $isInbox = str_contains($endpoint, '/inbox/');

            Log::info('TikTok video upload initialized', [
                'post_id'    => $post->id,
                'publish_id' => $publishId,
                'flow'       => $isInbox ? 'inbox_draft' : 'direct_post',
            ]);

            return $this->pollUntilPublished($token, $publishId, $post->id);
        }

        throw new RuntimeException(
            'TikTok video publish failed: neither video.publish (direct post) nor video.upload (inbox) '.
            'scope is authorized. Enable one of these scopes in TikTok Developer Portal and re-authorize the account.'
        );
    }

    private function pollUntilPublished(string $token, string $publishId, int $postId): string
    {
        for ($attempt = 1; $attempt <= self::POLL_ATTEMPTS; $attempt++) {
            sleep(self::POLL_DELAY_SECONDS);

            $response = Http::withToken($token)
                ->contentType('application/json; charset=UTF-8')
                ->post(self::API_BASE.'/post/publish/status/fetch/', [
                    'publish_id' => $publishId,
                ]);

            if (! $response->successful()) {
                Log::warning('TikTok status poll HTTP error', [
                    'attempt'    => $attempt,
                    'publish_id' => $publishId,
                    'http_status' => $response->status(),
                ]);
                continue;
            }

            $status = $response->json('data.status') ?? '';
            $itemId = $response->json('data.publicaly_available_post_id')
                ?? $response->json('data.item_id')
                ?? '';

            Log::info('TikTok publish status poll', [
                'attempt'    => $attempt,
                'publish_id' => $publishId,
                'status'     => $status,
                'post_id'    => $postId,
            ]);

            if ($status === 'PUBLISH_COMPLETE') {
                $externalId = filled($itemId) ? (string) $itemId : $publishId;
                Log::info('TikTok video published', ['post_id' => $postId, 'external_id' => $externalId]);

                return $externalId;
            }

            if ($status === 'FAILED') {
                $failReason = $response->json('data.fail_reason') ?? 'unknown';
                throw new RuntimeException("TikTok video publishing failed (reason: {$failReason}).");
            }

            // PROCESSING_UPLOAD or other transitional state — keep waiting
        }

        // Timed out — store publish_id as the external ID so the admin can check manually
        Log::warning('TikTok publish status poll timed out — storing publish_id', [
            'publish_id' => $publishId,
            'post_id'    => $postId,
        ]);

        return $publishId;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function isVideo(string $url): bool
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return in_array($ext, self::VIDEO_EXTENSIONS, true);
    }

    private function assertSuccess(Response $response): void
    {
        $errorCode = $response->json('error.code');

        // TikTok returns {"error": {"code": "ok"}} for success
        if (! $response->successful() || ($errorCode !== null && $errorCode !== 'ok')) {
            $httpStatus = $response->status();
            $error      = $response->json('error.message') ?? $response->body();
            $logId      = $response->json('error.log_id');

            Log::warning('TikTok API error', [
                'http_status'   => $httpStatus,
                'error_code'    => $errorCode,
                'error_message' => $error,
                'log_id'        => $logId,
            ]);

            throw new RuntimeException("[HTTP {$httpStatus}] TikTok API (code: {$errorCode}): {$error}");
        }
    }
}
