<?php

namespace App\Domain\HR\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Performance Review - Employee performance reviews
 */
class PerformanceReview extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'review_period_start',
        'review_period_end',
        'overall_rating',
        'technical_skills_rating',
        'communication_rating',
        'teamwork_rating',
        'productivity_rating',
        'strengths',
        'areas_for_improvement',
        'goals',
        'comments',
        'status',
        'submitted_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'review_period_start' => 'date',
            'review_period_end' => 'date',
            'overall_rating' => 'integer',
            'technical_skills_rating' => 'integer',
            'communication_rating' => 'integer',
            'teamwork_rating' => 'integer',
            'productivity_rating' => 'integer',
            'submitted_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // ─── Helpers ───

    public function getAverageRating(): float
    {
        $ratings = array_filter([
            $this->technical_skills_rating,
            $this->communication_rating,
            $this->teamwork_rating,
            $this->productivity_rating,
        ]);

        if (empty($ratings)) {
            return 0;
        }

        return round(array_sum($ratings) / count($ratings), 2);
    }

    public function getRatingGrade(): string
    {
        $average = $this->getAverageRating();

        return match (true) {
            $average >= 4.5 => 'A+',
            $average >= 4.0 => 'A',
            $average >= 3.5 => 'B+',
            $average >= 3.0 => 'B',
            $average >= 2.5 => 'C+',
            $average >= 2.0 => 'C',
            $average >= 1.5 => 'D',
            default => 'F',
        };
    }

    public function getPerformanceLevel(): string
    {
        $average = $this->getAverageRating();

        return match (true) {
            $average >= 4.5 => 'Outstanding',
            $average >= 4.0 => 'Exceeds Expectations',
            $average >= 3.0 => 'Meets Expectations',
            $average >= 2.0 => 'Below Expectations',
            default => 'Unsatisfactory',
        };
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function acknowledge(): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    public function getReviewPeriodDuration(): int
    {
        return $this->review_period_start->diffInDays($this->review_period_end);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('review_period_start', '>=', $startDate)
                    ->where('review_period_end', '<=', $endDate);
    }
}