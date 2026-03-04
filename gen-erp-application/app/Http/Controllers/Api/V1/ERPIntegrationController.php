<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\ERPIntegrationServiceInterface;
use App\Domain\CMS\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controller for ERP integration with CMS.
 */
#[OA\Tag(name: 'CMS - ERP Integration', description: 'Endpoints for integrating ERP data with CMS')]
class ERPIntegrationController extends BaseApiController
{
    public function __construct(
        private readonly ERPIntegrationServiceInterface $erpIntegrationService
    ) {}

    /**
     * Get products for product grid section.
     */
    #[OA\Get(
        path: '/api/v1/cms/erp/products',
        summary: 'Get products for CMS',
        description: 'Get products from inventory for use in CMS product grid sections',
        tags: ['CMS - ERP Integration'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: 'Number of products to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 12)
            ),
            new OA\Parameter(
                name: 'category_id',
                description: 'Filter by category ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'featured_only',
                description: 'Show only featured products',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'sort_by',
                description: 'Sort products by field',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['name', 'price', 'created_at'])
            ),
            new OA\Parameter(
                name: 'sort_order',
                description: 'Sort order',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'slug', type: 'string'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'selling_price', type: 'integer'),
                                new OA\Property(property: 'image', type: 'string'),
                                new OA\Property(property: 'is_featured', type: 'boolean')
                            ]
                        ))
                    ]
                )
            )
        ]
    )]
    public function products(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $options = [
            'limit' => $request->integer('limit', 12),
            'category_id' => $request->integer('category_id'),
            'featured_only' => $request->boolean('featured_only'),
            'sort_by' => $request->string('sort_by', 'name'),
            'sort_order' => $request->string('sort_order', 'asc'),
        ];

        $products = $this->erpIntegrationService->getProductsForGrid(
            auth()->user()->currentCompany->id,
            array_filter($options)
        );

        return $this->success($products, 'Products retrieved successfully');
    }

    /**
     * Get team members for team grid section.
     */
    #[OA\Get(
        path: '/api/v1/cms/erp/team',
        summary: 'Get team members for CMS',
        description: 'Get team members from HR for use in CMS team grid sections',
        tags: ['CMS - ERP Integration'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: 'Number of team members to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 12)
            ),
            new OA\Parameter(
                name: 'department_id',
                description: 'Filter by department ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'show_on_website',
                description: 'Show only members marked for website display',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', default: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Team members retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'first_name', type: 'string'),
                                new OA\Property(property: 'last_name', type: 'string'),
                                new OA\Property(property: 'position', type: 'string'),
                                new OA\Property(property: 'department', type: 'object'),
                                new OA\Property(property: 'photo', type: 'string'),
                                new OA\Property(property: 'bio', type: 'string')
                            ]
                        ))
                    ]
                )
            )
        ]
    )]
    public function team(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $options = [
            'limit' => $request->integer('limit', 12),
            'department_id' => $request->integer('department_id'),
            'show_on_website' => $request->boolean('show_on_website', true),
            'sort_by' => $request->string('sort_by', 'first_name'),
            'sort_order' => $request->string('sort_order', 'asc'),
        ];

        $teamMembers = $this->erpIntegrationService->getTeamMembersForGrid(
            auth()->user()->currentCompany->id,
            array_filter($options)
        );

        return $this->success($teamMembers, 'Team members retrieved successfully');
    }

    /**
     * Get projects for portfolio section.
     */
    #[OA\Get(
        path: '/api/v1/cms/erp/projects',
        summary: 'Get projects for CMS',
        description: 'Get completed projects for use in CMS portfolio sections',
        tags: ['CMS - ERP Integration'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: 'Number of projects to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 6)
            ),
            new OA\Parameter(
                name: 'category',
                description: 'Filter by project category',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'client_id',
                description: 'Filter by client ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Projects retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'featured_image', type: 'string'),
                                new OA\Property(property: 'completed_at', type: 'string'),
                                new OA\Property(property: 'budget', type: 'number')
                            ]
                        ))
                    ]
                )
            )
        ]
    )]
    public function projects(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $options = [
            'limit' => $request->integer('limit', 6),
            'category' => $request->string('category'),
            'client_id' => $request->integer('client_id'),
            'sort_by' => $request->string('sort_by', 'completed_at'),
            'sort_order' => $request->string('sort_order', 'desc'),
        ];

        $projects = $this->erpIntegrationService->getProjectsForPortfolio(
            auth()->user()->currentCompany->id,
            array_filter($options)
        );

        return $this->success($projects, 'Projects retrieved successfully');
    }

    /**
     * Get company statistics.
     */
    #[OA\Get(
        path: '/api/v1/cms/erp/stats',
        summary: 'Get company statistics',
        description: 'Get company statistics for use in CMS stats sections',
        tags: ['CMS - ERP Integration'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'products', type: 'integer'),
                        new OA\Property(property: 'employees', type: 'integer'),
                        new OA\Property(property: 'projects_completed', type: 'integer'),
                        new OA\Property(property: 'customers', type: 'integer'),
                        new OA\Property(property: 'years_in_business', type: 'integer')
                    ]
                )
            )
        ]
    )]
    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $stats = $this->erpIntegrationService->getCompanyStats(
            auth()->user()->currentCompany->id
        );

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Get testimonials.
     */
    #[OA\Get(
        path: '/api/v1/cms/erp/testimonials',
        summary: 'Get testimonials',
        description: 'Get testimonials from projects and reviews for CMS',
        tags: ['CMS - ERP Integration'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                description: 'Number of testimonials to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 6)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Testimonials retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'company', type: 'string'),
                                new OA\Property(property: 'content', type: 'string'),
                                new OA\Property(property: 'rating', type: 'integer'),
                                new OA\Property(property: 'date', type: 'string')
                            ]
                        ))
                    ]
                )
            )
        ]
    )]
    public function testimonials(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $options = [
            'limit' => $request->integer('limit', 6),
        ];

        $testimonials = $this->erpIntegrationService->getTestimonials(
            auth()->user()->currentCompany->id,
            $options
        );

        return $this->success($testimonials, 'Testimonials retrieved successfully');
    }

    /**
     * Search ERP data.
     */
    #[OA\Get(
        path: '/api/v1/cms/erp/search',
        summary: 'Search ERP data',
        description: 'Search across products, team members, and projects',
        tags: ['CMS - ERP Integration'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Search query',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'types',
                description: 'Types to search (comma-separated)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'products,team,projects')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'type', type: 'string'),
                                new OA\Property(property: 'title', type: 'string'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'url', type: 'string'),
                                new OA\Property(property: 'image', type: 'string')
                            ]
                        ))
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'types' => 'nullable|string',
        ]);

        $this->authorize('viewAny', Site::class);

        $query = $request->string('q');
        $types = $request->filled('types') 
            ? explode(',', $request->string('types')) 
            : [];

        $results = $this->erpIntegrationService->searchERPData(
            auth()->user()->currentCompany->id,
            $query,
            $types
        );

        return $this->success($results, 'Search results retrieved successfully');
    }

    /**
     * Get related products.
     */
    #[OA\Get(
        path: '/api/v1/cms/erp/products/{productId}/related',
        summary: 'Get related products',
        description: 'Get products related to a specific product',
        tags: ['CMS - ERP Integration'],
        parameters: [
            new OA\Parameter(
                name: 'productId',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Number of related products to return',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 4)
            ),
            new OA\Parameter(
                name: 'algorithm',
                description: 'Algorithm for finding related products',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['category', 'tags', 'price_range'], default: 'category')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Related products retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'slug', type: 'string'),
                                new OA\Property(property: 'selling_price', type: 'integer'),
                                new OA\Property(property: 'image', type: 'string')
                            ]
                        ))
                    ]
                )
            )
        ]
    )]
    public function relatedProducts(Request $request, int $productId): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $options = [
            'limit' => $request->integer('limit', 4),
            'algorithm' => $request->string('algorithm', 'category'),
        ];

        $relatedProducts = $this->erpIntegrationService->getRelatedProducts(
            $productId,
            auth()->user()->currentCompany->id,
            $options
        );

        return $this->success($relatedProducts, 'Related products retrieved successfully');
    }
}