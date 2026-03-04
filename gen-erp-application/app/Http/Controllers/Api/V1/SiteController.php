<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\CMSServiceInterface;
use App\Domain\CMS\DTOs\CreateSiteData;
use App\Domain\CMS\DTOs\UpdateSiteData;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Enums\SiteStatus;
use App\Http\Resources\SiteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="CMS - Sites",
 *     description="CMS site management"
 * )
 * REST API v1 controller for CMS Site CRUD operations.
 */
class SiteController extends BaseApiController
{
    public function __construct(
        private readonly CMSServiceInterface $cmsService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/cms/sites",
     *     summary="List all sites for the authenticated company",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->currentCompany->id;
        $sites = $this->cmsService->getSitesForCompany($companyId);

        return $this->success(SiteResource::collection($sites));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/sites",
     *     summary="Create a new site",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="My Business Site"),
     *             @OA\Property(property="slug", type="string", example="my-business"),
     *             @OA\Property(property="subdomain", type="string", example="mybusiness"),
     *             @OA\Property(property="domain", type="string", example="mybusiness.com"),
     *             @OA\Property(property="logo", type="string"),
     *             @OA\Property(property="favicon", type="string"),
     *             @OA\Property(property="primary_color", type="string", example="#3B82F6"),
     *             @OA\Property(property="accent_color", type="string", example="#10B981"),
     *             @OA\Property(property="font_family", type="string", example="Inter"),
     *             @OA\Property(property="seo_title", type="string"),
     *             @OA\Property(property="seo_description", type="string"),
     *             @OA\Property(property="google_analytics_id", type="string"),
     *             @OA\Property(property="facebook_pixel_id", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Site created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_sites,slug',
            'subdomain' => 'nullable|string|max:255|unique:cms_sites,subdomain',
            'domain' => 'nullable|string|max:255|unique:cms_sites,domain',
            'logo' => 'nullable|string',
            'favicon' => 'nullable|string',
            'primary_color' => 'nullable|string|size:7',
            'accent_color' => 'nullable|string|size:7',
            'font_family' => 'nullable|string|max:100',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_image' => 'nullable|string',
            'google_analytics_id' => 'nullable|string|max:50',
            'facebook_pixel_id' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);

        $companyId = $request->user()->currentCompany->id;

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Auto-generate subdomain if not provided
        if (empty($validated['subdomain'])) {
            $validated['subdomain'] = Str::slug($validated['name']);
        }

        $data = new CreateSiteData(
            companyId: $companyId,
            name: $validated['name'],
            slug: $validated['slug'],
            domain: $validated['domain'] ?? null,
            subdomain: $validated['subdomain'],
            logo: $validated['logo'] ?? null,
            favicon: $validated['favicon'] ?? null,
            primaryColor: $validated['primary_color'] ?? '#3B82F6',
            accentColor: $validated['accent_color'] ?? '#10B981',
            fontFamily: $validated['font_family'] ?? 'Inter',
            status: SiteStatus::DRAFT,
            seoTitle: $validated['seo_title'] ?? null,
            seoDescription: $validated['seo_description'] ?? null,
            seoImage: $validated['seo_image'] ?? null,
            googleAnalyticsId: $validated['google_analytics_id'] ?? null,
            facebookPixelId: $validated['facebook_pixel_id'] ?? null,
            settings: $validated['settings'] ?? null,
        );

        $site = $this->cmsService->createSite($data);

        return $this->success(new SiteResource($site), 'Site created successfully.', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cms/sites/{id}",
     *     summary="Get a specific site",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Site ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $site->load(['pages', 'menus', 'blogPosts', 'blogCategories']);

        return $this->success(new SiteResource($site));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cms/sites/{id}",
     *     summary="Update a site",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Site ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="subdomain", type="string"),
     *             @OA\Property(property="domain", type="string"),
     *             @OA\Property(property="logo", type="string"),
     *             @OA\Property(property="primary_color", type="string"),
     *             @OA\Property(property="accent_color", type="string"),
     *             @OA\Property(property="seo_title", type="string"),
     *             @OA\Property(property="seo_description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Site updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:cms_sites,slug,' . $site->id,
            'subdomain' => 'sometimes|string|max:255|unique:cms_sites,subdomain,' . $site->id,
            'domain' => 'nullable|string|max:255|unique:cms_sites,domain,' . $site->id,
            'logo' => 'nullable|string',
            'favicon' => 'nullable|string',
            'primary_color' => 'sometimes|string|size:7',
            'accent_color' => 'sometimes|string|size:7',
            'font_family' => 'sometimes|string|max:100',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_image' => 'nullable|string',
            'google_analytics_id' => 'nullable|string|max:50',
            'facebook_pixel_id' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);

        $data = new UpdateSiteData(
            name: $validated['name'] ?? null,
            slug: $validated['slug'] ?? null,
            domain: $validated['domain'] ?? null,
            subdomain: $validated['subdomain'] ?? null,
            logo: $validated['logo'] ?? null,
            favicon: $validated['favicon'] ?? null,
            primaryColor: $validated['primary_color'] ?? null,
            accentColor: $validated['accent_color'] ?? null,
            fontFamily: $validated['font_family'] ?? null,
            seoTitle: $validated['seo_title'] ?? null,
            seoDescription: $validated['seo_description'] ?? null,
            seoImage: $validated['seo_image'] ?? null,
            googleAnalyticsId: $validated['google_analytics_id'] ?? null,
            facebookPixelId: $validated['facebook_pixel_id'] ?? null,
            settings: $validated['settings'] ?? null,
        );

        $site = $this->cmsService->updateSite($site->id, $data);

        return $this->success(new SiteResource($site), 'Site updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cms/sites/{id}",
     *     summary="Delete a site",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Site ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Site deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Site $site): JsonResponse
    {
        $this->authorize('delete', $site);

        $this->cmsService->deleteSite($site->id);

        return $this->success(null, 'Site deleted successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/sites/{id}/publish",
     *     summary="Publish a site",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Site ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Site published successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function publish(Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $site = $this->cmsService->publishSite($site->id);

        return $this->success(new SiteResource($site), 'Site published successfully.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cms/sites/{id}/unpublish",
     *     summary="Unpublish a site",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Site ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Site unpublished successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function unpublish(Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $site = $this->cmsService->unpublishSite($site->id);

        return $this->success(new SiteResource($site), 'Site unpublished successfully.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cms/sites/{id}/statistics",
     *     summary="Get site statistics",
     *     tags={"CMS - Sites"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Site ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function statistics(Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $statistics = $this->cmsService->getSiteStatistics($site->id);

        return $this->success($statistics);
    }
}