<?php

namespace App\Domain\Document\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Custom form created by users for data collection.
 */
class Form extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'slug',
        'is_public',
        'is_active',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('display_order');
    }

    /**
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get form settings with defaults.
     */
    public function getFormSettings(): array
    {
        return array_merge([
            'success_message' => 'Thank you for your submission!',
            'redirect_url' => null,
            'allow_multiple_submissions' => true,
            'require_login' => false,
            'send_email_notification' => false,
            'notification_email' => null,
        ], $this->settings ?? []);
    }

    /**
     * Generate unique slug for the form.
     */
    public function generateSlug(): string
    {
        $baseSlug = \Str::slug($this->name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get form validation rules based on fields.
     */
    public function getValidationRules(): array
    {
        $rules = [];
        
        foreach ($this->fields as $field) {
            $fieldRules = [];
            
            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }
            
            // Add field type specific rules
            $fieldRules = array_merge($fieldRules, $field->getTypeValidationRules());
            
            // Add custom validation rules
            if ($field->validation_rules) {
                $fieldRules = array_merge($fieldRules, $field->validation_rules);
            }
            
            $rules[$field->field_key] = $fieldRules;
        }
        
        return $rules;
    }
}