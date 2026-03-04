<?php

namespace App\Domain\CMS\Models;

use App\Domain\Auth\Models\User;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\BlogCategory;
use App\Domain\CMS\Enums\PageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a blog post within a CMS site.
 */
class BlogPost extends Model
{
    use HasFactory;

    protected $table = 'cms_blog_posts';

    protected $fillable = [
        'site_id',
        'category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'scheduled_at',
        'views_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<BlogCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Scope to only published posts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<BlogPost>  $query
     * @return \Illuminate\Database\Eloquent\Builder<BlogPost>
     */
    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', PageStatus::PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope by slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<BlogPost>  $query
     * @return \Illuminate\Database\Eloquent\Builder<BlogPost>
     */
    public function scopeBySlug(\Illuminate\Database\Eloquent\Builder $query, string $slug): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<BlogPost>  $query
     * @return \Illuminate\Database\Eloquent\Builder<BlogPost>
     */
    public function scopeInCategory(\Illuminate\Database\Eloquent\Builder $query, int $categoryId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('category_id', $categoryId);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get the blog post URL.
     */
    public function getUrl(): string
    {
        $baseUrl = $this->site->getUrl();
        return "{$baseUrl}/blog/{$this->slug}";
    }

    /**
     * Check if post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === PageStatus::PUBLISHED 
            && ($this->published_at === null || $this->published_at <= now());
    }

    /**
     * Get excerpt or generate from content.
     */
    public function getExcerpt(int $length = 160): string
    {
        if ($this->excerpt) {
            return $this->excerpt;
        }

        return substr(strip_tags($this->content), 0, $length) . '...';
    }

    /**
     * Get reading time estimate.
     */
    public function getReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}