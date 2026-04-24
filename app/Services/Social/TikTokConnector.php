<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use App\Models\SocialPostTarget;
use App\Services\Social\Contracts\SocialPlatformPublisher;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TikTokConnector implements SocialPlatformPublisher
{
    private const BASE_URL = 'https://open.tiktokapis.com/v2';

    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'webm', 'mkv'];

    // TikTok caption max length
    private const TITLE_MAX_LENGTH = 150;

    public function __construct(private readonly array $credentials) {}

    public function publish(SocialPost $post, SocialPostTarget $_target): string
    {
        $token = $this->credentials['access_token'] ?? null;

        if (! filled($token)) {
            throw new RuntimeException(
                'TikTok access_token credential is required to publish. '.
                'Update the account credentials and try again.'
            );
        }

        $media = $post->media ?? [];

        if (empty($media)) {
            throw new RuntimeException(
                'TikTok requires at least one image or video URL in the media field. '.
                'Add a media URL to the post and try again.'
            );
        }

        // If any media item is a video, use the video endpoint with the first video URL
        $videoUrl = collect($media)->first(fn ($url) => $this->isVideo($url));

        if ($videoUrl) {
            return $this->publishVideo($post, $token, $videoUrl);
        }

        return $this->publishPhotos($post, $token, $media);
    }

    public function updatePublishedPost(SocialPost $post, SocialPostTarget $target): void
    {
        throw new RuntimeException('TikTok published post editing is not supported by this connector workflow. Create a new post instead.');
    }

    public function deletePublishedPost(SocialPostTarget $target): void
    {
        throw new RuntimeException('TikTok published post deletion is not supported by this connector workflow.');
    }

    public function publishCommentReply(string $platformCommentId, string $message): string
    {
        throw new RuntimeException('TikTok comment replies are not yet supported by this connector.');
    }

    private function publishVideo(SocialPost $post, string $token, string $videoUrl): string
    {
        $response = Http::withToken($token)
            ->contentType('application/json; charset=UTF-8')
            ->post(self::BASE_URL.'/post/publish/video/init/', [
                'post_info' => [
                    'title' => mb_substr($post->content, 0, self::TITLE_MAX_LENGTH),
                    'privacy_level' => 'PUBLIC_TO_EVERYONE',
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                    'video_cover_timestamp_ms' => 1000,
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $videoUrl,
                ],
            ]);

        $this->assertSuccess($response);

        return $response->json('data.publish_id') ?? $response->json('data.item_id') ?? '';
    }

    private function publishPhotos(SocialPost $post, string $token, array $photoUrls): string
    {
        $response = Http::withToken($token)
            ->contentType('application/json; charset=UTF-8')
            ->post(self::BASE_URL.'/post/publish/content/init/', [
                'post_info' => [
                    'title' => mb_substr($post->content, 0, self::TITLE_MAX_LENGTH),
                    'privacy_level' => 'PUBLIC_TO_EVERYONE',
                    'disable_duet' => false,
                    'disable_comment' => false,
                    'disable_stitch' => false,
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'photo_images' => array_values($photoUrls),
                    'photo_cover_index' => 0,
                ],
                'post_mode' => 'DIRECT_POST',
                'media_type' => 'PHOTO',
            ]);

        $this->assertSuccess($response);

        return $response->json('data.publish_id') ?? $response->json('data.item_id') ?? '';
    }

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
