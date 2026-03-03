<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Contracts\AccountingServiceInterface;
use App\Domain\Accounting\DTOs\CreateAccountData;
use App\Domain\Accounting\DTOs\UpdateAccountData;
use App\Domain\Accounting\Models\Account;
use App\Http\Resources\AccountResource;
use App\Support\Enums\AccountType;
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
    public function __construct(
        private AccountingServiceInterface $accountingService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/accounts",
     *     summary="List all accounts",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="account_type", in="query", description="Account type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="account_group_id", in="query", description="Account Group ID", @OA\Schema(type="integer")),
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
        $accounts = $this->accountingService
            ->getAccounts(
                activeCompany(),
                $request->get('search'),
                $request->get('account_type'),
                $request->get('account_group_id')
            )
            ->paginate($request->integer('per_page', 15));

        return $this->paginated(AccountResource::collection($accounts));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/accounts/{id}",
     *     summary="Get a specific account",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Account ID", @OA\Schema(type="integer")),
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
    public function show(Account $account): JsonResponse
    {
        $account->load(['accountGroup']);

        return $this->success(new AccountResource($account));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/accounts",
     *     summary="Create a new account",
     *     tags={"Accounts"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="account_group_id", type="integer"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="account_type", type="string"),
     *             @OA\Property(property="opening_balance", type="integer"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Account created",
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
            'account_group_id' => ['required', Rule::exists('account_groups', 'id')->where('company_id', $companyId)],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'string'],
            'opening_balance' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $accountData = new CreateAccountData(
            company_id: $companyId,
            account_group_id: $validated['account_group_id'],
            code: $validated['code'],
            name: $validated['name'],
            account_type: AccountType::from($validated['account_type']),
            opening_balance: $validated['opening_balance'] ?? null,
            description: $validated['description'] ?? null,
            is_active: $validated['is_active'] ?? true,
        );

        $account = $this->accountingService->createAccount($accountData);

        return $this->success(new AccountResource($account->load(['accountGroup'])), __('Account created'), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/accounts/{id}",
     *     summary="Update an account",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Account ID", @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="account_type", type="string"),
     *             @OA\Property(property="opening_balance", type="integer"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Account updated",
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
    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'account_type' => ['sometimes', 'string'],
            'opening_balance' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $updateData = new UpdateAccountData(
            code: $validated['code'] ?? null,
            name: $validated['name'] ?? null,
            account_type: isset($validated['account_type']) ? AccountType::from($validated['account_type']) : null,
            opening_balance: $validated['opening_balance'] ?? null,
            description: $validated['description'] ?? null,
            is_active: $validated['is_active'] ?? null,
        );

        $account = $this->accountingService->updateAccount($account, $updateData);

        return $this->success(new AccountResource($account->load(['accountGroup'])), __('Account updated'));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/accounts/{id}",
     *     summary="Delete an account",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Account ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Account $account): JsonResponse
    {
        $this->accountingService->deleteAccount($account);

        return $this->success(null, __('Account deleted'));
    }
}
