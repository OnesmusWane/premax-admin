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

    // Max seconds to wait for IG video processing before giving up
    private const VIDEO_POLL_ATTEMPTS = 12;
    private const VIDEO_POLL_DELAY_SECONDS = 5;

    public function __construct(private readonly array $credentials) {}

    public function publish(SocialPost $post, SocialPostTarget $_target): string
    {
        $igUserId = $this->credentials['business_account_id'] ?? null;
        $token = $this->credentials['access_token'] ?? null;

        if (! filled($igUserId) || ! filled($token)) {
            throw new RuntimeException(
                'Instagram business_account_id and access_token credentials are required to publish. '.
                'Update the account credentials and try again.'
            );
        }

        $media = $post->media ?? [];

        if (empty($media)) {
            throw new RuntimeException(
                'Instagram requires at least one image or video URL in the media field. '.
                'Add a media URL to the post and try again.'
            );
        }

        if (count($media) === 1) {
            return $this->publishSingle($post, $igUserId, $token, $media[0]);
        }

        return $this->publishCarousel($post, $igUserId, $token, $media);
    }

    public function updatePublishedPost(SocialPost $post, SocialPostTarget $target): void
    {
        throw new RuntimeException('Instagram published post editing is not supported by this connector workflow. Create a new post instead.');
    }

    public function deletePublishedPost(SocialPostTarget $target): void
    {
        throw new RuntimeException('Instagram published post deletion is not supported by this connector workflow.');
    }

    public function publishCommentReply(string $platformCommentId, string $message): string
    {
        $token = trim((string) ($this->credentials['access_token'] ?? ''));

        if (! filled($token)) {
            throw new RuntimeException('Instagram access_token is required to reply to comments.');
        }

        $response = Http::post(self::BASE_URL."/{$platformCommentId}/replies", [
            'message'      => $message,
            'access_token' => $token,
        ]);
        $this->assertSuccess($response, 'reply to comment');

        return (string) ($response->json('id') ?? '');
    }

    private function publishSingle(SocialPost $post, string $igUserId, string $token, string $mediaUrl): string
    {
        $containerPayload = [
            'caption' => $post->content,
            'access_token' => $token,
        ];

        if ($this->isVideo($mediaUrl)) {
            $containerPayload['media_type'] = 'VIDEO';
            $containerPayload['video_url'] = $mediaUrl;
        } else {
            $containerPayload['image_url'] = $mediaUrl;
        }

        // Step 1: Create media container
        $response = Http::post(self::BASE_URL."/{$igUserId}/media", $containerPayload);
        $this->assertSuccess($response, 'create container');
        $creationId = $response->json('id');

        // Step 2: Wait for video processing if needed
        if ($this->isVideo($mediaUrl)) {
            $this->waitForVideoReady($creationId, $token);
        }

        // Step 3: Publish the container
        return $this->publishContainer($igUserId, $creationId, $token);
    }

    private function publishCarousel(SocialPost $post, string $igUserId, string $token, array $mediaUrls): string
    {
        // Step 1: Create individual item containers
        $itemIds = [];
        foreach ($mediaUrls as $url) {
            $payload = [
                'is_carousel_item' => true,
                'access_token' => $token,
            ];

            if ($this->isVideo($url)) {
                $payload['media_type'] = 'VIDEO';
                $payload['video_url'] = $url;
            } else {
                $payload['image_url'] = $url;
            }

            $response = Http::post(self::BASE_URL."/{$igUserId}/media", $payload);
            $this->assertSuccess($response, 'create carousel item');
            $itemIds[] = $response->json('id');
        }

        // Step 2: Create the carousel container
        $carouselResponse = Http::post(self::BASE_URL."/{$igUserId}/media", [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $itemIds),
            'caption' => $post->content,
            'access_token' => $token,
        ]);
        $this->assertSuccess($carouselResponse, 'create carousel container');
        $carouselId = $carouselResponse->json('id');

        // Step 3: Publish
        return $this->publishContainer($igUserId, $carouselId, $token);
    }

    private function publishContainer(string $igUserId, string $creationId, string $token): string
    {
        $response = Http::post(self::BASE_URL."/{$igUserId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $token,
        ]);
        $this->assertSuccess($response, 'publish container');

        return $response->json('id') ?? '';
    }

    private function waitForVideoReady(string $creationId, string $token): void
    {
        for ($attempt = 0; $attempt < self::VIDEO_POLL_ATTEMPTS; $attempt++) {
            sleep(self::VIDEO_POLL_DELAY_SECONDS);

            $response = Http::get(self::BASE_URL."/{$creationId}", [
                'fields' => 'status_code',
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $statusCode = $response->json('status_code');

                if ($statusCode === 'FINISHED') {
                    return;
                }

                if ($statusCode === 'ERROR') {
                    throw new RuntimeException('Instagram video processing failed. Check that the video URL is publicly accessible and in a supported format.');
                }
            }
        }

        throw new RuntimeException('Instagram video processing timed out after '.((self::VIDEO_POLL_ATTEMPTS * self::VIDEO_POLL_DELAY_SECONDS)).' seconds.');
    }

    private function isVideo(string $url): bool
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return in_array($ext, self::VIDEO_EXTENSIONS, true);
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
