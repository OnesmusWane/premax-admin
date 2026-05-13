<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CommentTemplateController extends Controller
{
    /**
     * List all comment templates, filterable by platform and search query.
     * GET /api/comment-templates?platform=facebook&search=price
     */
    public function index(Request $request)
    {
        $platform = $request->string('platform')->toString();
        $search   = $request->string('search')->toString();

        $query = CommentTemplate::query()->with('creator:id,name');

        if (filled($platform) && $platform !== 'all') {
            $query->forPlatform($platform);
        }

        if (filled($search)) {
            $query->search($search);
        }

        $templates = $query->orderByDesc('usage_count')->orderBy('name')->get();

        Log::info('Comment templates listed', [
            'platform' => $platform ?: 'all',
            'search'   => $search ?: null,
            'count'    => $templates->count(),
        ]);

        return response()->json([
            'templates' => $templates->map(fn (CommentTemplate $t) => $this->serialize($t)),
        ]);
    }

    /**
     * Create a new comment template.
     * POST /api/comment-templates
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'body'     => 'required|string|max:2000',
            'platform' => ['nullable', Rule::in(['facebook', 'instagram', 'tiktok', 'all'])],
            'shortcut' => 'nullable|string|max:30|unique:comment_templates,shortcut',
        ]);

        $template = CommentTemplate::create([
            ...$data,
            'created_by' => $request->user()?->id,
        ]);

        Log::info('Comment template created', ['id' => $template->id, 'name' => $template->name]);

        return response()->json(
            $this->serialize($template->fresh()->load('creator:id,name')),
            201
        );
    }

    /**
     * Update an existing comment template.
     * PUT /api/comment-templates/{commentTemplate}
     */
    public function update(Request $request, CommentTemplate $commentTemplate)
    {
        $data = $request->validate([
            'name'     => 'sometimes|required|string|max:120',
            'body'     => 'sometimes|required|string|max:2000',
            'platform' => ['nullable', Rule::in(['facebook', 'instagram', 'tiktok', 'all'])],
            'shortcut' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('comment_templates', 'shortcut')->ignore($commentTemplate->id),
            ],
        ]);

        $commentTemplate->update($data);

        Log::info('Comment template updated', ['id' => $commentTemplate->id]);

        return response()->json(
            $this->serialize($commentTemplate->fresh()->load('creator:id,name'))
        );
    }

    /**
     * Delete a comment template.
     * DELETE /api/comment-templates/{commentTemplate}
     */
    public function destroy(CommentTemplate $commentTemplate)
    {
        $id = $commentTemplate->id;
        $commentTemplate->delete();

        Log::info('Comment template deleted', ['id' => $id]);

        return response()->json(['message' => 'Template deleted successfully.']);
    }

    /**
     * Increment usage count and return the template body ready to use.
     * POST /api/comment-templates/{commentTemplate}/use
     */
    public function use(CommentTemplate $commentTemplate)
    {
        $commentTemplate->increment('usage_count');

        Log::info('Comment template used', [
            'id'          => $commentTemplate->id,
            'usage_count' => $commentTemplate->usage_count,
        ]);

        return response()->json([
            'body'     => $commentTemplate->body,
            'template' => $this->serialize($commentTemplate->fresh()->load('creator:id,name')),
        ]);
    }

    private function serialize(CommentTemplate $template): array
    {
        return [
            'id'          => $template->id,
            'name'        => $template->name,
            'body'        => $template->body,
            'platform'    => $template->platform,
            'shortcut'    => $template->shortcut,
            'usage_count' => $template->usage_count,
            'created_by'  => $template->creator ? [
                'id'   => $template->creator->id,
                'name' => $template->creator->name,
            ] : null,
            'created_at'  => optional($template->created_at)?->toISOString(),
        ];
    }
}
