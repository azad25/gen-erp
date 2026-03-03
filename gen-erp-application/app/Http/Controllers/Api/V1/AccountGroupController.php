<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Contracts\AccountingServiceInterface;
use App\Http\Resources\AccountGroupResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Account Groups",
 *     description="Account group management"
 * )
 * REST API v1 controller for Account Group CRUD operations.
 */
class AccountGroupController extends BaseApiController
{
    public function __construct(
        private readonly AccountingServiceInterface $accountingService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/account-groups",
     *     summary="List all account groups",
     *     tags={"Account Groups"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
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
        $groups = $this->accountingService->getAccountGroups(
            activeCompany()->id,
            $request->get('search'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($groups, AccountGroupResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/account-groups/{id}",
     *     summary="Get a specific account group",
     *     tags={"Account Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Account Group ID", @OA\Schema(type="integer")),
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
        $accountGroup = $this->accountingService->getAccountGroup(activeCompany()->id, $id);
        $accountGroup->load(['accounts']);

        return $this->success(new AccountGroupResource($accountGroup));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/account-groups",
     *     summary="Create a new account group",
     *     tags={"Account Groups"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Account group created",
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
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('account_groups', 'code')->where('company_id', $companyId)],
            'description' => ['nullable', 'string'],
        ]);

        $group = $this->accountingService->createAccountGroup($companyId, $validated);

        return $this->success(new AccountGroupResource($group), __('Account group created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/account-groups/{id}",
     *     summary="Update an account group",
     *     tags={"Account Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Account Group ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Account group updated",
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
        $companyId = activeCompany()->id;
        $accountGroup = $this->accountingService->getAccountGroup($companyId, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('account_groups', 'code')->where('company_id', $companyId)->ignore($accountGroup->id)],
            'description' => ['nullable', 'string'],
        ]);

        $updatedGroup = $this->accountingService->updateAccountGroup($accountGroup, $validated);

        return $this->success(new AccountGroupResource($updatedGroup), __('Account group updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/account-groups/{id}",
     *     summary="Delete an account group",
     *     tags={"Account Groups"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Account Group ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Account group deleted",
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
        $accountGroup = $this->accountingService->getAccountGroup(activeCompany()->id, $id);
        $this->accountingService->deleteAccountGroup($accountGroup);

        return $this->success(null, __('Account group deleted'));
    }
}
