<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\Contracts\CompanyServiceInterface;
use App\Http\Resources\BranchResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Branches",
 *     description="Branch management"
 * )
 * REST API v1 controller for Branch CRUD operations.
 */
class BranchController extends BaseApiController
{
    public function __construct(
        private readonly CompanyServiceInterface $companyService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/branches",
     *     summary="List all branches",
     *     tags={"Branches"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $branches = $this->companyService->getBranches(
            activeCompany()->id,
            $request->get('search'),
            $request->boolean('is_active'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($branches, BranchResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/branches/{id}",
     *     summary="Get a specific branch",
     *     tags={"Branches"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $branch = $this->companyService->getBranch(activeCompany()->id, $id);

        return $this->success(new BranchResource($branch));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/branches",
     *     summary="Create a new branch",
     *     tags={"Branches"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Branch created",
     *
     *         @OA\JsonContent(
     *
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
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch = $this->companyService->createBranch(activeCompany()->id, $validated);

        return $this->success(new BranchResource($branch), __('Branch created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/branches/{id}",
     *     summary="Update a branch",
     *     tags={"Branches"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Branch updated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $branch = $this->companyService->getBranch(activeCompany()->id, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updatedBranch = $this->companyService->updateBranch($branch, $validated);

        return $this->success(new BranchResource($updatedBranch), __('Branch updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/branches/{id}",
     *     summary="Delete a branch",
     *     tags={"Branches"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Branch deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $branch = $this->companyService->getBranch(activeCompany()->id, $id);
        $this->companyService->deleteBranch($branch);

        return $this->success(null, __('Branch deleted'));
    }
}
