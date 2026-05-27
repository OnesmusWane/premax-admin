<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CloudinaryService
{
    private Cloudinary $client;

    public function __construct()
    {
        $this->client = new Cloudinary(config('cloudinary.cloud_url'));
    }

    /**
     * Upload a file and return its secure URL.
     */
    public function upload(UploadedFile $file, string $folder, string $resourceType = 'image'): string
    {
        $result = $this->client->uploadApi()->upload($file->getRealPath(), [
            'folder'        => $folder,
            'resource_type' => $resourceType,
        ]);

        return $result['secure_url'];
    }

    /**
     * Upload a file and return the full Cloudinary result array.
     */
    public function uploadFull(UploadedFile $file, string $folder, string $resourceType = 'auto'): array
    {
        return (array) $this->client->uploadApi()->upload($file->getRealPath(), [
            'folder'        => $folder,
            'resource_type' => $resourceType,
        ]);
    }

    /**
     * Delete an asset. Handles Cloudinary URLs, local premax_website paths,
     * and legacy admin public-disk paths (gallery/xxx.jpg).
     */
    public function delete(string $path, string $legacyDisk = 'premax_website'): void
    {
        if ($this->isCloudinaryUrl($path)) {
            $publicId = $this->extractPublicId($path);
            if (! $publicId) {
                return;
            }

            $cloudName = config('cloudinary.cloud_name');
            $apiKey    = config('cloudinary.api_key');
            $apiSecret = config('cloudinary.api_secret');
            $timestamp = time();
            $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$apiSecret}");

            try {
                Http::post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
                    'public_id' => $publicId,
                    'api_key'   => $apiKey,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Cloudinary delete failed', ['public_id' => $publicId, 'error' => $e->getMessage()]);
            }

            return;
        }

        // Local path — delegate to the appropriate disk
        if ($path && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
            Storage::disk($legacyDisk)->delete($path);
        }
    }

    public function isCloudinaryUrl(string $path): bool
    {
        return str_contains($path, 'res.cloudinary.com');
    }

    private function extractPublicId(string $url): ?string
    {
        // https://res.cloudinary.com/CLOUD/image/upload/v123/folder/name.jpg
        if (preg_match('~/image/upload/(?:v\d+/)?(.+)\.[a-zA-Z0-9]+$~', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
