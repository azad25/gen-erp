<?php

namespace App\Http\Controllers\Api\V1\HR;

use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeSkill;
use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Employee Skill Management
 */
class EmployeeSkillController extends Controller
{
    /**
     * Get skills for an employee
     * 
     * @param int $employeeId
     * @return JsonResponse
     */
    public function index(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $skills = EmployeeSkill::where('employee_id', $employeeId)
            ->with(['employee'])
            ->orderBy('proficiency_level', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $skills,
        ]);
    }

    /**
     * Get all skills across all employees (for company)
     * 
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();

        $skills = EmployeeSkill::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['employee'])
        ->when($request->input('skill_name'), function ($query, $skillName) {
            $query->where('skill_name', 'like', "%{$skillName}%");
        })
        ->when($request->input('min_proficiency'), function ($query, $minProficiency) {
            $query->where('proficiency_level', '>=', $minProficiency);
        })
        ->orderBy('proficiency_level', 'desc')
        ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $skills->items(),
            'meta' => [
                'current_page' => $skills->currentPage(),
                'total' => $skills->total(),
                'per_page' => $skills->perPage(),
            ],
        ]);
    }

    /**
     * Add a skill to an employee
     * 
     * @param Request $request
     * @param int $employeeId
     * @return JsonResponse
     */
    public function store(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|integer|min:1|max:5',
            'years_of_experience' => 'nullable|numeric|min:0',
            'certification' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $skill = EmployeeSkill::create([
            'employee_id' => $employeeId,
            'skill_name' => $validated['skill_name'],
            'proficiency_level' => $validated['proficiency_level'],
            'years_of_experience' => $validated['years_of_experience'] ?? null,
            'certification' => $validated['certification'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Skill added successfully',
            'data' => $skill->load('employee'),
        ], 201);
    }

    /**
     * Get a specific employee skill
     * 
     * @param int $employeeId
     * @param int $skillId
     * @return JsonResponse
     */
    public function show(int $employeeId, int $skillId): JsonResponse
    {
        $skill = EmployeeSkill::where('employee_id', $employeeId)
            ->where('id', $skillId)
            ->with(['employee'])
            ->firstOrFail();

        $this->authorize('view', $skill->employee);

        return response()->json([
            'success' => true,
            'data' => $skill,
        ]);
    }

    /**
     * Update employee skill
     * 
     * @param Request $request
     * @param int $employeeId
     * @param int $skillId
     * @return JsonResponse
     */
    public function update(Request $request, int $employeeId, int $skillId): JsonResponse
    {
        $skill = EmployeeSkill::where('employee_id', $employeeId)
            ->where('id', $skillId)
            ->firstOrFail();

        $this->authorize('update', $skill->employee);

        $validated = $request->validate([
            'skill_name' => 'sometimes|string|max:255',
            'proficiency_level' => 'sometimes|integer|min:1|max:5',
            'years_of_experience' => 'nullable|numeric|min:0',
            'certification' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $skill->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Skill updated successfully',
            'data' => $skill->load('employee'),
        ]);
    }

    /**
     * Remove a skill from an employee
     * 
     * @param int $employeeId
     * @param int $skillId
     * @return JsonResponse
     */
    public function destroy(int $employeeId, int $skillId): JsonResponse
    {
        $skill = EmployeeSkill::where('employee_id', $employeeId)
            ->where('id', $skillId)
            ->firstOrFail();

        $this->authorize('update', $skill->employee);

        $skill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Skill removed successfully',
        ]);
    }

    /**
     * Get skill statistics for company
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $companyId = CompanyContext::activeId();

        $totalSkills = EmployeeSkill::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->count();

        $uniqueSkills = EmployeeSkill::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->distinct('skill_name')->count('skill_name');

        $topSkills = EmployeeSkill::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->select('skill_name', \DB::raw('COUNT(*) as employee_count'), \DB::raw('AVG(proficiency_level) as avg_proficiency'))
        ->groupBy('skill_name')
        ->orderBy('employee_count', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_skills' => $totalSkills,
                'unique_skills' => $uniqueSkills,
                'top_skills' => $topSkills,
            ],
        ]);
    }
}
