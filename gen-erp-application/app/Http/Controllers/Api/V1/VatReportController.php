<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Report\Services\Mushak61ReportService;
use App\Domain\Report\Services\Mushak62ReportService;
use App\Domain\Report\Services\Mushak66Service;
use App\Domain\Report\Services\Mushak91Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="VAT Reports",
 *     description="Bangladesh VAT/Mushak report generation"
 * )
 * REST API v1 controller for VAT Report operations.
 */
class VatReportController extends BaseApiController
{
    public function __construct(
        private Mushak61ReportService $mushak61,
        private Mushak62ReportService $mushak62,
        private Mushak66Service $mushak66,
        private Mushak91Service $mushak91
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/vat-reports/mushak-61",
     *     summary="Generate Mushak 6.1 (Purchase Register)",
     *     tags={"VAT Reports"},
     *
     *     @OA\Parameter(name="month", in="query", required=true, description="Month (1-12)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year", in="query", required=true, description="Year", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Mushak 6.1 report data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array")
     *         )
     *     )
     * )
     */
    public function mushak61(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $company = $request->user()->activeCompany();
        $data = $this->mushak61->generate($company, $validated['month'], $validated['year']);

        return $this->success([
            'report_name' => 'Mushak 6.1 - Purchase Register',
            'period' => date('F Y', mktime(0, 0, 0, $validated['month'], 1, $validated['year'])),
            'company' => [
                'name' => $company->name,
                'vat_bin' => $company->vat_bin,
            ],
            'data' => $data,
            'summary' => [
                'total_records' => count($data),
                'total_taxable_value' => array_sum(array_column($data, 'taxable_value')),
                'total_vat_amount' => array_sum(array_column($data, 'vat_amount')),
                'total_amount' => array_sum(array_column($data, 'total')),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vat-reports/mushak-62",
     *     summary="Generate Mushak 6.2 (VAT Summary)",
     *     tags={"VAT Reports"},
     *
     *     @OA\Parameter(name="month", in="query", required=true, description="Month (1-12)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year", in="query", required=true, description="Year", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Mushak 6.2 summary data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function mushak62(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $company = $request->user()->activeCompany();
        $summary = $this->mushak62->generateSummary($company, $validated['month'], $validated['year']);

        return $this->success([
            'report_name' => 'Mushak 6.2 - VAT Summary',
            'period' => date('F Y', mktime(0, 0, 0, $validated['month'], 1, $validated['year'])),
            'company' => [
                'name' => $company->name,
                'vat_bin' => $company->vat_bin,
            ],
            'summary' => $summary,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vat-reports/mushak-66",
     *     summary="Generate Mushak 6.6 (Credit Note Register)",
     *     tags={"VAT Reports"},
     *
     *     @OA\Parameter(name="month", in="query", required=true, description="Month (1-12)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year", in="query", required=true, description="Year", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Mushak 6.6 report data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array")
     *         )
     *     )
     * )
     */
    public function mushak66(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $company = $request->user()->activeCompany();
        $data = $this->mushak66->generate($company, $validated['month'], $validated['year']);

        return $this->success([
            'report_name' => 'Mushak 6.6 - Credit Note Register',
            'period' => date('F Y', mktime(0, 0, 0, $validated['month'], 1, $validated['year'])),
            'company' => [
                'name' => $company->name,
                'vat_bin' => $company->vat_bin,
            ],
            'data' => $data,
            'summary' => [
                'total_records' => count($data),
                'total_taxable_value' => array_sum(array_column($data, 'taxable_value')),
                'total_vat_amount' => array_sum(array_column($data, 'vat_amount')),
                'total_amount' => array_sum(array_column($data, 'total')),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vat-reports/mushak-91",
     *     summary="Generate Mushak 9.1 (Treasury Challan)",
     *     tags={"VAT Reports"},
     *
     *     @OA\Parameter(name="month", in="query", required=true, description="Month (1-12)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year", in="query", required=true, description="Year", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Mushak 9.1 challan data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function mushak91(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $company = $request->user()->activeCompany();
        $data = $this->mushak91->generate($company, $validated['month'], $validated['year']);

        return $this->success([
            'report_name' => 'Mushak 9.1 - Treasury Challan',
            'data' => $data,
        ]);
    }
}
