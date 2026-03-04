<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch the application language.
     */
    public function switch(Request $request)
    {
        $request->validate([
            'locale' => 'required|string|in:bn,en'
        ]);

        $locale = $request->input('locale');
        
        // Set the application locale
        App::setLocale($locale);
        
        // Store in session
        Session::put('locale', $locale);
        
        // Store in user preferences if authenticated
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        return redirect()->back()->with('success', 'Language switched successfully');
    }
}