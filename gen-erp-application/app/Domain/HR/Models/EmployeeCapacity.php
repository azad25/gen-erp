<?php

namespace App\Domain\HR\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Capacity - Weekly capacity planning for employees
 */
class EmployeeCapacity extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'week_start_date',
        'total_capacity_hours',
        'allocated_hours',
        'available_hours',
        'utilization_percentage',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'total_capacity_hours' => 'integer',
            'allocated_hours' => 'integer',
            'available_hours' => 'integer',
            'utilization_percentage' => 'decimal:2',
        ];
    }

    // ─── Relationships ───

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ─── Helpers ───

    public function calculateUtilization(): void
    {
        if ($this->total_capacity_hours > 0) {
            $this->utilization_percentage = ($this->allocated_hours / $this->total_capacity_hours) * 100;
        } else {
            $this->utilization_percentage = 0;
        }
        
        $this->available_hours = $this->total_capacity_hours - $this->allocated_hours;
        $this->save();
    }

    public function allocateHours(int $hours): bool
    {
        $this->allocated_hours += $hours;
        $this->calculateUtilization();
        return true;
    }

    public function deallocateHours(int $hours): void
    {
        $this->allocated_hours = max(0, $this->allocated_hours - $hours);
        $this->calculateUtilization();
    }

    public function isOverallocated(): bool
    {
        return $this->allocated_hours > $this->total_capacity_hours;
    }

    public function getUtilizationStatus(): string
    {
        $percentage = $this->utilization_percentage;
        
        if ($percentage > 100) {
            return 'overallocated';
        } elseif ($percentage >= 90) {
            return 'fully_utilized';
        } elseif ($percentage >= 70) {
            return 'well_utilized';
        } elseif ($percentage >= 50) {
            return 'moderately_utilized';
        } else {
            return 'underutilized';
        }
    }

    public function getWeekEndDate(): \Carbon\Carbon
    {
        return $this->week_start_date->copy()->addDays(6);
    }
}