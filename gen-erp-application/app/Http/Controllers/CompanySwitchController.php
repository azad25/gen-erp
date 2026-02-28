<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyContext;
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
}
