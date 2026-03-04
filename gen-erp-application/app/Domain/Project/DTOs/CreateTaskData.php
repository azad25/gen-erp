<?php

namespace App\Domain\Project\DTOs;

class CreateTaskData
{
    public function __construct(
        public int $projectId,
        public string $title,
        public ?string $description = null,
        public ?string $status = null,
        public ?string $priority = null,
        public ?string $type = null,
        public ?int $assigneeId = null,
        public ?int $reporterId = null,
        public ?int $boardId = null,
        public ?int $boardColumnId = null,
        public ?int $parentTaskId = null,
        public ?\DateTime $startDate = null,
        public ?\DateTime $dueDate = null,
        public ?float $estimatedHours = null,
        public ?int $storyPoints = null,
        public ?array $tags = null,
        public ?array $settings = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            projectId: $data['project_id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            priority: $data['priority'] ?? null,
            type: $data['type'] ?? null,
            assigneeId: $data['assignee_id'] ?? null,
            reporterId: $data['reporter_id'] ?? null,
            boardId: $data['board_id'] ?? null,
            boardColumnId: $data['board_column_id'] ?? null,
            parentTaskId: $data['parent_task_id'] ?? null,
            startDate: isset($data['start_date']) ? new \DateTime($data['start_date']) : null,
            dueDate: isset($data['due_date']) ? new \DateTime($data['due_date']) : null,
            estimatedHours: $data['estimated_hours'] ?? null,
            storyPoints: $data['story_points'] ?? null,
            tags: $data['tags'] ?? null,
            settings: $data['settings'] ?? null,
        );
    }
}