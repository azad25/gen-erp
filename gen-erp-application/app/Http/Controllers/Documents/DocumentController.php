<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * Display the document management page.
     */
    public function index(): Response
    {
        $company = CompanyContext::active();
        
        return Inertia::render('Documents/Index', [
            'company' => $company->only(['id', 'name']),
        ]);
    }

    /**
     * Display the documents dashboard.
     */
    public function dashboard(): Response
    {
        $company = CompanyContext::active();
        
        return Inertia::render('Documents/Dashboard', [
            'company' => $company->only(['id', 'name']),
        ]);
    }

    /**
     * Display the folders page.
     */
    public function folders(): Response
    {
        $company = CompanyContext::active();
        
        return Inertia::render('Documents/Folders', [
            'company' => $company->only(['id', 'name']),
        ]);
    }

    /**
     * Display the recent documents page.
     */
    public function recent(): Response
    {
        $company = CompanyContext::active();
        
        return Inertia::render('Documents/Recent', [
            'company' => $company->only(['id', 'name']),
        ]);
    }
}