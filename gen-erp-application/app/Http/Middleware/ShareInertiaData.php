<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareInertiaData
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share([
            'locale' => app()->getLocale(),
            'translations' => [
                'dashboard' => __('dashboard'),
                'sidebar' => __('sidebar'),
            ],
        ]);

        return $next($request);
    }
}