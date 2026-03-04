<?php

namespace App\Domain\HR\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Availability - Availability calendar for employees
 */
class EmployeeAvailability extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'is_available',
        'availability_type',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    // ─── Relationships ───

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ─── Helpers ───

    public function isFullDay(): bool
    {
        return $this->availability_type === 'full_day';
    }

    public function isPartialDay(): bool
    {
        return in_array($this->availability_type, ['morning', 'afternoon']);
    }

    public function isUnavailable(): bool
    {
        return $this->availability_type === 'unavailable' || !$this->is_available;
    }

    public function getAvailabilityHours(): int
    {
        if (!$this->is_available || $this->availability_type === 'unavailable') {
            return 0;
        }

        return match ($this->availability_type) {
            'full_day' => 8,
            'morning', 'afternoon' => 4,
            default => 0,
        };
    }

    public function getDisplayStatus(): string
    {
        if (!$this->is_available) {
            return 'Unavailable';
        }

        return match ($this->availability_type) {
            'full_day' => 'Available (Full Day)',
            'morning' => 'Available (Morning)',
            'afternoon' => 'Available (Afternoon)',
            'unavailable' => 'Unavailable',
            default => 'Unknown',
        };
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                    ->where('availability_type', '!=', 'unavailable');
    }

    public function scopeUnavailable($query)
    {
        return $query->where(function ($q) {
            $q->where('is_available', false)
              ->orWhere('availability_type', 'unavailable');
        });
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}