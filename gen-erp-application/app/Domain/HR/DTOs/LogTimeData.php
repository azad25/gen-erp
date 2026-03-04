<?php

namespace App\Domain\HR\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for time logging
 */
class LogTimeData
{
    public function __construct(
        public readonly int $employeeId,
        public readonly Carbon $entryDate,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly float $hours,
        public readonly ?int $taskId = null,
        public readonly ?int $projectId = null,
        public readonly ?string $description = null,
        public readonly string $entryType = 'task',
        public readonly bool $isBillable = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'],
            entryDate: Carbon::parse($data['entry_date']),
            startTime: $data['start_time'],
            endTime: $data['end_time'],
            hours: $data['hours'],
            taskId: $data['task_id'] ?? null,
            projectId: $data['project_id'] ?? null,
            description: $data['description'] ?? null,
            entryType: $data['entry_type'] ?? 'task',
            isBillable: $data['is_billable'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'entry_date' => $this->entryDate->toDateString(),
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'hours' => $this->hours,
            'task_id' => $this->taskId,
            'project_id' => $this->projectId,
            'description' => $this->description,
            'entry_type' => $this->entryType,
            'is_billable' => $this->isBillable,
        ];
    }
}