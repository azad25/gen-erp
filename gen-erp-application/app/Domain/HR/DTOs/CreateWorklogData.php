<?php

namespace App\Domain\HR\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for worklog creation
 */
class CreateWorklogData
{
    public function __construct(
        public readonly int $employeeId,
        public readonly Carbon $logDate,
        public readonly ?string $summary = null,
        public readonly ?string $mood = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'],
            logDate: Carbon::parse($data['log_date']),
            summary: $data['summary'] ?? null,
            mood: $data['mood'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'log_date' => $this->logDate->toDateString(),
            'summary' => $this->summary,
            'mood' => $this->mood,
        ];
    }
}