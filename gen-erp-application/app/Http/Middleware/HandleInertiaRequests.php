<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user'        => $request->user() ? array_merge(
                    $request->user()->only('id','name','email'),
                    ['companies' => $request->user()->companies()->get(['companies.id', 'companies.name'])]
                ) : null,
                'company'     => \App\Services\CompanyContext::hasActive() ? \App\Services\CompanyContext::active()->only('id','name','plan','vat_registered') : null,
                'branch'      => \App\Services\BranchContext::active()?->only('id','name'),
                'permissions' => $request->user() ? $this->getUserPermissions($request->user()) : [],
            ],
            'flash' => ['success' => session('success'), 'error' => session('error')],
        ]);
    }

    private function getUserPermissions($user): array
    {
        $activeCompany = \App\Services\CompanyContext::hasActive() ? \App\Services\CompanyContext::active() : null;

        if (!$activeCompany) {
            return [];
        }

        $pivot = $user->companies()->where('companies.id', $activeCompany->id)->first()?->pivot;

        if (!$pivot) {
            return [];
        }

        $role = $pivot->role ?? null;

        return match ($role) {
            'owner' => ['create', 'edit', 'delete', 'view'],
            'admin' => ['create', 'edit', 'delete', 'view'],
            'manager' => ['create', 'edit', 'view'],
            'sales' => ['create', 'view'],
            'warehouse' => ['view'],
            'finance' => ['view'],
            'hr' => ['view'],
            default => ['view'],
        };
    }
}
