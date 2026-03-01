<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * API Token Management Controller (for third-party integrations)
 */
class APITokenController extends Controller
{
    /**
     * List all API tokens for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = session('active_company_id');

        $tokens = PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'created_at' => $token->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * Create a new API token
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = $request->user();
        $companyId = session('active_company_id');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => __('No active company selected.'),
            ], 403);
        }

        $company = $user->companies()->find($companyId);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => __('You do not have access to this company.'),
            ], 403);
        }

        // Check if plan allows API access (optional - implement based on your subscription logic)
        // if (!$company->subscription?->plan?->features['api_access'] ?? false) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => __('API access requires a Pro or Enterprise plan.'),
        //     ], 403);
        // }

        // Create token with abilities (scopes)
        $abilities = $request->abilities ?? ['*'];
        $expiresAt = $request->expires_at ? \Carbon\Carbon::parse($request->expires_at) : null;

        $token = $user->createToken(
            name: $request->name,
            abilities: $abilities,
            expiresAt: $expiresAt
        );

        // Store company binding — CRITICAL for multi-tenancy in API context
        PersonalAccessToken::where('id', $token->accessToken->id)
            ->update(['company_id' => $companyId]);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token->plainTextToken, // shown ONCE
                'name' => $request->name,
                'abilities' => $abilities,
                'expires_at' => $expiresAt,
            ],
            'message' => __('API token created successfully. Make sure to copy it now as it will not be shown again.'),
        ], 201);
    }

    /**
     * Revoke an API token
     */
    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $user = $request->user();
        $companyId = session('active_company_id');

        $token = PersonalAccessToken::where('id', $tokenId)
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->where('company_id', $companyId)
            ->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => __('Token not found.'),
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => __('API token revoked successfully.'),
        ]);
    }
}
