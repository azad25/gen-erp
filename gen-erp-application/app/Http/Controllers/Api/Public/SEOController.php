<?php

namespace App\Http\Controllers\Api\Public;

use App\Domain\CMS\Contracts\PublicSiteServiceInterface;
use App\Domain\CMS\Contracts\SEOServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Public controller for SEO-related endpoints.
 */
#[OA\Tag(name: 'Public - SEO', description: 'Public SEO endpoints for sitemap, robots.txt, etc.')]
class SEOController extends Controller
{
    public function __construct(
        private readonly PublicSiteServiceInterface $publicSiteService,
        private readonly SEOServiceInterface $seoService
    ) {}

    /**
     * Generate sitemap.xml for a site.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/sitemap.xml',
        summary: 'Get sitemap XML',
        description: 'Generate sitemap.xml for a site',
        tags: ['Public - SEO'],
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
                description: 'Sitemap XML generated successfully',
                content: new OA\MediaType(
                    mediaType: 'application/xml',
                    schema: new OA\Schema(type: 'string')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            )
        ]
    )]
    public function sitemap(string $tenant): Response
    {
        $site = $this->publicSiteService->findSiteByTenant($tenant);

        if (!$site) {
            return response('Site not found', 404);
        }

        $sitemap = $this->seoService->generateSitemap($site);

        return response($sitemap, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600', // Cache for 1 hour
        ]);
    }

    /**
     * Generate robots.txt for a site.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/robots.txt',
        summary: 'Get robots.txt',
        description: 'Generate robots.txt for a site',
        tags: ['Public - SEO'],
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
                description: 'Robots.txt generated successfully',
                content: new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            )
        ]
    )]
    public function robots(string $tenant): Response
    {
        $site = $this->publicSiteService->findSiteByTenant($tenant);

        if (!$site) {
            return response('Site not found', 404);
        }

        $robotsTxt = $this->seoService->generateRobotsTxt($site);

        return response($robotsTxt, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'public, max-age=86400', // Cache for 24 hours
        ]);
    }

    /**
     * Get structured data for a page.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/structured-data',
        summary: 'Get structured data',
        description: 'Get JSON-LD structured data for a page or site',
        tags: ['Public - SEO'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page_slug',
                description: 'Page slug (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'blog_slug',
                description: 'Blog post slug (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Structured data retrieved successfully',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'object')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            )
        ]
    )]
    public function structuredData(string $tenant): Response
    {
        $site = $this->publicSiteService->findSiteByTenant($tenant);

        if (!$site) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        $page = null;
        $blogPost = null;

        // Get page if specified
        if (request('page_slug')) {
            $page = $site->pages()
                ->where('slug', request('page_slug'))
                ->where('is_published', true)
                ->first();
        }

        // Get blog post if specified
        if (request('blog_slug')) {
            $blogPost = $site->blogPosts()
                ->where('slug', request('blog_slug'))
                ->where('is_published', true)
                ->first();
        }

        $structuredData = $this->seoService->generateStructuredData($site, $page, $blogPost);

        return response()->json($structuredData, 200, [
            'Cache-Control' => 'public, max-age=3600', // Cache for 1 hour
        ]);
    }

    /**
     * Get meta tags for a page.
     */
    #[OA\Get(
        path: '/api/public/{tenant}/meta-tags',
        summary: 'Get meta tags',
        description: 'Get meta tags for a page or site',
        tags: ['Public - SEO'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                description: 'Site subdomain or custom domain',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page_slug',
                description: 'Page slug (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'blog_slug',
                description: 'Blog post slug (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Meta tags retrieved successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 404,
                description: 'Site not found'
            )
        ]
    )]
    public function metaTags(string $tenant): Response
    {
        $site = $this->publicSiteService->findSiteByTenant($tenant);

        if (!$site) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        $page = null;
        $blogPost = null;

        // Get page if specified
        if (request('page_slug')) {
            $page = $site->pages()
                ->where('slug', request('page_slug'))
                ->where('is_published', true)
                ->first();
        }

        // Get blog post if specified
        if (request('blog_slug')) {
            $blogPost = $site->blogPosts()
                ->where('slug', request('blog_slug'))
                ->where('is_published', true)
                ->first();
        }

        $metaTags = $this->seoService->generateMetaTags($site, $page, $blogPost);

        return response()->json($metaTags, 200, [
            'Cache-Control' => 'public, max-age=3600', // Cache for 1 hour
        ]);
    }
}