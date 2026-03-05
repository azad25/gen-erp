<?php

namespace App\Http\Controllers\Api\V1\HR;

use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\PerformanceReview;
use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Performance Review Management
 */
class PerformanceReviewController extends Controller
{
    /**
     * Get performance reviews
     * 
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();

        $reviews = PerformanceReview::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['employee', 'reviewer'])
        ->when($request->input('employee_id'), function ($query, $employeeId) {
            $query->where('employee_id', $employeeId);
        })
        ->when($request->input('reviewer_id'), function ($query, $reviewerId) {
            $query->where('reviewer_id', $reviewerId);
        })
        ->when($request->input('status'), function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($request->input('review_period'), function ($query, $period) {
            $query->where('review_period', $period);
        })
        ->orderBy('review_date', 'desc')
        ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
            ],
        ]);
    }

    /**
     * Get reviews for a specific employee
     * 
     * @param int $employeeId
     * @return JsonResponse
     */
    public function employeeReviews(int $employeeId): JsonResponse
    {
        $employee = Employee::findOrFail($employeeId);
        
        $this->authorize('view', $employee);

        $reviews = PerformanceReview::where('employee_id', $employeeId)
            ->with(['reviewer'])
            ->orderBy('review_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Create a performance review
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'reviewer_id' => 'required|exists:users,id',
            'review_period' => 'required|string|max:50',
            'review_date' => 'required|date',
            'overall_rating' => 'required|numeric|min:1|max:5',
            'technical_skills_rating' => 'nullable|numeric|min:1|max:5',
            'communication_rating' => 'nullable|numeric|min:1|max:5',
            'teamwork_rating' => 'nullable|numeric|min:1|max:5',
            'leadership_rating' => 'nullable|numeric|min:1|max:5',
            'punctuality_rating' => 'nullable|numeric|min:1|max:5',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'goals' => 'nullable|string',
            'comments' => 'nullable|string',
            'status' => 'required|in:draft,submitted,acknowledged',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorize('update', $employee);

        $review = PerformanceReview::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Performance review created successfully',
            'data' => $review->load(['employee', 'reviewer']),
        ], 201);
    }

    /**
     * Get a specific performance review
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $review = PerformanceReview::with(['employee', 'reviewer'])
            ->findOrFail($id);

        $this->authorize('view', $review->employee);

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    /**
     * Update a performance review
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = PerformanceReview::findOrFail($id);

        $this->authorize('update', $review->employee);

        $validated = $request->validate([
            'review_period' => 'sometimes|string|max:50',
            'review_date' => 'sometimes|date',
            'overall_rating' => 'sometimes|numeric|min:1|max:5',
            'technical_skills_rating' => 'nullable|numeric|min:1|max:5',
            'communication_rating' => 'nullable|numeric|min:1|max:5',
            'teamwork_rating' => 'nullable|numeric|min:1|max:5',
            'leadership_rating' => 'nullable|numeric|min:1|max:5',
            'punctuality_rating' => 'nullable|numeric|min:1|max:5',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'goals' => 'nullable|string',
            'comments' => 'nullable|string',
            'status' => 'sometimes|in:draft,submitted,acknowledged',
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Performance review updated successfully',
            'data' => $review->load(['employee', 'reviewer']),
        ]);
    }

    /**
     * Delete a performance review
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $review = PerformanceReview::findOrFail($id);

        $this->authorize('update', $review->employee);

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Performance review deleted successfully',
        ]);
    }

    /**
     * Submit a review (change status from draft to submitted)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function submit(int $id): JsonResponse
    {
        $review = PerformanceReview::findOrFail($id);

        $this->authorize('update', $review->employee);

        $review->update(['status' => 'submitted']);

        return response()->json([
            'success' => true,
            'message' => 'Performance review submitted successfully',
            'data' => $review->load(['employee', 'reviewer']),
        ]);
    }

    /**
     * Acknowledge a review (employee acknowledges they've read it)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function acknowledge(int $id): JsonResponse
    {
        $review = PerformanceReview::findOrFail($id);

        $this->authorize('view', $review->employee);

        $review->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Performance review acknowledged successfully',
            'data' => $review->load(['employee', 'reviewer']),
        ]);
    }

    /**
     * Get performance statistics
     * 
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();

        $totalReviews = PerformanceReview::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->count();

        $averageRating = PerformanceReview::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->avg('overall_rating');

        $statusBreakdown = PerformanceReview::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->select('status', \DB::raw('COUNT(*) as count'))
        ->groupBy('status')
        ->get();

        $topPerformers = PerformanceReview::whereHas('employee', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->with(['employee'])
        ->orderBy('overall_rating', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 2),
                'status_breakdown' => $statusBreakdown,
                'top_performers' => $topPerformers,
            ],
        ]);
    }
}
