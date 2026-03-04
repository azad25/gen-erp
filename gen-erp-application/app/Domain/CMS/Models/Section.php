<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Enums\SectionType;
use Database\Factories\Domain\CMS\SectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a section within a CMS page.
 */
class Section extends Model
{
    use HasFactory;

    protected $table = 'cms_sections';

    protected $fillable = [
        'page_id',
        'type',
        'sort_order',
        'content',
        'is_visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'content' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SectionFactory
    {
        return SectionFactory::new();
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * Scope to only visible sections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Section>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Section>
     */
    public function scopeVisible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope by section type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Section>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Section>
     */
    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, SectionType $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('type', $type);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get content value by key with fallback.
     */
    public function getContent(string $key, mixed $default = null): mixed
    {
        return $this->content[$key] ?? $default;
    }

    /**
     * Set content value by key.
     */
    public function setContent(string $key, mixed $value): void
    {
        $content = $this->content ?? [];
        $content[$key] = $value;
        $this->content = $content;
    }

    /**
     * Check if section has content for key.
     */
    public function hasContent(string $key): bool
    {
        return isset($this->content[$key]) && !empty($this->content[$key]);
    }

    /**
     * Get section title for display.
     */
    public function getDisplayTitle(): string
    {
        return match ($this->type) {
            SectionType::HERO_BANNER => $this->getContent('title', 'Hero Banner'),
            SectionType::TEXT_BLOCK => $this->getContent('heading', 'Text Block'),
            SectionType::IMAGE_TEXT => $this->getContent('heading', 'Image & Text'),
            SectionType::PRODUCT_GRID => $this->getContent('heading', 'Products'),
            SectionType::PORTFOLIO_GRID => $this->getContent('heading', 'Portfolio'),
            SectionType::TEAM_GRID => $this->getContent('heading', 'Team'),
            SectionType::STATS => $this->getContent('heading', 'Statistics'),
            SectionType::FAQ => $this->getContent('heading', 'FAQ'),
            SectionType::CTA_BANNER => $this->getContent('title', 'Call to Action'),
            SectionType::CONTACT_FORM => $this->getContent('heading', 'Contact Form'),
            SectionType::GALLERY => $this->getContent('heading', 'Gallery'),
            SectionType::TESTIMONIALS => $this->getContent('heading', 'Testimonials'),
            SectionType::BLOG_POSTS => $this->getContent('heading', 'Blog Posts'),
            SectionType::CUSTOM_HTML => 'Custom HTML',
            default => ucfirst(str_replace('_', ' ', $this->type->value)),
        };
    }

    /**
     * Get section icon for display.
     */
    public function getIcon(): string
    {
        return match ($this->type) {
            SectionType::HERO_BANNER => 'heroicons:photo',
            SectionType::TEXT_BLOCK => 'heroicons:document-text',
            SectionType::IMAGE_TEXT => 'heroicons:photo',
            SectionType::PRODUCT_GRID => 'heroicons:squares-2x2',
            SectionType::PORTFOLIO_GRID => 'heroicons:briefcase',
            SectionType::TEAM_GRID => 'heroicons:users',
            SectionType::STATS => 'heroicons:chart-bar',
            SectionType::FAQ => 'heroicons:question-mark-circle',
            SectionType::CTA_BANNER => 'heroicons:megaphone',
            SectionType::CONTACT_FORM => 'heroicons:envelope',
            SectionType::GALLERY => 'heroicons:photo',
            SectionType::TESTIMONIALS => 'heroicons:chat-bubble-left-right',
            SectionType::BLOG_POSTS => 'heroicons:newspaper',
            SectionType::CUSTOM_HTML => 'heroicons:code-bracket',
            default => 'heroicons:squares-2x2',
        };
    }
}