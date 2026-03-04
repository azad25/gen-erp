<?php

namespace App\Domain\CMS\Services;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Models\Menu;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for public-facing site rendering and data retrieval.
 */
class PublicSiteService
{
    /**
     * Find site by domain or subdomain.
     */
    public function findSiteByTenant(string $tenant): ?Site
    {
        // Try to find by custom domain first
        $site = Site::where('custom_domain', $tenant)
            ->where('is_published', true)
            ->first();

        if ($site) {
            return $site;
        }

        // Try to find by subdomain
        return Site::where('subdomain', $tenant)
            ->where('is_published', true)
            ->first();
    }

    /**
     * Get site with all public data for rendering.
     */
    public function getSiteData(string $tenant): ?array
    {
        $site = $this->findSiteByTenant($tenant);

        if (!$site) {
            return null;
        }

        return [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'subdomain' => $site->subdomain,
                'custom_domain' => $site->custom_domain,
                'theme' => $site->theme,
                'logo_url' => $site->logo_url,
                'favicon_url' => $site->favicon_url,
                'meta_title' => $site->meta_title,
                'meta_description' => $site->meta_description,
                'settings' => $site->settings,
            ],
            'menus' => $this->getSiteMenus($site->id),
        ];
    }

    /**
     * Get page by slug for a site.
     */
    public function getPageBySlug(string $tenant, string $slug): ?array
    {
        $site = $this->findSiteByTenant($tenant);

        if (!$site) {
            return null;
        }

        $page = Page::where('site_id', $site->id)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['sections' => function ($query) {
                $query->where('is_visible', true)->orderBy('order');
            }])
            ->first();

        if (!$page) {
            return null;
        }

        return [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'meta_keywords' => $page->meta_keywords,
                'og_image' => $page->og_image,
                'is_homepage' => $page->is_homepage,
            ],
            'sections' => $page->sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'type' => $section->type,
                    'content' => $section->content,
                    'order' => $section->order,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get homepage for a site.
     */
    public function getHomepage(string $tenant): ?array
    {
        $site = $this->findSiteByTenant($tenant);

        if (!$site) {
            return null;
        }

        $page = Page::where('site_id', $site->id)
            ->where('is_homepage', true)
            ->where('is_published', true)
            ->with(['sections' => function ($query) {
                $query->where('is_visible', true)->orderBy('order');
            }])
            ->first();

        if (!$page) {
            // If no homepage is set, get the first published page
            $page = Page::where('site_id', $site->id)
                ->where('is_published', true)
                ->with(['sections' => function ($query) {
                    $query->where('is_visible', true)->orderBy('order');
                }])
                ->orderBy('created_at')
                ->first();
        }

        if (!$page) {
            return null;
        }

        return [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'meta_keywords' => $page->meta_keywords,
                'og_image' => $page->og_image,
                'is_homepage' => $page->is_homepage,
            ],
            'sections' => $page->sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'type' => $section->type,
                    'content' => $section->content,
                    'order' => $section->order,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get all published pages for a site.
     */
    public function getSitePages(string $tenant): array
    {
        $site = $this->findSiteByTenant($tenant);

        if (!$site) {
            return [];
        }

        $pages = Page::where('site_id', $site->id)
            ->where('is_published', true)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        return $pages->map(function ($page) {
            return [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'is_homepage' => $page->is_homepage,
            ];
        })->toArray();
    }

    /**
     * Get site menus.
     */
    public function getSiteMenus(int $siteId): array
    {
        $menus = Menu::where('site_id', $siteId)
            ->where('is_active', true)
            ->with(['items' => function ($query) {
                $query->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)->orderBy('order');
                    }]);
            }])
            ->get();

        return $menus->map(function ($menu) {
            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'location' => $menu->location,
                'items' => $menu->items->map(function ($item) {
                    return $this->formatMenuItem($item);
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Format menu item with children.
     */
    private function formatMenuItem($item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->url,
            'type' => $item->type,
            'target' => $item->target,
            'icon' => $item->icon,
            'order' => $item->order,
            'children' => $item->children->map(function ($child) {
                return $this->formatMenuItem($child);
            })->toArray(),
        ];
    }

    /**
     * Get blog posts for a site.
     */
    public function getBlogPosts(string $tenant, ?int $categoryId = null, int $perPage = 10): array
    {
        $site = $this->findSiteByTenant($tenant);

        if (!$site) {
            return [];
        }

        $query = $site->blogPosts()
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $posts = $query->paginate($perPage);

        return [
            'data' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ];
    }

    /**
     * Get single blog post by slug.
     */
    public function getBlogPost(string $tenant, string $slug): ?array
    {
        $site = $this->findSiteByTenant($tenant);

        if (!$site) {
            return null;
        }

        $post = $site->blogPosts()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->with(['category', 'author'])
            ->first();

        if (!$post) {
            return null;
        }

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featured_image' => $post->featured_image,
            'published_at' => $post->published_at,
            'author' => $post->author ? [
                'id' => $post->author->id,
                'name' => $post->author->name,
            ] : null,
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
        ];
    }

    /**
     * Search pages and blog posts.
     */
    public function search(string $tenant, string $query, int $perPage = 10): array
    {
        $site = $this->findSiteByTenant($tenant);

        if (!$site) {
            return ['pages' => [], 'posts' => []];
        }

        // Search pages
        $pages = Page::where('site_id', $site->id)
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('meta_description', 'like', "%{$query}%");
            })
            ->limit($perPage)
            ->get()
            ->map(function ($page) {
                return [
                    'type' => 'page',
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'excerpt' => $page->meta_description,
                ];
            });

        // Search blog posts
        $posts = $site->blogPosts()
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->limit($perPage)
            ->get()
            ->map(function ($post) {
                return [
                    'type' => 'post',
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'published_at' => $post->published_at,
                ];
            });

        return [
            'pages' => $pages->toArray(),
            'posts' => $posts->toArray(),
        ];
    }
}