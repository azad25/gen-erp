<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Report generation"
 * )
 * REST API v1 controller for Report operations.
 */
class ReportController extends BaseApiController
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *     path="/reports",
     *     summary="List available reports",
     *     tags={"Reports"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $reports = [
            [
                'id' => 'trial_balance',
                'name' => 'Trial Balance',
                'type' => 'financial',
                'description' => 'Shows debit/credit balances for all accounts',
            ],
            [
                'id' => 'profit_loss',
                'name' => 'Profit & Loss Statement',
                'type' => 'financial',
                'description' => 'Income statement showing revenue and expenses',
            ],
            [
                'id' => 'balance_sheet',
                'name' => 'Balance Sheet',
                'type' => 'financial',
                'description' => 'Assets, liabilities, and equity statement',
            ],
            [
                'id' => 'custom',
                'name' => 'Custom Report Builder',
                'type' => 'custom',
                'description' => 'Build custom reports with drag-and-drop interface',
            ],
        ];

        return $this->success($reports);
    }

    /**
     * @OA\Post(
     *     path="/reports/generate",
     *     summary="Generate a report",
     *     tags={"Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string"),
     *             @OA\Property(property="parameters", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_id' => ['required', 'string'],
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['required', 'string'],
            'filters.*.operator' => ['required', 'string'],
            'filters.*.value' => ['required'],
        ]);

        $result = $this->reportService->generateReport($validated['report_id'], $validated['filters'] ?? []);

        return $this->success($result);
    }
}
