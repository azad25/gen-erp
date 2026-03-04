<?php

namespace App\Domain\CMS\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ERPIntegrationServiceInterface
{
    /**
     * Get products for product grid section.
     */
    public function getProductsForGrid(int $companyId, array $options = []): Collection;

    /**
     * Get team members for team grid section.
     */
    public function getTeamMembersForGrid(int $companyId, array $options = []): Collection;

    /**
     * Get projects for portfolio grid section.
     */
    public function getProjectsForPortfolio(int $companyId, array $options = []): array;

    /**
     * Get blog posts for blog section.
     */
    public function getBlogPostsForSection(int $siteId, array $options = []): Collection;

    /**
     * Get statistics for stats section.
     */
    public function getCompanyStats(int $companyId): array;

    /**
     * Get testimonials from completed projects or customer feedback.
     */
    public function getTestimonials(int $companyId, array $options = []): array;

    /**
     * Search across ERP data for global search functionality.
     */
    public function searchERPData(int $companyId, string $query, array $types = []): array;

    /**
     * Get related products based on category, tags, or purchase history.
     */
    public function getRelatedProducts(int $productId, int $companyId, array $options = []): Collection;
}