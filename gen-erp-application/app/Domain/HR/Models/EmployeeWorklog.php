<?php

namespace App\Domain\HR\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Worklog - Daily work summary for employees
 */
class EmployeeWorklog extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'log_date',
        'total_hours',
        'billable_hours',
        'tasks_completed',
        'summary',
        'mood',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'total_hours' => 'decimal:2',
            'billable_hours' => 'decimal:2',
            'tasks_completed' => 'integer',
        ];
    }

    // ─── Relationships ───

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ─── Helpers ───

    public function getProductivityScore(): int
    {
        $baseScore = 50;
        
        // Add points for hours worked
        if ($this->total_hours >= 8) {
            $baseScore += 20;
        } elseif ($this->total_hours >= 6) {
            $baseScore += 10;
        }
        
        // Add points for tasks completed
        $baseScore += min(20, $this->tasks_completed * 5);
        
        // Add points for mood
        $moodScores = [
            'excellent' => 10,
            'good' => 5,
            'neutral' => 0,
            'tired' => -5,
            'stressed' => -10,
        ];
        
        $baseScore += $moodScores[$this->mood] ?? 0;
        
        return max(0, min(100, $baseScore));
    }

    public function getBillablePercentage(): int
    {
        if ($this->total_hours <= 0) {
            return 0;
        }
        
        return (int) round(($this->billable_hours / $this->total_hours) * 100);
    }

    public function updateFromTimeEntries(): void
    {
        $timeEntries = $this->employee->timeEntries()
            ->where('entry_date', $this->log_date)
            ->get();

        $totalHours = $timeEntries->sum('hours');
        $billableHours = $timeEntries->where('is_billable', true)->sum('hours');
        
        $tasksCompleted = $this->employee->employeeTasks()
            ->whereDate('completed_at', $this->log_date)
            ->count();

        $this->update([
            'total_hours' => $totalHours,
            'billable_hours' => $billableHours,
            'tasks_completed' => $tasksCompleted,
        ]);
    }
}