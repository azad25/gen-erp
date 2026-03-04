<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\CMS\Contracts\WishlistServiceInterface;
use App\Domain\CMS\Models\Wishlist;
use App\Http\Resources\WishlistResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin controller for managing wishlist data and analytics.
 */
#[OA\Tag(name: 'CMS - Wishlist Management', description: 'Admin endpoints for managing customer wishlists')]
class WishlistController extends BaseApiController
{
    public function __construct(
        private readonly WishlistServiceInterface $wishlistService
    ) {}

    /**
     * Get wishlist items with filtering and pagination.
     */
    #[OA\Get(
        path: '/api/v1/cms/wishlists',
        summary: 'List wishlist items',
        description: 'Get paginated list of wishlist items with filtering options',
        tags: ['CMS - Wishlist Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Filter by site ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'customer_id',
                description: 'Filter by customer ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'product_id',
                description: 'Filter by product ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Items per page (max 100)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
            ),
            new OA\Parameter(
                name: 'sort',
                description: 'Sort field',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['created_at', 'updated_at'], default: 'created_at')
            ),
            new OA\Parameter(
                name: 'direction',
                description: 'Sort direction',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wishlist items retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WishlistResource')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Wishlist::class);

        $query = Wishlist::with(['customer']);

        // Apply filters
        if ($request->filled('site_id')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('site_id', $request->integer('site_id'));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        // Apply sorting
        $sortField = $request->string('sort', 'created_at');
        $sortDirection = $request->string('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate
        $perPage = min($request->integer('per_page', 15), 100);
        $wishlists = $query->paginate($perPage);

        return $this->success(
            WishlistResource::collection($wishlists)->response()->getData(),
            'Wishlist items retrieved successfully'
        );
    }

    /**
     * Get wishlist statistics for a site.
     */
    #[OA\Get(
        path: '/api/v1/cms/wishlists/statistics',
        summary: 'Get wishlist statistics',
        description: 'Get comprehensive wishlist analytics for a site',
        tags: ['CMS - Wishlist Management'],
        parameters: [
            new OA\Parameter(
                name: 'site_id',
                description: 'Site ID to get statistics for',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wishlist statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_wishlist_items', type: 'integer', description: 'Total number of wishlist items'),
                        new OA\Property(property: 'customers_with_wishlists', type: 'integer', description: 'Number of customers with wishlists'),
                        new OA\Property(property: 'average_items_per_customer', type: 'number', description: 'Average items per customer'),
                        new OA\Property(property: 'recent_wishlist_items', type: 'integer', description: 'Items added in last 30 days'),
                        new OA\Property(
                            property: 'most_wishlisted_products',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'product_id', type: 'integer'),
                                    new OA\Property(property: 'wishlist_count', type: 'integer')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'site_id' => 'required|integer|exists:cms_sites,id'
        ]);

        $this->authorize('viewAny', Wishlist::class);

        $statistics = $this->wishlistService->getWishlistStatistics(
            $request->integer('site_id')
        );

        return $this->success($statistics, 'Wishlist statistics retrieved successfully');
    }

    /**
     * Delete a wishlist item.
     */
    #[OA\Delete(
        path: '/api/v1/cms/wishlists/{id}',
        summary: 'Delete wishlist item',
        description: 'Remove a wishlist item (admin action)',
        tags: ['CMS - Wishlist Management'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Wishlist item ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wishlist item deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Wishlist item deleted successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Wishlist item not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')
            )
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $wishlistItem = Wishlist::findOrFail($id);
        
        $this->authorize('delete', $wishlistItem);

        $wishlistItem->delete();

        return $this->success(null, 'Wishlist item deleted successfully');
    }

    /**
     * Clear all wishlist items for a customer.
     */
    #[OA\Delete(
        path: '/api/v1/cms/wishlists/customers/{customerId}/clear',
        summary: 'Clear customer wishlist',
        description: 'Remove all wishlist items for a specific customer (admin action)',
        tags: ['CMS - Wishlist Management'],
        parameters: [
            new OA\Parameter(
                name: 'customerId',
                description: 'Customer ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer wishlist cleared successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Customer wishlist cleared successfully')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Customer not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')
            )
        ]
    )]
    public function clearCustomerWishlist(Request $request, int $customerId): JsonResponse
    {
        $this->authorize('viewAny', Wishlist::class);

        $cleared = $this->wishlistService->clearWishlist($customerId);

        if (!$cleared) {
            return $this->error('No wishlist items found for this customer', 404);
        }

        return $this->success(null, 'Customer wishlist cleared successfully');
    }
}