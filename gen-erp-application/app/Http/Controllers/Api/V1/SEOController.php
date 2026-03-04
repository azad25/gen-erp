<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\SEOServiceInterface;
use App\Domain\CMS\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin controller for SEO management and analysis.
 */
#[OA\Tag(name: 'CMS - SEO Management', description: 'Admin endpoints for SEO analysis and management')]
class SEOController extends BaseApiController
{
    public function __construct(
        private readonly SEOServiceInterface $seoService
    ) {}

    /**
     * Get SEO analysis for a site.
     */
    #[OA\Get(
        path: '/api/v1/cms/seo/analysis',
        summary: 'Get SEO analysis',
        description: 'Get comprehensive SEO analysis for a site',
        tags: ['CMS - SEO Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Site ID to analyze',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SEO analysis retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'score', type: 'integer', description: 'SEO score (0-100)'),
                        new OA\Property(property: 'grade', type: 'string', description: 'SEO grade (A-F)'),
                        new OA\Property(property: 'issues', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'type', type: 'string'),
                                new OA\Property(property: 'message', type: 'string')
                            ]
                        )),
                        new OA\Property(property: 'recommendations', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'stats', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function analysis(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id'
        ]);

        $site = Site::findOrFail($request->integer('site_id'));
        $this->authorize('view', $site);

        $analysis = $this->seoService->analyzeSEO($site);

        return $this->success($analysis, 'SEO analysis retrieved successfully');
    }

    /**
     * Get sitemap preview for a site.
     */
    #[OA\Get(
        path: '/api/v1/cms/seo/sitemap-preview',
        summary: 'Get sitemap preview',
        description: 'Get a preview of the sitemap for a site',
        tags: ['CMS - SEO Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Site ID',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sitemap preview retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'urls', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'url', type: 'string'),
                                new OA\Property(property: 'lastmod', type: 'string'),
                                new OA\Property(property: 'changefreq', type: 'string'),
                                new OA\Property(property: 'priority', type: 'string'),
                                new OA\Property(property: 'type', type: 'string')
                            ]
                        )),
                        new OA\Property(property: 'total_urls', type: 'integer')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function sitemapPreview(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id'
        ]);

        $site = Site::with(['pages', 'blogPosts'])->findOrFail($request->integer('site_id'));
        $this->authorize('view', $site);

        $urls = [];
        $baseUrl = $site->getUrl();

        // Add homepage
        $urls[] = [
            'url' => $baseUrl,
            'lastmod' => now()->format('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '1.0',
            'type' => 'homepage',
        ];

        // Add pages
        foreach ($site->pages()->where('is_published', true)->get() as $page) {
            $urls[] = [
                'url' => $baseUrl . '/' . $page->slug,
                'lastmod' => $page->updated_at->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'type' => 'page',
            ];
        }

        // Add blog posts
        foreach ($site->blogPosts()->where('is_published', true)->get() as $post) {
            $urls[] = [
                'url' => $baseUrl . '/blog/' . $post->slug,
                'lastmod' => $post->updated_at->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
                'type' => 'blog_post',
            ];
        }

        return $this->success([
            'urls' => $urls,
            'total_urls' => count($urls),
        ], 'Sitemap preview retrieved successfully');
    }

    /**
     * Get structured data preview for a page.
     */
    #[OA\Get(
        path: '/api/v1/cms/seo/structured-data-preview',
        summary: 'Get structured data preview',
        description: 'Get a preview of structured data for a page',
        tags: ['CMS - SEO Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Site ID',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page_id',
                description: 'Page ID (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'blog_post_id',
                description: 'Blog post ID (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Structured data preview retrieved successfully',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function structuredDataPreview(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id',
            'page_id' => 'nullable|integer|exists:cms_pages,id',
            'blog_post_id' => 'nullable|integer|exists:cms_blog_posts,id',
        ]);

        $site = Site::findOrFail($request->integer('site_id'));
        $this->authorize('view', $site);

        $page = null;
        $blogPost = null;

        if ($request->filled('page_id')) {
            $page = $site->pages()->findOrFail($request->integer('page_id'));
        }

        if ($request->filled('blog_post_id')) {
            $blogPost = $site->blogPosts()->findOrFail($request->integer('blog_post_id'));
        }

        $structuredData = $this->seoService->generateStructuredData($site, $page, $blogPost);

        return $this->success($structuredData, 'Structured data preview retrieved successfully');
    }

    /**
     * Get meta tags preview for a page.
     */
    #[OA\Get(
        path: '/api/v1/cms/seo/meta-tags-preview',
        summary: 'Get meta tags preview',
        description: 'Get a preview of meta tags for a page',
        tags: ['CMS - SEO Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Site ID',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'page_id',
                description: 'Page ID (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'blog_post_id',
                description: 'Blog post ID (optional)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Meta tags preview retrieved successfully',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function metaTagsPreview(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id',
            'page_id' => 'nullable|integer|exists:cms_pages,id',
            'blog_post_id' => 'nullable|integer|exists:cms_blog_posts,id',
        ]);

        $site = Site::findOrFail($request->integer('site_id'));
        $this->authorize('view', $site);

        $page = null;
        $blogPost = null;

        if ($request->filled('page_id')) {
            $page = $site->pages()->findOrFail($request->integer('page_id'));
        }

        if ($request->filled('blog_post_id')) {
            $blogPost = $site->blogPosts()->findOrFail($request->integer('blog_post_id'));
        }

        $metaTags = $this->seoService->generateMetaTags($site, $page, $blogPost);

        return $this->success($metaTags, 'Meta tags preview retrieved successfully');
    }

    /**
     * Get SEO dashboard data.
     */
    #[OA\Get(
        path: '/api/v1/cms/seo/dashboard',
        summary: 'Get SEO dashboard',
        description: 'Get comprehensive SEO dashboard data for all sites',
        tags: ['CMS - SEO Management'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SEO dashboard data retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'sites', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'url', type: 'string'),
                                new OA\Property(property: 'seo_score', type: 'integer'),
                                new OA\Property(property: 'seo_grade', type: 'string'),
                                new OA\Property(property: 'issues_count', type: 'integer'),
                                new OA\Property(property: 'last_analyzed', type: 'string')
                            ]
                        )),
                        new OA\Property(property: 'summary', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        // Get user's company sites
        $sites = Site::where('company_id', auth()->user()->currentCompany->id)
            ->where('is_published', true)
            ->get();

        $siteAnalyses = [];
        $totalScore = 0;
        $totalIssues = 0;

        foreach ($sites as $site) {
            $analysis = $this->seoService->analyzeSEO($site);
            
            $siteAnalyses[] = [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->getUrl(),
                'seo_score' => $analysis['score'],
                'seo_grade' => $analysis['grade'],
                'issues_count' => count($analysis['issues']),
                'last_analyzed' => now()->toISOString(),
            ];

            $totalScore += $analysis['score'];
            $totalIssues += count($analysis['issues']);
        }

        $averageScore = $sites->count() > 0 ? round($totalScore / $sites->count()) : 0;

        $summary = [
            'total_sites' => $sites->count(),
            'average_seo_score' => $averageScore,
            'total_issues' => $totalIssues,
            'sites_with_good_seo' => collect($siteAnalyses)->where('seo_score', '>=', 80)->count(),
            'sites_needing_attention' => collect($siteAnalyses)->where('seo_score', '<', 60)->count(),
        ];

        return $this->success([
            'sites' => $siteAnalyses,
            'summary' => $summary,
        ], 'SEO dashboard data retrieved successfully');
    }
}