<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\CMS\Contracts\PublicSiteServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Public controller for rendering CMS sites.
 */
#[OA\Tag(name: 'Public - Site Rendering', description: 'Public endpoints for rendering CMS sites')]
class SiteController extends Controller
{
    public function __construct(
        private readonly PublicSiteServiceInterface $publicSiteService
    ) {}

    /**
     * Get site data including menus and settings.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/site',
        summary: 'Get site data',
        description: 'Get public site data including menus and settings',
        tags: ['Public - Site Rendering'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Site data retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'site',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'subdomain', type: 'string'),
                                new OA\Property(property: 'custom_domain', type: 'string'),
                                new OA\Property(property: 'theme', type: 'string'),
                                new OA\Property(property: 'logo_url', type: 'string'),
                                new OA\Property(property: 'favicon_url', type: 'string'),
                                new OA\Property(property: 'meta_title', type: 'string'),
                                new OA\Property(property: 'meta_description', type: 'string'),
                                new OA\Property(property: 'settings', type: 'object')
                            ]
                        ),
                        new OA\Property(property: 'menus', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            )
        ]
    )]
    public function show(string $tenant): JsonResponse
    {
        $siteData = $this->publicSiteService->getSiteData($tenant);

        if (!$siteData) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        return response()->json($siteData);
    }

    /**
     * Get homepage.
     */
    #[OA\Get(
        path: '/api/public/{tenant}',
        summary: 'Get homepage',
        description: 'Get the homepage for a site',
        tags: ['Public - Site Rendering'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Homepage retrieved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Site or homepage not found'
            )
        ]
    )]
    public function homepage(string $tenant): JsonResponse
    {
        $pageData = $this->publicSiteService->getHomepage($tenant);

        if (!$pageData) {
            return response()->json(['message' => 'Homepage not found'], 404);
        }

        return response()->json($pageData);
    }

    /**
     * Get page by slug.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/pages/{slug}',
        summary: 'Get page by slug',
        description: 'Get a specific page by its slug',
        tags: ['Public - Site Rendering'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'slug',
                description: 'Page slug',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Page retrieved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Page not found'
            )
        ]
    )]
    public function page(string $tenant, string $slug): JsonResponse
    {
        $pageData = $this->publicSiteService->getPageBySlug($tenant, $slug);

        if (!$pageData) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        return response()->json($pageData);
    }

    /**
     * Get all pages.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/pages',
        summary: 'Get all pages',
        description: 'Get all published pages for a site',
        tags: ['Public - Site Rendering'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pages retrieved successfully'
            )
        ]
    )]
    public function pages(string $tenant): JsonResponse
    {
        $pages = $this->publicSiteService->getSitePages($tenant);

        return response()->json(['pages' => $pages]);
    }

    /**
     * Get blog posts.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/blog',
        summary: 'Get blog posts',
        description: 'Get paginated blog posts for a site',
        tags: ['Public - Site Rendering'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'category_id',
                description: 'Filter by category ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Blog posts retrieved successfully'
            )
        ]
    )]
    public function blog(Request $request, string $tenant): JsonResponse
    {
        $posts = $this->publicSiteService->getBlogPosts(
            $tenant,
            $request->integer('category_id'),
            $request->integer('per_page', 10)
        );

        return response()->json($posts);
    }

    /**
     * Get single blog post.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/blog/{slug}',
        summary: 'Get blog post',
        description: 'Get a single blog post by slug',
        tags: ['Public - Site Rendering'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'slug',
                description: 'Blog post slug',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Blog post retrieved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Blog post not found'
            )
        ]
    )]
    public function blogPost(string $tenant, string $slug): JsonResponse
    {
        $post = $this->publicSiteService->getBlogPost($tenant, $slug);

        if (!$post) {
            return response()->json(['message' => 'Blog post not found'], 404);
        }

        return response()->json($post);
    }

    /**
     * Search site content.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/search',
        summary: 'Search site content',
        description: 'Search pages and blog posts',
        tags: ['Public - Site Rendering'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Results per type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results retrieved successfully'
            )
        ]
    )]
    public function search(Request $request, string $tenant): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $results = $this->publicSiteService->search(
            $tenant,
            $request->string('q'),
            $request->integer('per_page', 10)
        );

        return response()->json($results);
    }
}