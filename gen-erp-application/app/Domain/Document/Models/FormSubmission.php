<?php

namespace App\Domain\Document\Models;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Form submission data.
 */
class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'submitted_by',
        'submission_data',
        'ip_address',
        'user_agent',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submission_data' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get specific field value from submission data.
     */
    public function getFieldValue(string $fieldKey): mixed
    {
        return $this->submission_data[$fieldKey] ?? null;
    }

    /**
     * Mark submission as processed.
     */
    public function markAsProcessed(): bool
    {
        return $this->update(['status' => 'processed']);
    }

    /**
     * Get formatted submission data for display.
     */
    public function getFormattedData(): array
    {
        $formatted = [];
        $form = $this->form()->with('fields')->first();
        
        if (!$form) {
            return $this->submission_data;
        }
        
        foreach ($form->fields as $field) {
            $value = $this->getFieldValue($field->field_key);
            $formatted[$field->label] = $this->formatFieldValue($field, $value);
        }
        
        return $formatted;
    }

    /**
     * Format field value based on field type.
     */
    private function formatFieldValue(FormField $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '-';
        }

        return match ($field->field_type) {
            FormFieldType::DATE => \Carbon\Carbon::parse($value)->format('Y-m-d'),
            FormFieldType::DATETIME => \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s'),
            FormFieldType::MULTISELECT, FormFieldType::CHECKBOX => is_array($value) ? implode(', ', $value) : $value,
            FormFieldType::FILE, FormFieldType::IMAGE => basename($value),
            default => (string) $value,
        };
    }
}