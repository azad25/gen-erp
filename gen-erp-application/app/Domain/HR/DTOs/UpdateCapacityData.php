<?php

namespace App\Domain\HR\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for capacity updates
 */
class UpdateCapacityData
{
    public function __construct(
        public readonly int $employeeId,
        public readonly Carbon $weekStartDate,
        public readonly int $totalCapacityHours,
        public readonly ?int $allocatedHours = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'],
            weekStartDate: Carbon::parse($data['week_start_date'])->startOfWeek(),
            totalCapacityHours: $data['total_capacity_hours'],
            allocatedHours: $data['allocated_hours'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'week_start_date' => $this->weekStartDate->toDateString(),
            'total_capacity_hours' => $this->totalCapacityHours,
            'allocated_hours' => $this->allocatedHours,
        ];
    }
}