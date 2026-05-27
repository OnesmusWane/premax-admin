<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    private const MAX_FILES = 12;
    private const MAX_FILE_SIZE_KB = 10240;

    public function __construct(private CloudinaryService $cloudinary) {}

    public function publicIndex()
    {
        return response()->json(
            GalleryItem::query()
                ->with('service:id,name,slug')
                ->published()
                ->ordered()
                ->get()
        );
    }

    public function index()
    {
        return response()->json(
            GalleryItem::query()
                ->with('service:id,name,slug')
                ->ordered()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'images'       => 'required|array|min:1|max:'.self::MAX_FILES,
            'images.*'     => 'required|image|mimes:jpg,jpeg,png,webp,avif|max:'.self::MAX_FILE_SIZE_KB,
            'title'        => 'nullable|string|max:120',
            'alt_text'     => 'nullable|string|max:160',
            'description'  => 'nullable|string|max:1000',
            'is_published' => 'nullable|boolean',
            'service_id'   => 'nullable|exists:services,id',
        ]);

        $nextOrder = (int) GalleryItem::max('sort_order');
        $created   = [];

        DB::transaction(function () use ($request, $data, $nextOrder, &$created) {
            $files = $request->file('images', []);
            foreach ($files as $index => $image) {
                $url = $this->cloudinary->upload($image, 'premax/gallery');

                $created[] = GalleryItem::create([
                    'title'        => count($files) === 1
                        ? ($data['title'] ?? null)
                        : Str::headline(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)),
                    'alt_text'     => $data['alt_text'] ?? null,
                    'description'  => $data['description'] ?? null,
                    'image_path'   => $url,
                    'is_published' => $data['is_published'] ?? true,
                    'sort_order'   => $nextOrder + $index + 1,
                    'user_id'      => $request->user()?->id,
                    'service_id'   => $data['service_id'] ?? null,
                ]);
            }
        });

        return response()->json(
            GalleryItem::query()
                ->with('service:id,name,slug')
                ->whereIn('id', collect($created)->pluck('id'))
                ->ordered()
                ->get(),
            201
        );
    }

    public function update(Request $request, GalleryItem $galleryItem)
    {
        $data = $request->validate([
            'title'        => 'nullable|string|max:120',
            'alt_text'     => 'nullable|string|max:160',
            'description'  => 'nullable|string|max:1000',
            'sort_order'   => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'service_id'   => 'nullable|exists:services,id',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:'.self::MAX_FILE_SIZE_KB,
        ]);

        if ($request->hasFile('image')) {
            if ($galleryItem->image_path) {
                // Legacy items used the admin public disk; Cloudinary items use the CDN
                $this->cloudinary->delete($galleryItem->image_path, 'public');
            }

            $data['image_path'] = $this->cloudinary->upload($request->file('image'), 'premax/gallery');
        }

        $galleryItem->update($data);

        return response()->json($galleryItem->fresh()->load('service:id,name,slug'));
    }

    public function destroy(GalleryItem $galleryItem)
    {
        if ($galleryItem->image_path) {
            $this->cloudinary->delete($galleryItem->image_path, 'public');
        }

        $galleryItem->delete();

        return response()->json(['message' => 'Gallery item deleted.']);
    }
}
