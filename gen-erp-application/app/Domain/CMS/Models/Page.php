<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Enums\PageStatus;
use Database\Factories\Domain\CMS\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a CMS page within a site.
 */
class Page extends Model
{
    use HasFactory;

    protected $table = 'cms_pages';

    protected $fillable = [
        'site_id',
        'title',
        'slug',
        'seo_title',
        'seo_description',
        'seo_image',
        'status',
        'is_homepage',
        'sort_order',
        'published_at',
        'scheduled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'is_homepage' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
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
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Scope to only published pages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Page>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Page>
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
     * Scope to only draft pages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Page>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Page>
     */
    public function scopeDraft(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', PageStatus::DRAFT);
    }

    /**
     * Scope by slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Page>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Page>
     */
    public function scopeBySlug(\Illuminate\Database\Eloquent\Builder $query, string $slug): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('slug', $slug);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get the page URL.
     */
    public function getUrl(): string
    {
        $baseUrl = $this->site->getUrl();
        
        if ($this->is_homepage) {
            return $baseUrl;
        }

        return "{$baseUrl}/{$this->slug}";
    }

    /**
     * Check if page is published.
     */
    public function isPublished(): bool
    {
        return $this->status === PageStatus::PUBLISHED 
            && ($this->published_at === null || $this->published_at <= now());
    }

    /**
     * Check if page is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === PageStatus::SCHEDULED 
            && $this->scheduled_at !== null 
            && $this->scheduled_at > now();
    }

    /**
     * Get SEO title or fallback to page title.
     */
    public function getSeoTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    /**
     * Get SEO description or generate from content.
     */
    public function getSeoDescription(): ?string
    {
        if ($this->seo_description) {
            return $this->seo_description;
        }

        // Generate from first text section if available
        $textSection = $this->sections()
            ->where('type', 'text_block')
            ->first();

        if ($textSection && isset($textSection->content['body'])) {
            return substr(strip_tags($textSection->content['body']), 0, 160);
        }

        return null;
    }
}