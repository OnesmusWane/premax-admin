<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use App\Models\SocialPostTarget;
use App\Services\Social\Contracts\SocialPlatformPublisher;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InstagramConnector implements SocialPlatformPublisher
{
    private const BASE_URL = 'https://graph.facebook.com/v25.0';

    // Extensions we treat as video
    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'webm', 'mkv'];

    // Instagram only supports these image formats
    private const SUPPORTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    // Max attempts and delay for container status polling
    private const CONTAINER_POLL_ATTEMPTS = 10;
    private const CONTAINER_POLL_DELAY_SECONDS = 3;

    // Max seconds to wait for IG video processing before giving up
    private const VIDEO_POLL_ATTEMPTS = 12;
    private const VIDEO_POLL_DELAY_SECONDS = 5;

    public function __construct(private readonly array $credentials) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Public interface
    // ──────────────────────────────────────────────────────────────────────────

    public function publish(SocialPost $post, SocialPostTarget $_target): string
    {
        Log::info('Instagram publish called', [
            'post_id'    => $post->id,
            'media'      => $post->media,
            'media_json' => json_encode($post->media),
        ]);

        $igUserId = $this->credentials['business_account_id'] ?? null;
        $token    = $this->credentials['access_token'] ?? null;

        if (! filled($igUserId) || ! filled($token)) {
            throw new RuntimeException(
                'Instagram business_account_id and access_token credentials are required to publish. ' .
                'Update the account credentials and try again.'
            );
        }

        $media = $post->media ?? [];

        if (empty($media)) {
            throw new RuntimeException(
                'Instagram requires at least one image or video URL in the media field. ' .
                'Add a media URL to the post and try again.'
            );
        }

        foreach ($media as $mediaUrl) {
            $this->assertSupportedFormat($mediaUrl);
        }

        if (count($media) === 1) {
            return $this->publishSingle($post, $igUserId, $token, $media[0]);
        }

        return $this->publishCarousel($post, $igUserId, $token, $media);
    }

   public function updatePublishedPost(SocialPost $post, SocialPostTarget $target): void
{
    $token = trim((string) ($this->credentials['access_token'] ?? ''));

    if (! filled($token)) {
        throw new RuntimeException(
            'Instagram access_token is required to update posts.'
        );
    }

    $mediaId = trim((string) ($target->external_post_id ?? ''));

    if (! filled($mediaId)) {
        throw new RuntimeException(
            'No external_post_id found on this target — post may not have been published yet.'
        );
    }

    $response = Http::post(self::BASE_URL . "/{$mediaId}", [
        'caption'          => $post->content,
        'comment_enabled'  => true,   // ← Required by Instagram API
        'access_token'     => $token,
    ]);

    if (! $response->successful()) {
        $errorMsg = $response->json('error.message') ?? $response->body();

        Log::warning('Instagram update failed', [
            'media_id'  => $mediaId,
            'target_id' => $target->id,
            'error'     => $errorMsg,
        ]);

        throw new RuntimeException(
            "[HTTP {$response->status()}] Instagram update failed: {$errorMsg}"
        );
    }

    Log::info('Instagram post updated successfully', [
        'media_id'  => $mediaId,
        'target_id' => $target->id,
    ]);
}

    public function deletePublishedPost(SocialPostTarget $target): void
        {
            $token = trim((string) ($this->credentials['access_token'] ?? ''));

            if (! filled($token)) {
                throw new RuntimeException(
                    'Instagram access_token is required to delete posts.'
                );
            }

            $mediaId = trim((string) ($target->external_post_id ?? ''));

            if (! filled($mediaId)) {
                // No external ID means it was never published — nothing to delete
                Log::info('Instagram delete skipped — no external_post_id', [
                    'target_id' => $target->id,
                ]);
                return;
            }

            $response = Http::delete(self::BASE_URL . "/{$mediaId}", [
                'access_token' => $token,
            ]);

            if (! $response->successful()) {
                $errorCode = $response->json('error.code');
                $errorMsg  = $response->json('error.message') ?? $response->body();

                // If post not found — already deleted, treat as success
                if ($errorCode === 100 || $response->status() === 404) {
                    Log::info('Instagram post already deleted or not found', [
                        'media_id'  => $mediaId,
                        'target_id' => $target->id,
                    ]);
                    return;
                }

                Log::warning('Instagram delete failed', [
                    'media_id'   => $mediaId,
                    'target_id'  => $target->id,
                    'error_code' => $errorCode,
                    'error'      => $errorMsg,
                ]);

                throw new RuntimeException(
                    "[HTTP {$response->status()}] Instagram delete failed: {$errorMsg}"
                );
            }

            Log::info('Instagram post deleted successfully', [
                'media_id'  => $mediaId,
                'target_id' => $target->id,
            ]);
        }

    public function publishCommentReply(string $platformCommentId, string $message): string
    {
        $token = trim((string) ($this->credentials['access_token'] ?? ''));

        if (! filled($token)) {
            throw new RuntimeException('Instagram access_token is required to reply to comments.');
        }

        $response = Http::post(self::BASE_URL . "/{$platformCommentId}/replies", [
            'message'      => $message,
            'access_token' => $token,
        ]);

        $this->assertSuccess($response, 'reply to comment');

        return (string) ($response->json('id') ?? '');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Publishing — single, carousel
    // ──────────────────────────────────────────────────────────────────────────

    private function publishSingle(SocialPost $post, string $igUserId, string $token, string $mediaUrl): string
    {
        $containerPayload = [
            'caption'      => $post->content,
            'access_token' => $token,
        ];

        if ($this->isVideo($mediaUrl)) {
            $containerPayload['media_type'] = 'VIDEO';
            $containerPayload['video_url']  = $mediaUrl;
        } else {
            $containerPayload['image_url'] = $mediaUrl;
        }

        Log::info('Instagram create container payload', [
            'ig_user_id' => $igUserId,
            'media_url'  => $mediaUrl,
            'is_video'   => $this->isVideo($mediaUrl),
        ]);

        // Step 1: Create media container
        $response = Http::post(self::BASE_URL . "/{$igUserId}/media", $containerPayload);
        $this->assertSuccess($response, 'create container');
        $creationId = $response->json('id');

        Log::info('Instagram container created', ['creation_id' => $creationId]);

        // Step 2: Wait for container to be ready (both images and videos)
        $this->waitForContainerReady($creationId, $token);

        // Step 3: Publish the container
        return $this->publishContainer($igUserId, $creationId, $token);
    }

    private function publishCarousel(SocialPost $post, string $igUserId, string $token, array $mediaUrls): string
    {
        // Step 1: Create individual item containers and wait for each
        $itemIds = [];

        foreach ($mediaUrls as $url) {
            $payload = [
                'is_carousel_item' => true,
                'access_token'     => $token,
            ];

            if ($this->isVideo($url)) {
                $payload['media_type'] = 'VIDEO';
                $payload['video_url']  = $url;
            } else {
                $payload['image_url'] = $url;
            }

            $response = Http::post(self::BASE_URL . "/{$igUserId}/media", $payload);
            $this->assertSuccess($response, 'create carousel item');
            $itemId = $response->json('id');

            // Wait for each item to be ready before moving on
            $this->waitForContainerReady($itemId, $token);

            $itemIds[] = $itemId;
        }

        // Step 2: Create the carousel container
        $carouselResponse = Http::post(self::BASE_URL . "/{$igUserId}/media", [
            'media_type'   => 'CAROUSEL',
            'children'     => implode(',', $itemIds),
            'caption'      => $post->content,
            'access_token' => $token,
        ]);

        $this->assertSuccess($carouselResponse, 'create carousel container');
        $carouselId = $carouselResponse->json('id');

        // Step 3: Wait for carousel container to be ready
        $this->waitForContainerReady($carouselId, $token);

        // Step 4: Publish
        return $this->publishContainer($igUserId, $carouselId, $token);
    }

    private function publishContainer(string $igUserId, string $creationId, string $token): string
    {
        $response = Http::post(self::BASE_URL . "/{$igUserId}/media_publish", [
            'creation_id'  => $creationId,
            'access_token' => $token,
        ]);

        $this->assertSuccess($response, 'publish container');

        return $response->json('id') ?? '';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Container & video polling
    // ──────────────────────────────────────────────────────────────────────────

    private function waitForContainerReady(string $creationId, string $token): void
    {
        for ($attempt = 1; $attempt <= self::CONTAINER_POLL_ATTEMPTS; $attempt++) {
            sleep(self::CONTAINER_POLL_DELAY_SECONDS);

            $response = Http::get(self::BASE_URL . "/{$creationId}", [
                'fields'       => 'status_code,status',
                'access_token' => $token,
            ]);

            if (! $response->successful()) {
                Log::warning('Instagram container status check failed', [
                    'attempt'     => $attempt,
                    'creation_id' => $creationId,
                ]);
                continue;
            }

            $statusCode = $response->json('status_code');

            Log::info('Instagram container status', [
                'attempt'     => $attempt,
                'creation_id' => $creationId,
                'status_code' => $statusCode,
            ]);

            if ($statusCode === 'FINISHED') {
                Log::info('Instagram container ready', ['creation_id' => $creationId]);
                return;
            }

            if ($statusCode === 'ERROR') {
                throw new RuntimeException(
                    'Instagram media container failed to process. ' .
                    'Check that the image URL is publicly accessible and in a supported format.'
                );
            }

            // IN_PROGRESS or other — keep waiting
        }

        // Timed out — attempt publish anyway
        Log::warning('Instagram container status check timed out — attempting publish anyway', [
            'creation_id' => $creationId,
        ]);
    }

    private function waitForVideoReady(string $creationId, string $token): void
    {
        for ($attempt = 0; $attempt < self::VIDEO_POLL_ATTEMPTS; $attempt++) {
            sleep(self::VIDEO_POLL_DELAY_SECONDS);

            $response = Http::get(self::BASE_URL . "/{$creationId}", [
                'fields'       => 'status_code',
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $statusCode = $response->json('status_code');

                if ($statusCode === 'FINISHED') {
                    return;
                }

                if ($statusCode === 'ERROR') {
                    throw new RuntimeException(
                        'Instagram video processing failed. ' .
                        'Check that the video URL is publicly accessible and in a supported format.'
                    );
                }
            }
        }

        throw new RuntimeException(
            'Instagram video processing timed out after ' .
            (self::VIDEO_POLL_ATTEMPTS * self::VIDEO_POLL_DELAY_SECONDS) . ' seconds.'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Token validation
    // ──────────────────────────────────────────────────────────────────────────

    public function validateToken(): array
    {
        $token     = trim((string) ($this->credentials['access_token'] ?? ''));
        $appId     = trim((string) ($this->credentials['app_id'] ?? ''));
        $appSecret = trim((string) ($this->credentials['app_secret'] ?? ''));

        if (! filled($token)) {
            return ['is_valid' => false, 'expires_at' => null, 'message' => 'Instagram access_token is missing.'];
        }

        if (! filled($appId) || ! filled($appSecret)) {
            return ['is_valid' => false, 'expires_at' => null, 'message' => 'app_id and app_secret are required to validate the token.'];
        }

        $response = Http::get(self::BASE_URL . '/debug_token', [
            'input_token'  => $token,
            'access_token' => "{$appId}|{$appSecret}",
        ]);

        if (! $response->successful()) {
            return [
                'is_valid'   => false,
                'expires_at' => null,
                'message'    => $response->json('error.message') ?? 'Token validation request failed.',
            ];
        }

        $data      = $response->json('data') ?? [];
        $isValid   = (bool) ($data['is_valid'] ?? false);
        $expiresAt = filled($data['expires_at'] ?? null)
            ? \Carbon\Carbon::createFromTimestamp((int) $data['expires_at'])
            : null;

        return [
            'is_valid'   => $isValid,
            'expires_at' => $expiresAt,
            'scopes'     => $data['scopes'] ?? [],
            'message'    => $isValid ? null : ($data['error']['message'] ?? 'Token is no longer valid.'),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Platform sync
    // ──────────────────────────────────────────────────────────────────────────

    public function syncPlatformPosts(int $limit = 25): array
    {
        $igUserId = trim((string) ($this->credentials['business_account_id'] ?? ''));
        $token    = trim((string) ($this->credentials['access_token'] ?? ''));

        if (! filled($igUserId) || ! filled($token)) {
            throw new RuntimeException('business_account_id and access_token are required to sync Instagram posts.');
        }

        $response = Http::get(self::BASE_URL . "/{$igUserId}/media", [
            'fields'       => 'id,caption,media_type,media_url,thumbnail_url,timestamp,permalink',
            'limit'        => $limit,
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Instagram media sync failed: ' . ($response->json('error.message') ?? $response->body())
            );
        }

        return collect($response->json('data') ?? [])->map(fn ($item) => [
            'external_post_id' => (string) $item['id'],
            'content'          => $item['caption'] ?? '',
            'media'            => array_filter([
                $item['media_url'] ?? $item['thumbnail_url'] ?? null,
            ]),
            'platform_url'     => $item['permalink'] ?? null,
            'media_type'       => strtolower($item['media_type'] ?? 'image'),
            'published_at'     => $item['timestamp'] ?? null,
        ])->all();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function isVideo(string $url): bool
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return in_array($ext, self::VIDEO_EXTENSIONS, true);
    }

    private function assertSupportedFormat(string $url): void
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        if ($this->isVideo($url)) {
            return;
        }

        if (! in_array($ext, self::SUPPORTED_IMAGE_EXTENSIONS, true)) {
            throw new RuntimeException(
                "Instagram does not support the '{$ext}' image format. " .
                'Use JPG or PNG instead.'
            );
        }
    }

    private function assertSuccess(Response $response, string $step): void
    {
        if (! $response->successful()) {
            $httpStatus = $response->status();
            $error      = $response->json('error.message') ?? $response->body();
            $errorCode  = $response->json('error.code');

            Log::warning("Instagram Graph API error at {$step}", [
                'http_status'   => $httpStatus,
                'error_code'    => $errorCode,
                'error_message' => $error,
                'step'          => $step,
            ]);

            throw new RuntimeException("[HTTP {$httpStatus}] Instagram API at {$step}: {$error}");
        }
    }
}