<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\CMSServiceInterface;
use App\Domain\CMS\DTOs\CreateSectionData;
use App\Domain\CMS\DTOs\UpdateSectionData;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Enums\SectionType;
use App\Http\Resources\SectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="CMS - Sections",
 *     description="CMS section management"
 * )
 * REST API v1 controller for CMS Section CRUD operations.
 */
class SectionController extends BaseApiController
{
    public function __construct(
        private readonly CMSServiceInterface $cmsService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cms/sections",
     *     summary="List all sections for a page",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="page_id", in="query", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page_id' => 'required|integer|exists:cms_pages,id',
        ]);

        $sections = $this->cmsService->getSectionsForPage($request->integer('page_id'));

        return $this->success(SectionResource::collection($sections));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/sections",
     *     summary="Create a new section",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"page_id", "type"},
     *             @OA\Property(property="page_id", type="integer"),
     *             @OA\Property(property="type", type="string", example="hero_banner"),
     *             @OA\Property(property="content", type="object"),
     *             @OA\Property(property="sort_order", type="integer", example=0),
     *             @OA\Property(property="is_visible", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Section created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_id' => 'required|integer|exists:cms_pages,id',
            'type' => 'required|string|in:' . implode(',', array_column(SectionType::cases(), 'value')),
            'content' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'is_visible' => 'nullable|boolean',
        ]);

        $sectionType = SectionType::from($validated['type']);

        $data = new CreateSectionData(
            pageId: $validated['page_id'],
            type: $sectionType,
            content: $validated['content'] ?? [],
            sortOrder: $validated['sort_order'] ?? 0,
            isVisible: $validated['is_visible'] ?? true,
        );

        $section = $this->cmsService->createSection($data);

        return $this->success(new SectionResource($section), 'Section created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cms/sections/{id}",
     *     summary="Get a specific section",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Section ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     )
     * )
     */
    public function show(Section $section): JsonResponse
    {
        $this->authorize('view', $section);

        return $this->success(new SectionResource($section));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cms/sections/{id}",
     *     summary="Update a section",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Section ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="content", type="object"),
     *             @OA\Property(property="sort_order", type="integer"),
     *             @OA\Property(property="is_visible", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Section updated successfully"
     *     )
     * )
     */
    public function update(Request $request, Section $section): JsonResponse
    {
        $this->authorize('update', $section);

        $validated = $request->validate([
            'type' => 'sometimes|string|in:' . implode(',', array_column(SectionType::cases(), 'value')),
            'content' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'is_visible' => 'nullable|boolean',
        ]);

        $sectionType = isset($validated['type']) ? SectionType::from($validated['type']) : null;

        $data = new UpdateSectionData(
            type: $sectionType,
            content: $validated['content'] ?? null,
            sortOrder: $validated['sort_order'] ?? null,
            isVisible: $validated['is_visible'] ?? null,
        );

        $section = $this->cmsService->updateSection($section->id, $data);

        return $this->success(new SectionResource($section), 'Section updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cms/sections/{id}",
     *     summary="Delete a section",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Section ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Section deleted successfully"
     *     )
     * )
     */
    public function destroy(Section $section): JsonResponse
    {
        $this->authorize('delete', $section);

        $this->cmsService->deleteSection($section->id);

        return $this->success(null, 'Section deleted successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/sections/{id}/duplicate",
     *     summary="Duplicate a section",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Section ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Section duplicated successfully"
     *     )
     * )
     */
    public function duplicate(Section $section): JsonResponse
    {
        $this->authorize('update', $section);

        $duplicatedSection = $this->cmsService->duplicateSection($section->id);

        return $this->success(new SectionResource($duplicatedSection), 'Section duplicated successfully.', 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cms/pages/{pageId}/sections/reorder",
     *     summary="Reorder sections for a page",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="pageId", in="path", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"section_ids"},
     *             @OA\Property(property="section_ids", type="array", @OA\Items(type="integer"), example={1, 3, 2, 4})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sections reordered successfully"
     *     )
     * )
     */
    public function reorder(Request $request, int $pageId): JsonResponse
    {
        $validated = $request->validate([
            'section_ids' => 'required|array',
            'section_ids.*' => 'integer|exists:cms_sections,id',
        ]);

        // Verify all sections belong to the page
        $sectionsCount = Section::where('page_id', $pageId)
            ->whereIn('id', $validated['section_ids'])
            ->count();

        if ($sectionsCount !== count($validated['section_ids'])) {
            return $this->error('Some sections do not belong to this page.', 422);
        }

        $this->cmsService->reorderSections($pageId, $validated['section_ids']);

        return $this->success(null, 'Sections reordered successfully.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cms/section-types",
     *     summary="Get all available section types",
     *     tags={"CMS - Sections"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function sectionTypes(): JsonResponse
    {
        $sectionTypes = collect(SectionType::cases())->map(function (SectionType $type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'category' => $type->category(),
                'icon' => $type->icon(),
                'default_content' => $type->getDefaultContent(),
            ];
        })->groupBy('category');

        return $this->success($sectionTypes);
    }
}