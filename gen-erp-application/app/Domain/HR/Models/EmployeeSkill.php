<?php

namespace App\Domain\HR\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Skill - Skills matrix for employees
 */
class EmployeeSkill extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'skill_name',
        'proficiency_level',
        'years_of_experience',
        'is_certified',
        'last_used_date',
    ];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'integer',
            'is_certified' => 'boolean',
            'last_used_date' => 'date',
        ];
    }

    // ─── Relationships ───

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ─── Helpers ───

    public function getProficiencyScore(): int
    {
        $scores = [
            'beginner' => 25,
            'intermediate' => 50,
            'advanced' => 75,
            'expert' => 100,
        ];

        return $scores[$this->proficiency_level] ?? 0;
    }

    public function isSkillCurrent(): bool
    {
        if (!$this->last_used_date) {
            return false;
        }

        // Consider skill current if used within last 2 years
        return $this->last_used_date->diffInYears(now()) <= 2;
    }

    public function getSkillAge(): int
    {
        if (!$this->last_used_date) {
            return 0;
        }

        return $this->last_used_date->diffInYears(now());
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_date' => now()]);
    }

    public function getDisplayName(): string
    {
        $name = $this->skill_name;
        
        if ($this->is_certified) {
            $name .= ' (Certified)';
        }
        
        return $name;
    }

    public function getExperienceLevel(): string
    {
        $years = $this->years_of_experience;
        
        if ($years >= 10) {
            return 'Senior';
        } elseif ($years >= 5) {
            return 'Mid-level';
        } elseif ($years >= 2) {
            return 'Junior';
        } else {
            return 'Entry-level';
        }
    }
}