<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }
    
    public function setLocale(Request $request, $locale)
    {
        if (in_array($locale, ['en', 'bn'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }
        
        return redirect()->back();
    }
}
