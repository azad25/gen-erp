<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Models\CostCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CostCenterController extends Controller
{
    /**
     * Display a listing of cost centers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CostCenter::query()
            ->with(['manager:id,name'])
            ->where('company_id', $request->user()->company_id);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $isActive = $request->get('status') === 'active';
            $query->where('is_active', $isActive);
        }

        // Order by code
        $query->orderBy('code');

        $costCenters = $query->paginate($request->get('per_page', 15));

        // Transform the data to include manager name
        $costCenters->getCollection()->transform(function ($costCenter) {
            return [
                'id' => $costCenter->id,
                'code' => $costCenter->code,
                'name' => $costCenter->name,
                'description' => $costCenter->description,
                'manager_id' => $costCenter->manager_id,
                'manager_name' => $costCenter->manager?->name,
                'budget' => $costCenter->budget,
                'is_active' => $costCenter->is_active,
                'created_at' => $costCenter->created_at,
                'updated_at' => $costCenter->updated_at,
            ];
        });

        return response()->json($costCenters);
    }

    /**
     * Store a newly created cost center.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('cost_centers')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'manager_id' => 'nullable|exists:employees,id',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $costCenter = CostCenter::create([
            'company_id' => $request->user()->company_id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'budget' => $validated['budget'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $costCenter->load('manager:id,name');

        return response()->json([
            'message' => 'Cost center created successfully',
            'data' => [
                'id' => $costCenter->id,
                'code' => $costCenter->code,
                'name' => $costCenter->name,
                'description' => $costCenter->description,
                'manager_id' => $costCenter->manager_id,
                'manager_name' => $costCenter->manager?->name,
                'budget' => $costCenter->budget,
                'is_active' => $costCenter->is_active,
                'created_at' => $costCenter->created_at,
                'updated_at' => $costCenter->updated_at,
            ],
        ], 201);
    }

    /**
     * Display the specified cost center.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $costCenter = CostCenter::where('company_id', $request->user()->company_id)
            ->with(['manager:id,name'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $costCenter->id,
                'code' => $costCenter->code,
                'name' => $costCenter->name,
                'description' => $costCenter->description,
                'manager_id' => $costCenter->manager_id,
                'manager_name' => $costCenter->manager?->name,
                'budget' => $costCenter->budget,
                'is_active' => $costCenter->is_active,
                'created_at' => $costCenter->created_at,
                'updated_at' => $costCenter->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified cost center.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $costCenter = CostCenter::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('cost_centers')->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->user()->company_id);
                })->ignore($costCenter->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'manager_id' => 'nullable|exists:employees,id',
            'budget' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $costCenter->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'budget' => $validated['budget'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $costCenter->load('manager:id,name');

        return response()->json([
            'message' => 'Cost center updated successfully',
            'data' => [
                'id' => $costCenter->id,
                'code' => $costCenter->code,
                'name' => $costCenter->name,
                'description' => $costCenter->description,
                'manager_id' => $costCenter->manager_id,
                'manager_name' => $costCenter->manager?->name,
                'budget' => $costCenter->budget,
                'is_active' => $costCenter->is_active,
                'created_at' => $costCenter->created_at,
                'updated_at' => $costCenter->updated_at,
            ],
        ]);
    }

    /**
     * Remove the specified cost center.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $costCenter = CostCenter::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        // Check if cost center is being used in journal entries
        $usageCount = \DB::table('journal_entry_lines')
            ->where('cost_center_id', $costCenter->id)
            ->count();

        if ($usageCount > 0) {
            return response()->json([
                'message' => 'Cannot delete cost center as it is being used in journal entries',
                'error' => 'Cost center is in use',
            ], 422);
        }

        $costCenter->delete();

        return response()->json([
            'message' => 'Cost center deleted successfully',
        ]);
    }

    /**
     * Get cost centers for dropdown/select options.
     */
    public function options(Request $request): JsonResponse
    {
        $costCenters = CostCenter::where('company_id', $request->user()->company_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json([
            'data' => $costCenters->map(function ($costCenter) {
                return [
                    'id' => $costCenter->id,
                    'code' => $costCenter->code,
                    'name' => $costCenter->name,
                    'label' => "{$costCenter->code} - {$costCenter->name}",
                ];
            }),
        ]);
    }
}