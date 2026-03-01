<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User management"
 * )
 * REST API v1 controller for User management.
 */
class UserController extends BaseApiController
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * @OA\Get(
     *     path="/users",
     *     summary="List all users",
     *     tags={"Users"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/User")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->get('search'), fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%")->orWhere('email', 'LIKE', "%{$s}%"))
            ->with(['companies'])
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($users);
    }

    /**
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Get a specific user",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['companies', 'lastActiveCompany']);

        return $this->success($user);
    }

    /**
     * @OA\Post(
     *     path="/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="avatar_url", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'url'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'avatar_url' => $validated['avatar_url'] ?? null,
        ]);

        return $this->success($user->load(['companies']), 'User created', 201);
    }

    /**
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Update a user",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="avatar_url", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'url'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $this->success($user->fresh()->load(['companies']), 'User updated');
    }

    /**
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete a user",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->success(null, 'User deleted');
    }

    /**
     * @OA\Post(
     *     path="/users/{user}/add-to-company",
     *     summary="Add user to company",
     *     tags={"Users"},
     *     @OA\Parameter(name="user", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="company_id", type="integer"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="is_owner", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User added to company",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function addToCompany(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'role' => ['required', 'string'],
            'is_owner' => ['sometimes', 'boolean'],
        ]);

        $this->userService->addToCompany($user, $validated['company_id'], $validated['role'], $validated['is_owner'] ?? false);

        return $this->success($user->fresh()->load(['companies']), 'User added to company');
    }

    /**
     * @OA\Post(
     *     path="/users/{user}/remove-from-company",
     *     summary="Remove user from company",
     *     tags={"Users"},
     *     @OA\Parameter(name="user", in="path", required=true, description="User ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="company_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User removed from company",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function removeFromCompany(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        $this->userService->removeFromCompany($user, $validated['company_id']);

        return $this->success($user->fresh()->load(['companies']), 'User removed from company');
    }
}
