<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CMSDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = $request->user()->currentCompany->id;

        return Inertia::render('CMS/Dashboard/Index', [
            'metrics' => [
                'totalSites' => $this->getTotalSites($companyId),
                'totalPages' => $this->getTotalPages($companyId),
                'totalBlogPosts' => $this->getTotalBlogPosts($companyId),
                'totalContacts' => $this->getTotalContacts($companyId),
                'publishedPages' => $this->getPublishedPages($companyId),
                'draftPages' => $this->getDraftPages($companyId),
            ]
        ]);
    }

    private function getTotalSites(int $companyId): int
    {
        return DB::table('cms_sites')
            ->where('company_id', $companyId)
            ->count();
    }

    private function getTotalPages(int $companyId): int
    {
        return DB::table('cms_pages')
            ->join('cms_sites', 'cms_pages.site_id', '=', 'cms_sites.id')
            ->where('cms_sites.company_id', $companyId)
            ->count();
    }

    private function getTotalBlogPosts(int $companyId): int
    {
        return DB::table('cms_blog_posts')
            ->join('cms_sites', 'cms_blog_posts.site_id', '=', 'cms_sites.id')
            ->where('cms_sites.company_id', $companyId)
            ->count();
    }

    private function getTotalContacts(int $companyId): int
    {
        return DB::table('cms_contact_submissions')
            ->join('cms_sites', 'cms_contact_submissions.site_id', '=', 'cms_sites.id')
            ->where('cms_sites.company_id', $companyId)
            ->count();
    }

    private function getPublishedPages(int $companyId): int
    {
        return DB::table('cms_pages')
            ->join('cms_sites', 'cms_pages.site_id', '=', 'cms_sites.id')
            ->where('cms_sites.company_id', $companyId)
            ->where('cms_pages.status', 'published')
            ->count();
    }

    private function getDraftPages(int $companyId): int
    {
        return DB::table('cms_pages')
            ->join('cms_sites', 'cms_pages.site_id', '=', 'cms_sites.id')
            ->where('cms_sites.company_id', $companyId)
            ->where('cms_pages.status', 'draft')
            ->count();
    }
}