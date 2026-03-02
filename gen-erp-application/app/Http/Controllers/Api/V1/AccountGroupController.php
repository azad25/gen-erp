<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AccountGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Account Groups",
 *     description="Account group management"
 * )
 * REST API v1 controller for Account Group CRUD operations.
 */
class AccountGroupController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/account-groups",
     *     summary="List all account groups",
     *     tags={"Account Groups"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/AccountGroup")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $groups = AccountGroup::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($groups);
    }

    /**
     * @OA\Get(
     *     path="/account-groups/{id}",
     *     summary="Get a specific account group",
     *     tags={"Account Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Account Group ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/AccountGroup")
     *         )
     *     )
     * )
     */
    public function show(AccountGroup $accountGroup): JsonResponse
    {
        $accountGroup->load(['accounts']);

        return $this->success($accountGroup);
    }

    /**
     * @OA\Post(
     *     path="/account-groups",
     *     summary="Create a new account group",
     *     tags={"Account Groups"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account group created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/AccountGroup"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:account_groups,code'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()->id;

        $group = AccountGroup::create($validated);

        return $this->success($group, __('Account group created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/account-groups/{id}",
     *     summary="Update an account group",
     *     tags={"Account Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Account Group ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account group updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/AccountGroup"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, AccountGroup $accountGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:account_groups,code,'.$accountGroup->id],
            'description' => ['nullable', 'string'],
        ]);

        $accountGroup->update($validated);

        return $this->success($accountGroup->fresh(), __('Account group updated'));
    }

    /**
     * @OA\Delete(
     *     path="/account-groups/{id}",
     *     summary="Delete an account group",
     *     tags={"Account Groups"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Account Group ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Account group deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(AccountGroup $accountGroup): JsonResponse
    {
        $accountGroup->delete();

        return $this->success(null, __('Account group deleted'));
    }
}