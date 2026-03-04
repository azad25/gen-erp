<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Contracts\SEOServiceInterface;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\BlogPost;

/**
 * Service for SEO-related functionality.
 */
class SEOService implements SEOServiceInterface
{
    /**
     * Generate sitemap XML for a site.
     */
    public function generateSitemap(Site $site): string
    {
        $baseUrl = $site->getUrl();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Add homepage
        $xml .= $this->addSitemapUrl($baseUrl, now(), 'daily', '1.0');

        // Add pages
        $pages = $site->pages()->where('is_published', true)->get();
        foreach ($pages as $page) {
            $url = $baseUrl . '/' . $page->slug;
            $xml .= $this->addSitemapUrl($url, $page->updated_at, 'weekly', '0.8');
        }

        // Add blog posts
        $blogPosts = $site->blogPosts()->where('is_published', true)->get();
        foreach ($blogPosts as $post) {
            $url = $baseUrl . '/blog/' . $post->slug;
            $xml .= $this->addSitemapUrl($url, $post->updated_at, 'monthly', '0.6');
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate robots.txt for a site.
     */
    public function generateRobotsTxt(Site $site): string
    {
        $baseUrl = $site->getUrl();
        $robotsTxt = "User-agent: *\n";
        
        if ($site->isPublished()) {
            $robotsTxt .= "Allow: /\n";
            $robotsTxt .= "Disallow: /admin/\n";
            $robotsTxt .= "Disallow: /api/\n";
            $robotsTxt .= "Disallow: /*.json$\n";
        } else {
            $robotsTxt .= "Disallow: /\n";
        }

        $robotsTxt .= "\nSitemap: {$baseUrl}/sitemap.xml\n";

        return $robotsTxt;
    }

    /**
     * Generate structured data for a page.
     */
    public function generateStructuredData(Site $site, ?Page $page = null, ?BlogPost $blogPost = null): array
    {
        $structuredData = [];

        // Organization/LocalBusiness schema
        $structuredData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site->name,
            'url' => $site->getUrl(),
            'logo' => $site->logo_url,
            'description' => $site->meta_description,
            'address' => $this->getBusinessAddress($site),
            'contactPoint' => $this->getContactPoint($site),
            'sameAs' => $this->getSocialMediaLinks($site),
        ];

        // WebSite schema
        $structuredData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $site->name,
            'url' => $site->getUrl(),
            'description' => $site->meta_description,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $site->getUrl() . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        // Page-specific schema
        if ($page) {
            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $page->title,
                'description' => $page->meta_description,
                'url' => $site->getUrl() . '/' . $page->slug,
                'isPartOf' => [
                    '@type' => 'WebSite',
                    'name' => $site->name,
                    'url' => $site->getUrl(),
                ],
                'datePublished' => $page->created_at->toISOString(),
                'dateModified' => $page->updated_at->toISOString(),
            ];
        }

        // Blog post schema
        if ($blogPost) {
            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $blogPost->title,
                'description' => $blogPost->excerpt,
                'image' => $blogPost->featured_image,
                'url' => $site->getUrl() . '/blog/' . $blogPost->slug,
                'datePublished' => $blogPost->published_at->toISOString(),
                'dateModified' => $blogPost->updated_at->toISOString(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $blogPost->author?->name ?? 'Admin',
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => $site->name,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $site->logo_url,
                    ],
                ],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $site->getUrl() . '/blog/' . $blogPost->slug,
                ],
            ];
        }

        return $structuredData;
    }

    /**
     * Get SEO analysis for a site.
     */
    public function analyzeSEO(Site $site): array
    {
        $issues = [];
        $score = 100;

        // Check site basics
        if (!$site->meta_title) {
            $issues[] = ['type' => 'error', 'message' => 'Site meta title is missing'];
            $score -= 10;
        }

        if (!$site->meta_description) {
            $issues[] = ['type' => 'error', 'message' => 'Site meta description is missing'];
            $score -= 10;
        }

        if (!$site->logo_url) {
            $issues[] = ['type' => 'warning', 'message' => 'Site logo is missing'];
            $score -= 5;
        }

        // Check pages
        $pages = $site->pages()->where('is_published', true)->get();
        $pagesWithoutMetaTitle = $pages->where('meta_title', null)->count();
        $pagesWithoutMetaDescription = $pages->where('meta_description', null)->count();

        if ($pagesWithoutMetaTitle > 0) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$pagesWithoutMetaTitle} pages are missing meta titles"
            ];
            $score -= min($pagesWithoutMetaTitle * 2, 20);
        }

        if ($pagesWithoutMetaDescription > 0) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$pagesWithoutMetaDescription} pages are missing meta descriptions"
            ];
            $score -= min($pagesWithoutMetaDescription * 2, 20);
        }

        // Check blog posts
        $blogPosts = $site->blogPosts()->where('is_published', true)->get();
        $postsWithoutFeaturedImage = $blogPosts->where('featured_image', null)->count();

        if ($postsWithoutFeaturedImage > 0) {
            $issues[] = [
                'type' => 'info',
                'message' => "{$postsWithoutFeaturedImage} blog posts are missing featured images"
            ];
            $score -= min($postsWithoutFeaturedImage, 10);
        }

        // Check homepage
        $homepage = $site->homepage();
        if (!$homepage) {
            $issues[] = ['type' => 'error', 'message' => 'No homepage is set'];
            $score -= 15;
        }

        return [
            'score' => max($score, 0),
            'grade' => $this->getGrade($score),
            'issues' => $issues,
            'recommendations' => $this->getRecommendations($issues),
            'stats' => [
                'total_pages' => $pages->count(),
                'pages_with_meta_title' => $pages->count() - $pagesWithoutMetaTitle,
                'pages_with_meta_description' => $pages->count() - $pagesWithoutMetaDescription,
                'total_blog_posts' => $blogPosts->count(),
                'posts_with_featured_image' => $blogPosts->count() - $postsWithoutFeaturedImage,
            ],
        ];
    }

    /**
     * Generate meta tags for a page.
     */
    public function generateMetaTags(Site $site, ?Page $page = null, ?BlogPost $blogPost = null): array
    {
        $tags = [];

        if ($blogPost) {
            $title = $blogPost->meta_title ?: $blogPost->title;
            $description = $blogPost->meta_description ?: $blogPost->excerpt;
            $image = $blogPost->featured_image;
            $url = $site->getUrl() . '/blog/' . $blogPost->slug;
            $type = 'article';
        } elseif ($page) {
            $title = $page->meta_title ?: $page->title;
            $description = $page->meta_description;
            $image = $page->og_image;
            $url = $site->getUrl() . '/' . $page->slug;
            $type = 'website';
        } else {
            $title = $site->meta_title ?: $site->name;
            $description = $site->meta_description;
            $image = $site->logo_url;
            $url = $site->getUrl();
            $type = 'website';
        }

        // Basic meta tags
        $tags['title'] = $title;
        $tags['description'] = $description;
        $tags['canonical'] = $url;

        // Open Graph tags
        $tags['og:title'] = $title;
        $tags['og:description'] = $description;
        $tags['og:type'] = $type;
        $tags['og:url'] = $url;
        $tags['og:site_name'] = $site->name;
        
        if ($image) {
            $tags['og:image'] = $image;
        }

        // Twitter Card tags
        $tags['twitter:card'] = 'summary_large_image';
        $tags['twitter:title'] = $title;
        $tags['twitter:description'] = $description;
        
        if ($image) {
            $tags['twitter:image'] = $image;
        }

        // Article-specific tags
        if ($blogPost) {
            $tags['article:published_time'] = $blogPost->published_at->toISOString();
            $tags['article:modified_time'] = $blogPost->updated_at->toISOString();
            
            if ($blogPost->author) {
                $tags['article:author'] = $blogPost->author->name;
            }
            
            if ($blogPost->category) {
                $tags['article:section'] = $blogPost->category->name;
            }
        }

        return $tags;
    }

    /**
     * Add URL to sitemap XML.
     */
    private function addSitemapUrl(string $url, $lastmod, string $changefreq, string $priority): string
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>{$url}</loc>\n";
        $xml .= "    <lastmod>{$lastmod->format('Y-m-d')}</lastmod>\n";
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        $xml .= "  </url>\n";
        
        return $xml;
    }

    /**
     * Get business address from site settings.
     */
    private function getBusinessAddress(Site $site): ?array
    {
        $settings = $site->settings ?? [];
        
        if (!isset($settings['address'])) {
            return null;
        }

        return [
            '@type' => 'PostalAddress',
            'streetAddress' => $settings['address']['street'] ?? '',
            'addressLocality' => $settings['address']['city'] ?? '',
            'addressRegion' => $settings['address']['state'] ?? '',
            'postalCode' => $settings['address']['zip'] ?? '',
            'addressCountry' => $settings['address']['country'] ?? '',
        ];
    }

    /**
     * Get contact point from site settings.
     */
    private function getContactPoint(Site $site): ?array
    {
        $settings = $site->settings ?? [];
        
        if (!isset($settings['contact'])) {
            return null;
        }

        return [
            '@type' => 'ContactPoint',
            'telephone' => $settings['contact']['phone'] ?? '',
            'email' => $settings['contact']['email'] ?? '',
            'contactType' => 'customer service',
        ];
    }

    /**
     * Get social media links from site settings.
     */
    private function getSocialMediaLinks(Site $site): array
    {
        $settings = $site->settings ?? [];
        $socialLinks = [];

        if (isset($settings['social'])) {
            foreach ($settings['social'] as $platform => $url) {
                if ($url) {
                    $socialLinks[] = $url;
                }
            }
        }

        return $socialLinks;
    }

    /**
     * Get SEO grade based on score.
     */
    private function getGrade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Get recommendations based on issues.
     */
    private function getRecommendations(array $issues): array
    {
        $recommendations = [];

        foreach ($issues as $issue) {
            $recommendation = match ($issue['message']) {
                'Site meta title is missing' => 'Add a compelling meta title to your site settings',
                'Site meta description is missing' => 'Add a descriptive meta description to your site settings',
                'Site logo is missing' => 'Upload a logo to improve brand recognition',
                'No homepage is set' => 'Set one of your pages as the homepage',
                default => 'Review and fix the identified issue',
            };

            if (str_contains($issue['message'], 'pages are missing meta titles')) {
                $recommendation = 'Add unique meta titles to all your pages for better search visibility';
            } elseif (str_contains($issue['message'], 'pages are missing meta descriptions')) {
                $recommendation = 'Add compelling meta descriptions to all your pages';
            } elseif (str_contains($issue['message'], 'blog posts are missing featured images')) {
                $recommendation = 'Add featured images to your blog posts for better social sharing';
            }

            $recommendations[] = $recommendation;
        }

        return array_unique($recommendations);
    }
}