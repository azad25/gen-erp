<?php

namespace App\Domain\Project\DTOs;

class UpdateTaskData
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $status = null,
        public ?string $priority = null,
        public ?string $type = null,
        public ?int $assigneeId = null,
        public ?int $boardColumnId = null,
        public ?int $parentTaskId = null,
        public ?\DateTime $startDate = null,
        public ?\DateTime $dueDate = null,
        public ?float $estimatedHours = null,
        public ?int $storyPoints = null,
        public ?int $position = null,
        public ?array $tags = null,
        public ?array $settings = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            priority: $data['priority'] ?? null,
            type: $data['type'] ?? null,
            assigneeId: $data['assignee_id'] ?? null,
            boardColumnId: $data['board_column_id'] ?? null,
            parentTaskId: $data['parent_task_id'] ?? null,
            startDate: isset($data['start_date']) ? new \DateTime($data['start_date']) : null,
            dueDate: isset($data['due_date']) ? new \DateTime($data['due_date']) : null,
            estimatedHours: $data['estimated_hours'] ?? null,
            storyPoints: $data['story_points'] ?? null,
            position: $data['position'] ?? null,
            tags: $data['tags'] ?? null,
            settings: $data['settings'] ?? null,
        );
    }
}