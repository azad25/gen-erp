<?php

namespace App\Domain\HR\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for performance reviews
 */
class PerformanceReviewData
{
    public function __construct(
        public readonly int $employeeId,
        public readonly int $reviewerId,
        public readonly Carbon $reviewPeriodStart,
        public readonly Carbon $reviewPeriodEnd,
        public readonly ?int $overallRating = null,
        public readonly ?int $technicalSkillsRating = null,
        public readonly ?int $communicationRating = null,
        public readonly ?int $teamworkRating = null,
        public readonly ?int $productivityRating = null,
        public readonly ?string $strengths = null,
        public readonly ?string $areasForImprovement = null,
        public readonly ?string $goals = null,
        public readonly ?string $comments = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'],
            reviewerId: $data['reviewer_id'],
            reviewPeriodStart: Carbon::parse($data['review_period_start']),
            reviewPeriodEnd: Carbon::parse($data['review_period_end']),
            overallRating: $data['overall_rating'] ?? null,
            technicalSkillsRating: $data['technical_skills_rating'] ?? null,
            communicationRating: $data['communication_rating'] ?? null,
            teamworkRating: $data['teamwork_rating'] ?? null,
            productivityRating: $data['productivity_rating'] ?? null,
            strengths: $data['strengths'] ?? null,
            areasForImprovement: $data['areas_for_improvement'] ?? null,
            goals: $data['goals'] ?? null,
            comments: $data['comments'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'reviewer_id' => $this->reviewerId,
            'review_period_start' => $this->reviewPeriodStart->toDateString(),
            'review_period_end' => $this->reviewPeriodEnd->toDateString(),
            'overall_rating' => $this->overallRating,
            'technical_skills_rating' => $this->technicalSkillsRating,
            'communication_rating' => $this->communicationRating,
            'teamwork_rating' => $this->teamworkRating,
            'productivity_rating' => $this->productivityRating,
            'strengths' => $this->strengths,
            'areas_for_improvement' => $this->areasForImprovement,
            'goals' => $this->goals,
            'comments' => $this->comments,
        ];
    }
}