<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\TaxGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Tax Groups",
 *     description="Tax group management"
 * )
 * REST API v1 controller for Tax Group CRUD operations.
 */
class TaxGroupController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/tax-groups",
     *     summary="List all tax groups",
     *     tags={"Tax Groups"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/TaxGroup")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $taxGroups = TaxGroup::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($taxGroups);
    }

    /**
     * @OA\Get(
     *     path="/tax-groups/{id}",
     *     summary="Get a specific tax group",
     *     tags={"Tax Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Tax Group ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/TaxGroup")
     *         )
     *     )
     * )
     */
    public function show(TaxGroup $taxGroup): JsonResponse
    {
        return $this->success($taxGroup);
    }

    /**
     * @OA\Post(
     *     path="/tax-groups",
     *     summary="Create a new tax group",
     *     tags={"Tax Groups"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="tax_rate", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tax group created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/TaxGroup"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;

        $taxGroup = TaxGroup::create($validated);

        return $this->success($taxGroup, 'Tax group created', 201);
    }

    /**
     * @OA\Put(
     *     path="/tax-groups/{id}",
     *     summary="Update a tax group",
     *     tags={"Tax Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Tax Group ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="tax_rate", type="number"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tax group updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/TaxGroup"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, TaxGroup $taxGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $taxGroup->update($validated);

        return $this->success($taxGroup->fresh(), 'Tax group updated');
    }

    /**
     * @OA\Delete(
     *     path="/tax-groups/{id}",
     *     summary="Delete a tax group",
     *     tags={"Tax Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Tax Group ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Tax group deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(TaxGroup $taxGroup): JsonResponse
    {
        $taxGroup->delete();

        return $this->success(null, 'Tax group deleted');
    }
}
