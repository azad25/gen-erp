<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payslip;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Payslips",
 *     description="Payslip management"
 * )
 * REST API v1 controller for Payslip operations.
 */
class PayslipController extends BaseApiController
{
    public function __construct(
        private PayrollService $payrollService
    ) {}

    /**
     * @OA\Get(
     *     path="/payslips",
     *     summary="List all payslips",
     *     tags={"Payslips"},
     *     @OA\Parameter(name="employee_id", in="query", description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="month", in="query", description="Month", @OA\Schema(type="integer")),
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
        $payslips = Payslip::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->get('month'), fn ($q, $m) => $q->where('month', $m))
            ->when($request->get('year'), fn ($q, $y) => $q->where('year', $y))
            ->with(['employee'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($payslips);
    }

    /**
     * @OA\Get(
     *     path="/payslips/{id}",
     *     summary="Get a specific payslip",
     *     tags={"Payslips"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Payslip ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payslip")
     *         )
     *     )
     * )
     */
    public function show(Payslip $payslip): JsonResponse
    {
        $payslip->load(['employee', 'earnings', 'deductions']);

        return $this->success($payslip);
    }

    /**
     * @OA\Post(
     *     path="/payslips",
     *     summary="Create a new payslip",
     *     tags={"Payslips"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer"),
     *             @OA\Property(property="month", type="integer"),
     *             @OA\Property(property="year", type="integer"),
     *             @OA\Property(property="basic_salary", type="integer"),
     *             @OA\Property(property="earnings", type="array"),
     *             @OA\Property(property="deductions", type="array")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payslip created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Payslip"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020'],
            'basic_salary' => ['required', 'integer', 'min:0'],
            'earnings' => ['nullable', 'array'],
            'deductions' => ['nullable', 'array'],
        ]);

        $validated['company_id'] = activeCompany()->id;

        $payslip = $this->payrollService->generatePayslip($validated);

        return $this->success($payslip->load(['employee', 'earnings', 'deductions']), 'Payslip created', 201);
    }

    public function update(Request $request, Payslip $payslip): JsonResponse
    {
        $validated = $request->validate([
            'basic_salary' => ['sometimes', 'integer', 'min:0'],
            'earnings' => ['nullable', 'array'],
            'deductions' => ['nullable', 'array'],
        ]);

        $payslip->update($validated);

        return $this->success($payslip->fresh(), 'Payslip updated');
    }

    public function destroy(Payslip $payslip): JsonResponse
    {
        $payslip->delete();

        return $this->success(null, 'Payslip deleted');
    }

    /**
     * @OA\Get(
     *     path="/payslips/{payslip}/download",
     *     summary="Download payslip PDF",
     *     tags={"Payslips"},
     *     @OA\Parameter(name="payslip", in="path", required=true, description="Payslip ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Download URL generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="download_url", type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function download(Payslip $payslip): JsonResponse
    {
        $url = $this->payrollService->getPayslipDownloadUrl($payslip);

        return $this->success(['download_url' => $url]);
    }
}
