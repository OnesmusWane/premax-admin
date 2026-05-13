<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MediaLibraryController extends Controller
{
    /**
     * Paginated list of media items.
     * GET /api/media-library?type=image&search=car&per_page=24
     */
    public function index(Request $request)
    {
        $type    = $request->string('type')->toString();
        $search  = $request->string('search')->toString();
        $perPage = (int) $request->input('per_page', 24);
        $perPage = min(max($perPage, 1), 100);

        $query = MediaLibrary::query()->with('creator:id,name')->latest();

        if (filled($type)) {
            $query->forType($type);
        }

        if (filled($search)) {
            $query->search($search);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn (MediaLibrary $m) => $this->serialize($m)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * Show a single media item.
     * GET /api/media-library/{media}
     */
    public function show(MediaLibrary $media)
    {
        $media->load('creator:id,name');

        return response()->json($this->serialize($media));
    }

    /**
     * Delete a media item and remove it from Cloudinary.
     * DELETE /api/media-library/{media}
     */
    public function destroy(MediaLibrary $media)
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey    = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');
        $publicId  = $media->cloudinary_public_id;

        if (filled($cloudName) && filled($apiKey) && filled($apiSecret) && filled($publicId)) {
            $resourceType = $media->type === 'video' ? 'video' : 'image';
            $timestamp    = time();
            $paramsToSign = "public_id={$publicId}&timestamp={$timestamp}";
            $signature    = sha1($paramsToSign . $apiSecret);

            try {
                $response = Http::post(
                    "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/destroy",
                    [
                        'public_id' => $publicId,
                        'api_key'   => $apiKey,
                        'timestamp' => $timestamp,
                        'signature' => $signature,
                    ]
                );

                if (! $response->successful()) {
                    Log::warning('Cloudinary deletion returned non-success', [
                        'public_id' => $publicId,
                        'status'    => $response->status(),
                        'body'      => $response->json(),
                    ]);
                } else {
                    Log::info('Cloudinary asset deleted', ['public_id' => $publicId]);
                }
            } catch (\Throwable $e) {
                Log::error('Cloudinary deletion exception', [
                    'public_id' => $publicId,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        $id = $media->id;
        $media->delete();

        Log::info('Media library item deleted', ['id' => $id, 'public_id' => $publicId ?? null]);

        return response()->json(['message' => 'Media deleted successfully.']);
    }

    /**
     * Update tags on a media item.
     * PUT /api/media-library/{media}/tags
     */
    public function updateTags(Request $request, MediaLibrary $media)
    {
        $data = $request->validate([
            'tags'   => 'required|array|max:10',
            'tags.*' => 'string|max:50',
        ]);

        $media->update(['tags' => $data['tags']]);

        Log::info('Media library tags updated', ['id' => $media->id, 'tags' => $data['tags']]);

        return response()->json($this->serialize($media->fresh()->load('creator:id,name')));
    }

    /**
     * Track usage when a media item is used in a post.
     * POST /api/media-library/{media}/track-usage
     */
    public function trackUsage(MediaLibrary $media)
    {
        $media->increment('used_count');
        $media->update(['last_used_at' => now()]);

        Log::info('Media library usage tracked', ['id' => $media->id, 'used_count' => $media->used_count]);

        return response()->json([
            'message'    => 'Usage tracked.',
            'used_count' => $media->used_count,
        ]);
    }

    private function serialize(MediaLibrary $media): array
    {
        return [
            'id'             => $media->id,
            'name'           => $media->name,
            'url'            => $media->url,
            'type'           => $media->type,
            'mime_type'      => $media->mime_type,
            'size_formatted' => $media->size_formatted,
            'width'          => $media->width,
            'height'         => $media->height,
            'duration'       => $media->duration,
            'tags'           => $media->tags ?? [],
            'used_count'     => $media->used_count,
            'last_used_at'   => optional($media->last_used_at)?->toISOString(),
            'created_by'     => $media->creator ? [
                'id'   => $media->creator->id,
                'name' => $media->creator->name,
            ] : null,
            'created_at'     => optional($media->created_at)?->toISOString(),
        ];
    }
}
