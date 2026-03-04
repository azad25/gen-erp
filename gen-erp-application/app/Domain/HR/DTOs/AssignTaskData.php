<?php

namespace App\Domain\HR\DTOs;

/**
 * Data Transfer Object for task assignment
 */
class AssignTaskData
{
    public function __construct(
        public readonly int $employeeId,
        public readonly int $taskId,
        public readonly int $assignedBy,
        public readonly ?float $estimatedHours = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'],
            taskId: $data['task_id'],
            assignedBy: $data['assigned_by'],
            estimatedHours: $data['estimated_hours'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'task_id' => $this->taskId,
            'assigned_by' => $this->assignedBy,
            'estimated_hours' => $this->estimatedHours,
            'notes' => $this->notes,
        ];
    }
}