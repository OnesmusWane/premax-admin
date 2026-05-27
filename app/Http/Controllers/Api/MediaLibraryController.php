<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaLibraryController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    /** Public: published images only — used by the website gallery page */
    public function publicIndex()
    {
        $items = MediaLibrary::where('type', 'image')
            ->where('is_published', true)
            ->with('name,slug')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($items);
    }

    /** Admin: full list with filtering, search, pagination */
    public function index(Request $request)
    {
        $type    = $request->string('type')->toString();
        $search  = $request->string('search')->toString();
        $perPage = min(max((int) $request->input('per_page', 24), 1), 100);

        $query = MediaLibrary::query()->latest();

        if (filled($type)) {
            $query->where('type', $type);
        }

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereJsonContains('tags', $search);
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    public function show(MediaLibrary $media)
    {
        return response()->json($media);
    }

    /** Upload a new media file to Cloudinary and save the record */
    public function store(Request $request)
    {
        $request->validate([
            'file'         => 'required|file|mimes:jpg,jpeg,png,webp,avif,mp4,mov|max:102400',
            'alt_text'     => 'nullable|string|max:160',
            'description'  => 'nullable|string|max:1000',
            'is_published' => 'nullable|boolean',
        ]);

        $file     = $request->file('file');
        $mimeType = $file->getMimeType() ?? '';
        $isVideo  = str_starts_with($mimeType, 'video/');

        try {
            $result = $this->cloudinary->uploadFull($file, 'premax/media', 'auto');

            $media = MediaLibrary::create([
                'name'                 => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'alt_text'             => $request->input('alt_text'),
                'description'          => $request->input('description'),
                'url'                  => $result['secure_url'],
                'cloudinary_public_id' => $result['public_id'],
                'mime_type'            => $mimeType,
                'type'                 => $isVideo ? 'video' : 'image',
                'size'                 => $result['bytes'] ?? $file->getSize(),
                'width'                => $result['width'] ?? null,
                'height'               => $result['height'] ?? null,
                'duration'             => $isVideo ? (int) ($result['duration'] ?? 0) ?: null : null,
                'is_published'         => $request->boolean('is_published', false),
                'sort_order'           => (MediaLibrary::max('sort_order') ?? 0) + 1,
                'created_by'           => $request->user()?->id,
            ]);

            return response()->json($media, 201);
        } catch (\Throwable $e) {
            Log::error('Media upload failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    /** Update metadata: name, alt_text, description, tags, is_published, service_id, sort_order */
    public function update(Request $request, MediaLibrary $media)
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'alt_text'     => 'nullable|string|max:160',
            'description'  => 'nullable|string|max:1000',
            'tags'         => 'nullable|array|max:10',
            'tags.*'       => 'string|max:50',
            'is_published' => 'nullable|boolean',
            'sort_order'   => 'nullable|integer|min:0',
        ]);

        $media->update($data);

        return response()->json($media->fresh());
    }

    public function destroy(MediaLibrary $media)
    {
        $this->cloudinary->delete($media->url);
        $media->delete();

        return response()->json(['message' => 'Media deleted.']);
    }

    public function trackUsage(MediaLibrary $media)
    {
        $media->increment('used_count');
        $media->update(['last_used_at' => now()]);

        return response()->json(['used_count' => $media->used_count]);
    }
}
