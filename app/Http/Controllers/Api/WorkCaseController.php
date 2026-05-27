<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkCase;
use App\Models\WorkCaseStep;
use App\Models\WorkCaseMetric;
use App\Models\WorkCaseGallery;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WorkCaseController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function index()
    {
        $cases = WorkCase::with([
                'steps:id,work_case_id,step_number,title,detail',
                'metrics:id,work_case_id,label,value',
                'gallery:id,work_case_id,image_path,sort_order',
            ])
            ->withCount(['steps', 'metrics', 'gallery'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json($cases);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'category'        => 'required|in:detailing,performance,bodywork,diagnostics',
            'service_type'    => 'nullable|string|max:255',
            'before_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'after_image'     => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'brief'           => 'nullable|string',
            'challenge'       => 'nullable|string',
            'outcome'         => 'nullable|string',
            'duration_days'   => 'nullable|integer|min:0',
            'completed_at'    => 'nullable|date',
            'client_type'     => 'nullable|string|max:255',
            'is_featured'     => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
            'steps'           => 'nullable|string',
            'metrics'         => 'nullable|string',
            'gallery_images'  => 'nullable|array',
            'gallery_images.*'=> 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
        ]);

        $slug = WorkCase::generateSlug($data['title']);

        $beforeImagePath = null;
        if ($request->hasFile('before_image')) {
            $beforeImagePath = $this->cloudinary->upload($request->file('before_image'), 'premax/works');
        }

        $afterImagePath = null;
        if ($request->hasFile('after_image')) {
            $afterImagePath = $this->cloudinary->upload($request->file('after_image'), 'premax/works');
        }

        $case = DB::transaction(function () use ($data, $slug, $beforeImagePath, $afterImagePath, $request) {
            $case = WorkCase::create([
                'title'         => $data['title'],
                'slug'          => $slug,
                'category'      => $data['category'],
                'service_type'  => $data['service_type'] ?? null,
                'before_image'  => $beforeImagePath,
                'after_image'   => $afterImagePath,
                'brief'         => $data['brief'] ?? null,
                'challenge'     => $data['challenge'] ?? null,
                'outcome'       => $data['outcome'] ?? null,
                'duration_days' => $data['duration_days'] ?? null,
                'completed_at'  => $data['completed_at'] ?? null,
                'client_type'   => $data['client_type'] ?? null,
                'is_featured'   => filter_var($data['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_active'     => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'sort_order'    => WorkCase::max('sort_order') + 1,
            ]);

            // Steps
            if (!empty($data['steps'])) {
                $steps = json_decode($data['steps'], true) ?? [];
                foreach ($steps as $index => $step) {
                    WorkCaseStep::create([
                        'work_case_id' => $case->id,
                        'step_number'  => $index + 1,
                        'title'        => $step['title'] ?? '',
                        'detail'       => $step['detail'] ?? null,
                    ]);
                }
            }

            // Metrics
            if (!empty($data['metrics'])) {
                $metrics = json_decode($data['metrics'], true) ?? [];
                foreach ($metrics as $metric) {
                    WorkCaseMetric::create([
                        'work_case_id' => $case->id,
                        'label'        => $metric['label'] ?? '',
                        'value'        => $metric['value'] ?? '',
                    ]);
                }
            }

            // Gallery images
            $galleryFiles = $request->file('gallery_images', []);
            foreach ($galleryFiles as $index => $file) {
                $path = $this->cloudinary->upload($file, 'premax/works');
                WorkCaseGallery::create([
                    'work_case_id' => $case->id,
                    'image_path'   => $path,
                    'sort_order'   => $index + 1,
                ]);
            }

            return $case;
        });

        Cache::forget('work.cases');

        return response()->json($case->load(['steps', 'metrics', 'gallery']), 201);
    }

    public function update(Request $request, WorkCase $workCase)
    {
        $data = $request->validate([
            'title'           => 'sometimes|required|string|max:255',
            'category'        => 'sometimes|required|in:detailing,performance,bodywork,diagnostics',
            'service_type'    => 'nullable|string|max:255',
            'before_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'after_image'     => 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'brief'           => 'nullable|string',
            'challenge'       => 'nullable|string',
            'outcome'         => 'nullable|string',
            'duration_days'   => 'nullable|integer|min:0',
            'completed_at'    => 'nullable|date',
            'client_type'     => 'nullable|string|max:255',
            'is_featured'     => 'nullable|boolean',
            'is_active'       => 'nullable|boolean',
            'steps'           => 'nullable|string',
            'metrics'         => 'nullable|string',
            'gallery_images'  => 'nullable|array',
            'gallery_images.*'=> 'nullable|image|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'gallery_keep'    => 'nullable|string',
        ]);

        $fillable = [];

        if (isset($data['title'])) {
            $fillable['title'] = $data['title'];
            // Re-generate slug only if title changed
            if ($data['title'] !== $workCase->title) {
                $fillable['slug'] = WorkCase::generateSlug($data['title']);
            }
        }

        foreach (['category', 'service_type', 'brief', 'challenge', 'outcome', 'duration_days', 'completed_at', 'client_type'] as $field) {
            if (array_key_exists($field, $data)) {
                $fillable[$field] = $data[$field];
            }
        }

        if (array_key_exists('is_featured', $data)) {
            $fillable['is_featured'] = filter_var($data['is_featured'], FILTER_VALIDATE_BOOLEAN);
        }
        if (array_key_exists('is_active', $data)) {
            $fillable['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        // Handle before_image replacement
        if ($request->hasFile('before_image')) {
            if ($workCase->before_image) {
                $this->cloudinary->delete($workCase->before_image);
            }
            $fillable['before_image'] = $this->cloudinary->upload($request->file('before_image'), 'premax/works');
        }

        // Handle after_image replacement
        if ($request->hasFile('after_image')) {
            if ($workCase->after_image) {
                $this->cloudinary->delete($workCase->after_image);
            }
            $fillable['after_image'] = $this->cloudinary->upload($request->file('after_image'), 'premax/works');
        }

        DB::transaction(function () use ($workCase, $fillable, $data, $request) {
            if (!empty($fillable)) {
                $workCase->update($fillable);
            }

            // Replace steps
            if (array_key_exists('steps', $data)) {
                $workCase->steps()->delete();
                $steps = json_decode($data['steps'] ?? '[]', true) ?? [];
                foreach ($steps as $index => $step) {
                    WorkCaseStep::create([
                        'work_case_id' => $workCase->id,
                        'step_number'  => $index + 1,
                        'title'        => $step['title'] ?? '',
                        'detail'       => $step['detail'] ?? null,
                    ]);
                }
            }

            // Replace metrics
            if (array_key_exists('metrics', $data)) {
                $workCase->metrics()->delete();
                $metrics = json_decode($data['metrics'] ?? '[]', true) ?? [];
                foreach ($metrics as $metric) {
                    WorkCaseMetric::create([
                        'work_case_id' => $workCase->id,
                        'label'        => $metric['label'] ?? '',
                        'value'        => $metric['value'] ?? '',
                    ]);
                }
            }

            // Gallery: keep specified images, delete the rest, add new uploads
            if (array_key_exists('gallery_keep', $data) || $request->hasFile('gallery_images')) {
                $keepPaths = json_decode($data['gallery_keep'] ?? '[]', true) ?? [];

                // Delete gallery items not in keep list
                $toDelete = $workCase->gallery()->whereNotIn('image_path', $keepPaths)->get();
                foreach ($toDelete as $item) {
                    $this->cloudinary->delete($item->image_path);
                    $item->delete();
                }

                // Add new gallery uploads
                $existingMax = $workCase->gallery()->max('sort_order') ?? 0;
                $galleryFiles = $request->file('gallery_images', []);
                foreach ($galleryFiles as $index => $file) {
                    $path = $this->cloudinary->upload($file, 'premax/works');
                    WorkCaseGallery::create([
                        'work_case_id' => $workCase->id,
                        'image_path'   => $path,
                        'sort_order'   => $existingMax + $index + 1,
                    ]);
                }
            }
        });

        Cache::forget('work.cases');

        return response()->json($workCase->fresh()->load(['steps', 'metrics', 'gallery']));
    }

    public function destroy(WorkCase $workCase)
    {
        if ($workCase->before_image) {
            $this->cloudinary->delete($workCase->before_image);
        }
        if ($workCase->after_image) {
            $this->cloudinary->delete($workCase->after_image);
        }

        foreach ($workCase->gallery as $item) {
            $this->cloudinary->delete($item->image_path);
        }

        $workCase->steps()->delete();
        $workCase->metrics()->delete();
        $workCase->gallery()->delete();
        $workCase->delete();

        Cache::forget('work.cases');

        return response()->json(['message' => 'Work case deleted.'], 200);
    }

    public function toggleFeatured(WorkCase $workCase)
    {
        $workCase->update(['is_featured' => !$workCase->is_featured]);
        Cache::forget('work.cases');
        return response()->json($workCase->fresh());
    }

    public function toggleActive(WorkCase $workCase)
    {
        $workCase->update(['is_active' => !$workCase->is_active]);
        Cache::forget('work.cases');
        return response()->json($workCase->fresh());
    }

}
