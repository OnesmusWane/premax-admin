<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GalleryController extends Controller
{
    private const MAX_FILES = 12;
    private const MAX_FILE_SIZE_KB = 4096;
    private const MAX_WIDTH = 2400;
    private const MAX_HEIGHT = 1600;
    private const MAX_PIXELS = 3_840_000;

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
            'images' => 'required|array|min:1|max:'.self::MAX_FILES,
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp,avif|max:'.self::MAX_FILE_SIZE_KB,
            'title' => 'nullable|string|max:120',
            'alt_text' => 'nullable|string|max:160',
            'description' => 'nullable|string|max:1000',
            'is_published' => 'nullable|boolean',
            'service_id' => 'nullable|exists:services,id',
        ]);

        foreach ($request->file('images', []) as $image) {
            $this->ensureImageDimensions($image);
        }

        $nextOrder = (int) GalleryItem::max('sort_order');
        $created = [];

        DB::transaction(function () use ($request, $data, $nextOrder, &$created) {
            foreach ($request->file('images', []) as $index => $image) {
                $path = $image->store('gallery', 'public');

                $created[] = GalleryItem::create([
                    'title' => count($request->file('images', [])) === 1
                        ? ($data['title'] ?? null)
                        : Str::headline(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)),
                    'alt_text' => $data['alt_text'] ?? null,
                    'description' => $data['description'] ?? null,
                    'image_path' => $path,
                    'is_published' => $data['is_published'] ?? true,
                    'sort_order' => $nextOrder + $index + 1,
                    'user_id' => $request->user()?->id,
                    'service_id' => $data['service_id'] ?? null,
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
            'title' => 'nullable|string|max:120',
            'alt_text' => 'nullable|string|max:160',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'service_id' => 'nullable|exists:services,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:'.self::MAX_FILE_SIZE_KB,
        ]);

        if ($request->hasFile('image')) {
            $this->ensureImageDimensions($request->file('image'));

            if ($galleryItem->image_path) {
                Storage::disk('public')->delete($galleryItem->image_path);
            }

            $data['image_path'] = $request->file('image')->store('gallery', 'public');
        }

        $galleryItem->update($data);

        return response()->json($galleryItem->fresh()->load('service:id,name,slug'));
    }

    public function destroy(GalleryItem $galleryItem)
    {
        if ($galleryItem->image_path) {
            Storage::disk('public')->delete($galleryItem->image_path);
        }

        $galleryItem->delete();

        return response()->json(['message' => 'Gallery item deleted.']);
    }

    private function ensureImageDimensions($image): void
    {
        [$width, $height] = getimagesize($image->getRealPath()) ?: [null, null];

        if (! $width || ! $height) {
            throw ValidationException::withMessages([
                'images' => ['One of the selected files could not be read as a valid image.'],
            ]);
        }

        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            throw ValidationException::withMessages([
                'images' => [
                    'Each image must be at most '
                    .self::MAX_WIDTH.'x'.self::MAX_HEIGHT
                    .' pixels.',
                ],
            ]);
        }

        if (($width * $height) > self::MAX_PIXELS) {
            throw ValidationException::withMessages([
                'images' => [
                    'Each image must be at most '.number_format(self::MAX_PIXELS).' total pixels.',
                ],
            ]);
        }
    }
}
