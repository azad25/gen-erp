<?php

namespace App\Http\Middleware;

use App\Models\Company;
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

        // User has zero companies — redirect to setup wizard
        if (! $companyId && $user->companies()->count() === 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => __('No company found. Please create a company first.'),
                ], 403);
            }

            return redirect()->route('setup.company');
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

            return redirect()->route('setup.company');
        }

        // Verify the user is a member of this company
        $company = Company::where('id', $companyId)
            ->where('is_active', true)
            ->first();

        if (! $company) {
            return $this->forbiddenResponse($request, __('Company not found or inactive.'));
        }

        $isMember = $user->companies()
            ->where('companies.id', $company->id)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $isMember) {
            return $this->forbiddenResponse($request, __('You do not have access to this company.'));
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
        // API: read from X-Company-ID header
        if ($request->expectsJson() || $request->is('api/*')) {
            $headerId = $request->header('X-Company-ID');

            return $headerId ? (int) $headerId : null;
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
