<?php

namespace App\Models\Traits;

use App\Models\CustomFieldValue;
use App\Services\CompanyContext;
use App\Services\CustomFieldService;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Apply to any model that supports custom fields (Product, Customer, Invoice, etc.).
 */
trait HasCustomFields
{
    /**
     * In-memory pending custom field values before persistence.
     *
     * @var array<string, mixed>
     */
    protected array $pendingCustomFields = [];

    /**
     * Loaded custom field values cache to avoid N+1.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $loadedCustomFields = null;

    /**
     * Returns the entity_type string for this model (e.g. 'product', 'customer').
     */
    abstract public function customFieldEntityType(): string;

    public static function bootHasCustomFields(): void
    {
        static::saved(function (self $model): void {
            if (! empty($model->pendingCustomFields)) {
                $model->saveCustomFields();
            }
        });
    }

    /**
     * @return HasMany<CustomFieldValue, $this>
     */
    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'entity_id')
            ->where('entity_type', $this->customFieldEntityType());
    }

    /**
     * Get a specific custom field value.
     */
    public function getCustomField(string $key): mixed
    {
        // Use the in-memory cache if available
        if ($this->loadedCustomFields !== null && array_key_exists($key, $this->loadedCustomFields)) {
            return $this->loadedCustomFields[$key];
        }

        $value = CustomFieldValue::withoutGlobalScopes()
            ->where('company_id', CompanyContext::activeId())
            ->where('entity_type', $this->customFieldEntityType())
            ->where('entity_id', $this->getKey())
            ->where('field_key', $key)
            ->first();

        return $value?->getTypedValue();
    }

    /**
     * Set a custom field value in memory (call saveCustomFields() to persist).
     */
    public function setCustomField(string $key, mixed $value): void
    {
        $this->pendingCustomFields[$key] = $value;
    }

    /**
     * Persist all pending custom field changes via CustomFieldService.
     */
    public function saveCustomFields(): void
    {
        if (empty($this->pendingCustomFields)) {
            return;
        }

        app(CustomFieldService::class)->saveValues(
            $this->customFieldEntityType(),
            $this->getKey(),
            $this->pendingCustomFields,
        );

        // Reset pending
        $this->pendingCustomFields = [];
        $this->loadedCustomFields = null;
    }

    /**
     * Load all custom field values into memory to avoid N+1 queries.
     */
    public function loadCustomFields(): void
    {
        $values = app(CustomFieldService::class)->getValues(
            $this->customFieldEntityType(),
            $this->getKey(),
        );

        $this->loadedCustomFields = $values
            ->mapWithKeys(fn (CustomFieldValue $v): array => [$v->field_key => $v->getTypedValue()])
            ->all();
    }
}
