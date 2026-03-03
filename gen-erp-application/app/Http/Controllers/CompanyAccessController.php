<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyAccessController extends Controller
{
    /**
     * Fix company access issues by setting the correct active company.
     */
    public function fixAccess(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        \Log::info('[CompanyAccessController] Fixing access for user', [
            'user_id' => $user->id,
            'current_session_company' => session('active_company_id'),
            'user_last_active' => $user->last_active_company_id,
        ]);

        // Clear any problematic session data
        session()->forget('active_company_id');

        // Get user's companies with proper relationships
        $companies = $user->companies()
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->get();

        \Log::info('[CompanyAccessController] Found companies', [
            'user_id' => $user->id,
            'companies_count' => $companies->count(),
            'company_ids' => $companies->pluck('id')->toArray(),
        ]);

        if ($companies->isEmpty()) {
            \Log::warning('[CompanyAccessController] No active companies found, redirecting to setup', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('company.setup');
        }

        // Use the first available company or the last active one
        $activeCompany = null;

        if ($user->last_active_company_id) {
            $activeCompany = $companies->where('id', $user->last_active_company_id)->first();
        }

        if (! $activeCompany) {
            $activeCompany = $companies->first();
        }

        // Force set the active company in session
        session(['active_company_id' => $activeCompany->id]);

        // Update user's last active company
        $user->update(['last_active_company_id' => $activeCompany->id]);

        \Log::info('[CompanyAccessController] Fixed company access', [
            'user_id' => $user->id,
            'company_id' => $activeCompany->id,
            'company_name' => $activeCompany->name,
        ]);

        return redirect()->route('dashboard')->with('success', "Company access restored. Active company: {$activeCompany->name}");
    }

    /**
     * Show company selection page for users with multiple companies.
     */
    public function selectCompany(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $companies = $user->companies()
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->get();

        if ($companies->isEmpty()) {
            return redirect()->route('company.setup');
        }

        if ($companies->count() === 1) {
            $company = $companies->first();
            session(['active_company_id' => $company->id]);
            $user->update(['last_active_company_id' => $company->id]);

            return redirect()->route('dashboard');
        }

        return inertia('Auth/SelectCompany', [
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'business_type' => $company->business_type,
                    'is_current' => $company->id === session('active_company_id'),
                ];
            }),
        ]);
    }

    /**
     * Switch to a specific company.
     */
    public function switchCompany(Request $request, Company $company): RedirectResponse
    {
        $user = $request->user();

        // Verify user has access to this company
        $hasAccess = $user->companies()
            ->where('companies.id', $company->id)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $hasAccess) {
            return redirect()->back()->withErrors(['company' => 'You do not have access to this company.']);
        }

        // Set the active company
        session(['active_company_id' => $company->id]);
        $user->update(['last_active_company_id' => $company->id]);

        return redirect()->route('dashboard')->with('success', "Switched to {$company->name}");
    }
}
