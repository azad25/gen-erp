<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
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
    /**
     * @OA\Get(
     *     path="/branches",
     *     summary="List all branches",
     *     tags={"Branches"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", description="Active status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Branch")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $branches = Branch::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('is_active'), fn ($q, $s) => $q->where('is_active', $s))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($branches);
    }

    /**
     * @OA\Get(
     *     path="/branches/{id}",
     *     summary="Get a specific branch",
     *     tags={"Branches"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Branch")
     *         )
     *     )
     * )
     */
    public function show(Branch $branch): JsonResponse
    {
        return $this->success($branch);
    }

    /**
     * @OA\Post(
     *     path="/branches",
     *     summary="Create a new branch",
     *     tags={"Branches"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Branch created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Branch"),
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

        $validated['company_id'] = activeCompany()->id;

        $branch = Branch::create($validated);

        return $this->success($branch, __('Branch created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/branches/{id}",
     *     summary="Update a branch",
     *     tags={"Branches"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Branch updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Branch"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Branch $branch): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch->update($validated);

        return $this->success($branch->fresh(), __('Branch updated'));
    }

    /**
     * @OA\Delete(
     *     path="/branches/{id}",
     *     summary="Delete a branch",
     *     tags={"Branches"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Branch ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Branch deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $branch->delete();

        return $this->success(null, __('Branch deleted'));
    }
}
