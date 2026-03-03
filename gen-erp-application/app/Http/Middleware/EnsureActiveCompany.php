<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Models\Company;
use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and sets the active company from session (web) or X-Company-ID header (API).
 */
class EnsureActiveCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip for unauthenticated requests (auth middleware should catch first)
        if (! $user) {
            return $next($request);
        }

        // Resolve company ID from the appropriate source
        $companyId = $this->resolveCompanyId($request);

        \Log::info('[EnsureActiveCompany] Middleware executed', [
            'user_id' => $user->id,
            'resolved_company_id' => $companyId,
            'session_company_id' => session('active_company_id'),
            'last_active_company_id' => $user->last_active_company_id,
            'companies_count' => $user->companies()->count(),
            'url' => $request->url(),
            'session_id' => session()->getId(),
        ]);

        // User has zero companies — redirect to setup wizard
        if (! $companyId && $user->companies()->count() === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('No company found. Please create a company first.'),
                ], 403);
            }

            return redirect()->route('company.setup');
        }

        // Auto-select a company if none resolved
        if (! $companyId) {
            $companyId = $user->last_active_company_id
                ?? $user->companies()->first()?->id;
        }

        if (! $companyId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('Unable to resolve an active company.'),
                ], 403);
            }

            return redirect()->route('company.setup');
        }

        // AGGRESSIVE FIX: Get any active company the user has access to
        $company = $user->companies()
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->where('companies.id', $companyId)
            ->first();

        // If the specific company is not found, get ANY active company for this user
        if (! $company) {
            \Log::warning('[EnsureActiveCompany] Specific company not found, getting any active company', [
                'user_id' => $user->id,
                'requested_company_id' => $companyId,
            ]);

            $company = $user->companies()
                ->where('companies.is_active', true)
                ->wherePivot('is_active', true)
                ->first();

            if ($company) {
                // Update session and user to use this company
                session(['active_company_id' => $company->id]);
                $user->update(['last_active_company_id' => $company->id]);

                \Log::info('[EnsureActiveCompany] Auto-switched to available company', [
                    'user_id' => $user->id,
                    'new_company_id' => $company->id,
                    'company_name' => $company->name,
                ]);
            }
        }

        if (! $company) {
            \Log::error('[EnsureActiveCompany] No active companies found for user', [
                'user_id' => $user->id,
                'total_companies' => $user->companies()->count(),
            ]);

            // Instead of showing error, redirect to fix access
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('Company not found or inactive.'),
                    'redirect_url' => route('company.fix-access'),
                ], 403);
            }

            return redirect()->route('company.fix-access');
        }

        // Set the active company context
        CompanyContext::setActive($company);

        // Update last active company on the user
        if ($user->last_active_company_id !== $company->id) {
            $user->update(['last_active_company_id' => $company->id]);
        }

        return $next($request);
    }

    /**
     * Resolve the company ID from the request source.
     */
    private function resolveCompanyId(Request $request): ?int
    {
        // API: For token-based auth, get company from token
        if ($request->bearerToken()) {
            $token = $request->user()?->currentAccessToken();

            if ($token && $token->company_id) {
                return $token->company_id;
            }

            // Fallback to X-Company-ID header for API requests
            $headerId = $request->header('X-Company-ID');

            return $headerId ? (int) $headerId : null;
        }

        // API: read from X-Company-ID header (for cookie-based SPA API calls)
        if ($request->expectsJson() || $request->is('api/*')) {
            $headerId = $request->header('X-Company-ID');

            // If no header, try session (for SPA using cookie auth)
            return $headerId ? (int) $headerId : session('active_company_id');
        }

        // Web: read from session
        return session('active_company_id');
    }

    /**
     * Return a 403 forbidden response.
     */
    private function forbiddenResponse(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }
}
