<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\DataTransferObjects\CompanySetupData;
use App\Domain\Auth\DataTransferObjects\UserRegistrationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompanySetupRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Domain\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Gen-ERP API",
 *     version="1.0.0",
 *     description="Gen-ERP REST API documentation",
 *     @OA\Contact(email="support@gen-erp.com")
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format: Bearer <token>"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and registration"
 * )
 * REST API v1 controller for Authentication operations.
 */
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="User login",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="remember", type="boolean", default=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=429, description="Too many attempts")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->ip(),
            $request->userAgent()
        );

        if (! $result['success']) {
            $status = 401;
            
            if (isset($result['retry_after'])) {
                $status = 429;
            } elseif (isset($result['requires_verification'])) {
                $status = 403;
            }

            return response()->json($result, $status);
        }

        if (isset($result['two_factor_required'])) {
            return response()->json($result);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
                'active_company' => $result['active_company'] ? new CompanyResource($result['active_company']) : null,
                'requires_company_selection' => $result['requires_company_selection'],
                'companies' => CompanyResource::collection($result['companies']),
            ],
            'message' => $result['message'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="User registration",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="company_name", type="string"),
     *             @OA\Property(property="business_type", type="string", enum={"retail","pharmacy","wholesale","manufacturing","rmg","restaurant","service","freelancer","ngo","ecommerce","school","government","other"})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $userData = UserRegistrationData::fromArray($request->validated());
        $result = $this->authService->register($userData);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
                'company' => $result['company'] ? new CompanyResource($result['company']) : null,
                'requires_company_setup' => $result['requires_company_setup'],
            ],
            'message' => $result['message'],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="User logout",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->authService->logout($request->user());

        return response()->json($result);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/user",
     *     summary="Get authenticated user",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="User data retrieved",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthenticated.'),
            ], 401);
        }

        $result = $this->authService->getUserData($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($result['user']),
                'company' => $result['company'] ? new CompanyResource($result['company']) : null,
                'permissions' => $result['permissions'],
                'subscription' => $result['subscription'],
                'companies' => CompanyResource::collection($result['companies']),
                'company_hierarchy' => $result['company_hierarchy'],
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/two-factor/challenge",
     *     summary="Submit two-factor authentication code",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="code", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="2FA verification successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Invalid code")
     * )
     */
    public function twoFactorChallenge(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid or expired token.'),
            ], 401);
        }

        $result = $this->authService->verifyTwoFactorCode($user, $request->code);

        if (! $result['success']) {
            return response()->json($result, 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_at' => $result['expires_at'],
                'active_company' => $result['active_company'] ? new CompanyResource($result['active_company']) : null,
                'requires_company_selection' => $result['requires_company_selection'],
                'companies' => CompanyResource::collection($result['companies']),
            ],
            'message' => $result['message'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/switch-company/{companyId}",
     *     summary="Switch active company",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Company switched successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Company not found")
     * )
     */
    public function switchCompany(Request $request, int $companyId): JsonResponse
    {
        $result = $this->authService->switchCompany($request->user(), $companyId);

        if (! $result['success']) {
            $status = $result['message'] === __('Company not found or access denied.') ? 404 : 403;

            return response()->json($result, $status);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'company' => new CompanyResource($result['company']),
                'permissions' => $result['permissions'],
            ],
            'message' => $result['message'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/setup-company",
     *     summary="Setup company for user",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="business_type", type="string", enum={"retail","pharmacy","wholesale","manufacturing","rmg","restaurant","service","freelancer","ngo","ecommerce","school","government","other"}),
     *             @OA\Property(property="country", type="string", default="BD"),
     *             @OA\Property(property="currency", type="string", default="BDT"),
     *             @OA\Property(property="timezone", type="string", default="Asia/Dhaka"),
     *             @OA\Property(property="address_line1", type="string"),
     *             @OA\Property(property="address_line2", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="district", type="string"),
     *             @OA\Property(property="postal_code", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="website", type="string"),
     *             @OA\Property(property="vat_bin", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Company setup successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function setupCompany(CompanySetupRequest $request): JsonResponse
    {
        $companyData = CompanySetupData::fromArray($request->validated());
        $result = $this->authService->setupCompany($request->user(), $companyData);

        return response()->json([
            'success' => true,
            'data' => [
                'company' => new CompanyResource($result['company']),
                'permissions' => $result['permissions'],
            ],
            'message' => $result['message'],
        ], 201);
    }
}
