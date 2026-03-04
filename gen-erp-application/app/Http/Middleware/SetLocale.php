<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Priority order: User preference > Session > Default (Bengali)
        $locale = 'bn'; // Default to Bengali
        
        // Check user preference if authenticated
        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        } 
        // Check session
        elseif (Session::has('locale')) {
            $locale = Session::get('locale');
        }
        
        // Ensure locale is supported
        if (in_array($locale, ['en', 'bn'])) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        } else {
            App::setLocale('bn');
            Session::put('locale', 'bn');
        }

        return $next($request);
    }
}
