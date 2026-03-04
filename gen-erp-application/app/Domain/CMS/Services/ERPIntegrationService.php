<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\ERPIntegrationServiceInterface;
use App\Domain\Product\Models\Product;
use App\Domain\HR\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for integrating CMS with ERP data.
 */
class ERPIntegrationService implements ERPIntegrationServiceInterface
{
    /**
     * Get products for product grid section.
     */
    public function getProductsForGrid(int $companyId, array $options = []): Collection
    {
        $query = Product::where('company_id', $companyId)
            ->where('is_active', true);

        // Apply filters
        if (isset($options['category_id'])) {
            $query->where('category_id', $options['category_id']);
        }

        if (isset($options['featured_only']) && $options['featured_only']) {
            $query->where('is_featured', true);
        }

        if (isset($options['tag'])) {
            $query->whereJsonContains('tags', $options['tag']);
        }

        // Apply sorting
        $sortBy = $options['sort_by'] ?? 'name';
        $sortOrder = $options['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'price':
                $query->orderBy('selling_price', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'name':
            default:
                $query->orderBy('name', $sortOrder);
                break;
        }

        // Apply limit
        $limit = $options['limit'] ?? 12;
        $query->limit($limit);

        return $query->get();
    }

    /**
     * Get team members for team grid section.
     */
    public function getTeamMembersForGrid(int $companyId, array $options = []): Collection
    {
        $query = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['user', 'department']);

        // Apply filters
        if (isset($options['department_id'])) {
            $query->where('department_id', $options['department_id']);
        }

        if (isset($options['show_on_website']) && $options['show_on_website']) {
            $query->where('show_on_website', true);
        }

        // Apply sorting
        $sortBy = $options['sort_by'] ?? 'first_name';
        $sortOrder = $options['sort_order'] ?? 'asc';

        switch ($sortBy) {
            case 'hire_date':
                $query->orderBy('hire_date', $sortOrder);
                break;
            case 'department':
                $query->join('departments', 'employees.department_id', '=', 'departments.id')
                    ->orderBy('departments.name', $sortOrder);
                break;
            case 'first_name':
            default:
                $query->orderBy('first_name', $sortOrder);
                break;
        }

        // Apply limit
        $limit = $options['limit'] ?? 12;
        $query->limit($limit);

        return $query->get();
    }

    /**
     * Get projects for portfolio grid section.
     */
    public function getProjectsForPortfolio(int $companyId, array $options = []): array
    {
        // Check if Project domain exists
        if (!class_exists('App\Domain\Project\Models\Project')) {
            return [];
        }

        $projectClass = 'App\Domain\Project\Models\Project';
        
        $query = $projectClass::where('company_id', $companyId)
            ->where('is_public', true)
            ->where('status', 'completed');

        // Apply filters
        if (isset($options['category'])) {
            $query->whereJsonContains('categories', $options['category']);
        }

        if (isset($options['client_id'])) {
            $query->where('customer_id', $options['client_id']);
        }

        // Apply sorting
        $sortBy = $options['sort_by'] ?? 'completed_at';
        $sortOrder = $options['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'budget':
                $query->orderBy('budget', $sortOrder);
                break;
            case 'completed_at':
            default:
                $query->orderBy('completed_at', $sortOrder);
                break;
        }

        // Apply limit
        $limit = $options['limit'] ?? 6;
        $query->limit($limit);

        return $query->get()->toArray();
    }

    /**
     * Get blog posts for blog section.
     */
    public function getBlogPostsForSection(int $siteId, array $options = []): Collection
    {
        $query = \App\Domain\CMS\Models\BlogPost::where('site_id', $siteId)
            ->where('is_published', true)
            ->with(['category', 'author']);

        // Apply filters
        if (isset($options['category_id'])) {
            $query->where('category_id', $options['category_id']);
        }

        if (isset($options['featured_only']) && $options['featured_only']) {
            $query->where('is_featured', true);
        }

        // Apply sorting
        $sortBy = $options['sort_by'] ?? 'published_at';
        $sortOrder = $options['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'views':
                $query->orderBy('views_count', $sortOrder);
                break;
            case 'published_at':
            default:
                $query->orderBy('published_at', $sortOrder);
                break;
        }

        // Apply limit
        $limit = $options['limit'] ?? 6;
        $query->limit($limit);

        return $query->get();
    }

    /**
     * Get statistics for stats section.
     */
    public function getCompanyStats(int $companyId): array
    {
        $stats = [];

        // Product count
        if (class_exists('App\Domain\Product\Models\Product')) {
            $stats['products'] = Product::where('company_id', $companyId)
                ->where('is_active', true)
                ->count();
        }

        // Employee count
        if (class_exists('App\Domain\HR\Models\Employee')) {
            $stats['employees'] = Employee::where('company_id', $companyId)
                ->where('is_active', true)
                ->count();
        }

        // Project count
        if (class_exists('App\Domain\Project\Models\Project')) {
            $projectClass = 'App\Domain\Project\Models\Project';
            $stats['projects_completed'] = $projectClass::where('company_id', $companyId)
                ->where('status', 'completed')
                ->count();
        }

        // Customer count
        if (class_exists('App\Domain\Customer\Models\Customer')) {
            $customerClass = 'App\Domain\Customer\Models\Customer';
            $stats['customers'] = $customerClass::where('company_id', $companyId)
                ->where('is_active', true)
                ->count();
        }

        // Years in business (based on company creation)
        $company = \App\Domain\Auth\Models\Company::find($companyId);
        if ($company) {
            $stats['years_in_business'] = now()->diffInYears($company->created_at);
        }

        return $stats;
    }

    /**
     * Get testimonials from completed projects or customer feedback.
     */
    public function getTestimonials(int $companyId, array $options = []): array
    {
        $testimonials = [];

        // Get testimonials from project feedback if available
        if (class_exists('App\Domain\Project\Models\Project')) {
            $projectClass = 'App\Domain\Project\Models\Project';
            $projects = $projectClass::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereNotNull('client_feedback')
                ->with('customer')
                ->limit($options['limit'] ?? 6)
                ->get();

            foreach ($projects as $project) {
                if ($project->client_feedback && $project->customer) {
                    $testimonials[] = [
                        'name' => $project->customer->name,
                        'company' => $project->customer->company_name,
                        'content' => $project->client_feedback,
                        'rating' => $project->client_rating ?? 5,
                        'project' => $project->name,
                        'date' => $project->completed_at,
                    ];
                }
            }
        }

        // Get testimonials from product reviews if available
        if (class_exists('App\Domain\CMS\Models\ProductReview')) {
            $reviews = \App\Domain\CMS\Models\ProductReview::whereHas('site', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('is_approved', true)
            ->where('rating', '>=', 4)
            ->whereNotNull('review')
            ->limit($options['limit'] ?? 6)
            ->get();

            foreach ($reviews as $review) {
                $testimonials[] = [
                    'name' => $review->customer_name,
                    'company' => null,
                    'content' => $review->review,
                    'rating' => $review->rating,
                    'product' => $review->product_name ?? 'Product',
                    'date' => $review->created_at,
                ];
            }
        }

        // Sort by date and limit
        usort($testimonials, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return array_slice($testimonials, 0, $options['limit'] ?? 6);
    }

    /**
     * Search across ERP data for global search functionality.
     */
    public function searchERPData(int $companyId, string $query, array $types = []): array
    {
        $results = [];

        // Search products
        if (empty($types) || in_array('products', $types)) {
            if (class_exists('App\Domain\Product\Models\Product')) {
                $products = Product::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhere('sku', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();

                foreach ($products as $product) {
                    $results[] = [
                        'type' => 'product',
                        'title' => $product->name,
                        'description' => $product->description,
                        'url' => "/products/{$product->slug}",
                        'image' => $product->image,
                    ];
                }
            }
        }

        // Search team members
        if (empty($types) || in_array('team', $types)) {
            if (class_exists('App\Domain\HR\Models\Employee')) {
                $employees = Employee::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->where('show_on_website', true)
                    ->where(function ($q) use ($query) {
                        $q->where('first_name', 'like', "%{$query}%")
                          ->orWhere('last_name', 'like', "%{$query}%")
                          ->orWhere('position', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();

                foreach ($employees as $employee) {
                    $results[] = [
                        'type' => 'team',
                        'title' => $employee->full_name,
                        'description' => $employee->position,
                        'url' => "/team/{$employee->slug}",
                        'image' => $employee->photo,
                    ];
                }
            }
        }

        // Search projects
        if (empty($types) || in_array('projects', $types)) {
            if (class_exists('App\Domain\Project\Models\Project')) {
                $projectClass = 'App\Domain\Project\Models\Project';
                $projects = $projectClass::where('company_id', $companyId)
                    ->where('is_public', true)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();

                foreach ($projects as $project) {
                    $results[] = [
                        'type' => 'project',
                        'title' => $project->name,
                        'description' => $project->description,
                        'url' => "/portfolio/{$project->slug}",
                        'image' => $project->featured_image,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Get related products based on category, tags, or purchase history.
     */
    public function getRelatedProducts(int $productId, int $companyId, array $options = []): Collection
    {
        $product = Product::where('company_id', $companyId)->find($productId);
        
        if (!$product) {
            return collect([]);
        }

        $query = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('id', '!=', $productId);

        $algorithm = $options['algorithm'] ?? 'category';

        switch ($algorithm) {
            case 'category':
                if ($product->category_id) {
                    $query->where('category_id', $product->category_id);
                }
                break;
            
            case 'tags':
                if ($product->tags) {
                    foreach ($product->tags as $tag) {
                        $query->orWhereJsonContains('tags', $tag);
                    }
                }
                break;
            
            case 'price_range':
                $priceRange = $product->selling_price * 0.3; // 30% price range
                $query->whereBetween('selling_price', [
                    $product->selling_price - $priceRange,
                    $product->selling_price + $priceRange
                ]);
                break;
        }

        $limit = $options['limit'] ?? 4;
        return $query->limit($limit)->get();
    }
}