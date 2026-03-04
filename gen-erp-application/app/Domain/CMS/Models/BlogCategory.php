<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a blog category for organizing blog posts.
 */
class BlogCategory extends Model
{
    use HasFactory;

    protected $table = 'cms_blog_categories';

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'description',
    ];

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function publishedPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id')
            ->published();
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Scope by slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<BlogCategory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<BlogCategory>
     */
    public function scopeBySlug(\Illuminate\Database\Eloquent\Builder $query, string $slug): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('slug', $slug);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get the category URL.
     */
    public function getUrl(): string
    {
        $baseUrl = $this->site->getUrl();
        return "{$baseUrl}/blog/category/{$this->slug}";
    }

    /**
     * Get posts count.
     */
    public function getPostsCount(): int
    {
        return $this->publishedPosts()->count();
    }
}