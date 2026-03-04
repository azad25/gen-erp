<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\CMSServiceInterface;
use App\Domain\CMS\DTOs\CreatePageData;
use App\Domain\CMS\DTOs\UpdatePageData;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Enums\PageStatus;
use App\Http\Resources\PageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="CMS - Pages",
 *     description="CMS page management"
 * )
 * REST API v1 controller for CMS Page CRUD operations.
 */
class PageController extends BaseApiController
{
    public function __construct(
        private readonly CMSServiceInterface $cmsService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cms/pages",
     *     summary="List all pages for a site",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="site_id", in="query", required=true, description="Site ID", @OA\Schema(type="integer")),
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
            'site_id' => 'required|integer|exists:cms_sites,id',
        ]);

        $pages = $this->cmsService->getPagesForSite($request->integer('site_id'));

        return $this->success(PageResource::collection($pages));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/pages",
     *     summary="Create a new page",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"site_id", "title", "slug"},
     *             @OA\Property(property="site_id", type="integer"),
     *             @OA\Property(property="title", type="string", example="About Us"),
     *             @OA\Property(property="slug", type="string", example="about-us"),
     *             @OA\Property(property="seo_title", type="string"),
     *             @OA\Property(property="seo_description", type="string"),
     *             @OA\Property(property="seo_image", type="string"),
     *             @OA\Property(property="is_homepage", type="boolean", example=false),
     *             @OA\Property(property="sort_order", type="integer", example=0)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Page created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_image' => 'nullable|string',
            'is_homepage' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Check for unique slug within site
        $existingPage = Page::where('site_id', $validated['site_id'])
            ->where('slug', $validated['slug'])
            ->first();

        if ($existingPage) {
            return $this->error('A page with this slug already exists for this site.', 422);
        }

        $data = new CreatePageData(
            siteId: $validated['site_id'],
            title: $validated['title'],
            slug: $validated['slug'],
            seoTitle: $validated['seo_title'] ?? null,
            seoDescription: $validated['seo_description'] ?? null,
            seoImage: $validated['seo_image'] ?? null,
            status: PageStatus::DRAFT,
            isHomepage: $validated['is_homepage'] ?? false,
            sortOrder: $validated['sort_order'] ?? 0,
        );

        $page = $this->cmsService->createPage($data);

        return $this->success(new PageResource($page), 'Page created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cms/pages/{id}",
     *     summary="Get a specific page",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     )
     * )
     */
    public function show(Page $page): JsonResponse
    {
        $this->authorize('view', $page);

        $page->load(['sections', 'site']);

        return $this->success(new PageResource($page));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cms/pages/{id}",
     *     summary="Update a page",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="seo_title", type="string"),
     *             @OA\Property(property="seo_description", type="string"),
     *             @OA\Property(property="is_homepage", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page updated successfully"
     *     )
     * )
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_image' => 'nullable|string',
            'is_homepage' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        // Check for unique slug within site if slug is being updated
        if (isset($validated['slug'])) {
            $existingPage = Page::where('site_id', $page->site_id)
                ->where('slug', $validated['slug'])
                ->where('id', '!=', $page->id)
                ->first();

            if ($existingPage) {
                return $this->error('A page with this slug already exists for this site.', 422);
            }
        }

        $data = new UpdatePageData(
            title: $validated['title'] ?? null,
            slug: $validated['slug'] ?? null,
            seoTitle: $validated['seo_title'] ?? null,
            seoDescription: $validated['seo_description'] ?? null,
            seoImage: $validated['seo_image'] ?? null,
            isHomepage: $validated['is_homepage'] ?? null,
            sortOrder: $validated['sort_order'] ?? null,
        );

        $page = $this->cmsService->updatePage($page->id, $data);

        return $this->success(new PageResource($page), 'Page updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cms/pages/{id}",
     *     summary="Delete a page",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page deleted successfully"
     *     )
     * )
     */
    public function destroy(Page $page): JsonResponse
    {
        $this->authorize('delete', $page);

        $this->cmsService->deletePage($page->id);

        return $this->success(null, 'Page deleted successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/pages/{id}/publish",
     *     summary="Publish a page",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page published successfully"
     *     )
     * )
     */
    public function publish(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $page = $this->cmsService->publishPage($page->id);

        return $this->success(new PageResource($page), 'Page published successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/pages/{id}/unpublish",
     *     summary="Unpublish a page",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page unpublished successfully"
     *     )
     * )
     */
    public function unpublish(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $page = $this->cmsService->unpublishPage($page->id);

        return $this->success(new PageResource($page), 'Page unpublished successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/pages/{id}/set-homepage",
     *     summary="Set page as homepage",
     *     tags={"CMS - Pages"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Page ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Page set as homepage successfully"
     *     )
     * )
     */
    public function setHomepage(Page $page): JsonResponse
    {
        $this->authorize('update', $page);

        $page = $this->cmsService->setAsHomepage($page->id);

        return $this->success(new PageResource($page), 'Page set as homepage successfully.');
    }
}