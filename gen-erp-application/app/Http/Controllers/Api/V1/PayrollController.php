<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Payroll",
 *     description="Payroll management"
 * )
 * REST API v1 controller for Payroll operations.
 */
class PayrollController extends BaseApiController
{
    public function __construct(
        private PayrollService $payrollService
    ) {}

    /**
     * @OA\Get(
     *     path="/payroll",
     *     summary="List payslips for a month",
     *     tags={"Payroll"},
     *     @OA\Parameter(name="month", in="query", description="Month (1-12)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year", in="query", description="Year", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Payslip")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);

        $payslips = $this->payrollService->getMonthlyPayslips($month, $year);

        return $this->paginated($payslips);
    }

    /**
     * @OA\Post(
     *     path="/payroll/run",
     *     summary="Run payroll for a month",
     *     tags={"Payroll"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="month", type="integer"),
     *             @OA\Property(property="year", type="integer"),
     *             @OA\Property(property="employee_ids", type="array")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payroll run completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['exists:employees,id'],
        ]);

        $results = $this->payrollService->runPayroll(
            $validated['month'],
            $validated['year'],
            $validated['employee_ids'] ?? null
        );

        return $this->success($results, 'Payroll run completed');
    }
}
