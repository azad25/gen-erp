<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles switching the active company context.
 */
class CompanySwitchController extends Controller
{
    public function switch(Request $request, int $companyId): RedirectResponse
    {
        $user = $request->user();

        $company = Company::where('id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $company) {
            abort(404, __('Company not found.'));
        }

        // Verify user is a member
        $isMember = $user->companies()
            ->where('companies.id', $company->id)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $isMember) {
            abort(403, __('You do not have access to this company.'));
        }

        CompanyContext::setActive($company);
        $user->update(['last_active_company_id' => $company->id]);

        return redirect()->back();
    }

    /**
     * Switch active company (JSON API for SPA)
     */
    public function switchApi(Request $request, int $companyId): JsonResponse
    {
        $user = $request->user();

        $company = Company::where('id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => __('Company not found or inactive.'),
            ], 404);
        }

        // Verify user is a member
        $isMember = $user->companies()
            ->where('companies.id', $company->id)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => __('You do not have access to this company.'),
            ], 403);
        }

        // Set new active company in session
        session(['active_company_id' => $company->id]);
        
        // Update last active company
        $user->update(['last_active_company_id' => $company->id]);

        // Regenerate session to prevent session fixation
        $request->session()->regenerate(true); // true = keep session data

        return response()->json([
            'success' => true,
            'data' => [
                'company' => new CompanyResource($company),
                'permissions' => $user->getPermissionsForCompany($company->id),
                'subscription' => $company->activeSubscription?->plan?->slug,
            ],
            'message' => __('Company switched successfully.'),
        ]);
    }
}
