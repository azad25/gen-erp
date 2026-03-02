<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Account;
use App\Models\AccountGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Accounts",
 *     description="Chart of Accounts management"
 * )
 * REST API v1 controller for Chart of Accounts operations.
 */
class AccountController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/accounts",
     *     summary="List all accounts",
     *     tags={"Accounts"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="account_type", in="query", description="Account type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="account_group_id", in="query", description="Account Group ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Account")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $accounts = Account::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($request->get('account_type'), fn ($q, $s) => $q->where('account_type', $s))
            ->when($request->get('account_group_id'), fn ($q, $id) => $q->where('account_group_id', $id))
            ->with(['group'])
            ->orderBy('code')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($accounts);
    }

    /**
     * @OA\Get(
     *     path="/accounts/{id}",
     *     summary="Get a specific account",
     *     tags={"Accounts"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Account ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     )
     * )
     */
    public function show(Account $account): JsonResponse
    {
        $account->load(['group']);

        return $this->success($account);
    }

    /**
     * @OA\Post(
     *     path="/accounts",
     *     summary="Create a new account",
     *     tags={"Accounts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="account_group_id", type="integer"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="account_type", type="string"),
     *             @OA\Property(property="opening_balance", type="integer"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Account created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'account_group_id' => ['required', Rule::exists('account_groups', 'id')->where('company_id', $companyId)],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'string'],
            'opening_balance' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['company_id'] = $companyId;

        $account = Account::create($validated);

        return $this->success($account->load(['group']), __('Account created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/accounts/{id}",
     *     summary="Update an account",
     *     tags={"Accounts"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Account ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="account_type", type="string"),
     *             @OA\Property(property="opening_balance", type="integer"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'account_type' => ['sometimes', 'string'],
            'opening_balance' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $account->update($validated);

        return $this->success($account->fresh()->load(['group']), __('Account updated'));
    }

    /**
     * @OA\Delete(
     *     path="/accounts/{id}",
     *     summary="Delete an account",
     *     tags={"Accounts"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Account ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Account $account): JsonResponse
    {
        $account->delete();

        return $this->success(null, __('Account deleted'));
    }
}
