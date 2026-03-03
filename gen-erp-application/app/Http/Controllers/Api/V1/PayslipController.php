<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\HR\Services\PayrollService;
use App\Http\Resources\PayslipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        private readonly PayrollService $payrollService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/payslips",
     *     summary="List all payslips",
     *     tags={"Payslips"},
     *
     *     @OA\Parameter(name="employee_id", in="query", description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="month", in="query", description="Month", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="year", in="query", description="Year", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
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

        return $this->paginated($payslips, PayslipResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payslips/{id}",
     *     summary="Get a specific payslip",
     *     tags={"Payslips"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Payslip ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $payslip = $this->payrollService->getPayslip(activeCompany()->id, $id);
        $payslip->load(['employee', 'earnings', 'deductions']);

        return $this->success(new PayslipResource($payslip));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payslips",
     *     summary="Create a new payslip",
     *     tags={"Payslips"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="employee_id", type="integer"),
     *             @OA\Property(property="month", type="integer"),
     *             @OA\Property(property="year", type="integer"),
     *             @OA\Property(property="basic_salary", type="integer"),
     *             @OA\Property(property="earnings", type="array"),
     *             @OA\Property(property="deductions", type="array")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Payslip created",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020'],
            'basic_salary' => ['required', 'integer', 'min:0'],
            'earnings' => ['nullable', 'array'],
            'deductions' => ['nullable', 'array'],
        ]);

        $validated['company_id'] = $companyId;

        $payslip = $this->payrollService->generatePayslip($validated);

        return $this->success(new PayslipResource($payslip->load(['employee', 'earnings', 'deductions'])), 'Payslip created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payslip = $this->payrollService->getPayslip(activeCompany()->id, $id);

        $validated = $request->validate([
            'basic_salary' => ['sometimes', 'integer', 'min:0'],
            'earnings' => ['nullable', 'array'],
            'deductions' => ['nullable', 'array'],
        ]);

        $updatedPayslip = $this->payrollService->updatePayslip($payslip, $validated);

        return $this->success(new PayslipResource($updatedPayslip), 'Payslip updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $payslip = $this->payrollService->getPayslip(activeCompany()->id, $id);
        $this->payrollService->deletePayslip($payslip);

        return $this->success(null, 'Payslip deleted');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payslips/{payslip}/download",
     *     summary="Download payslip PDF",
     *     tags={"Payslips"},
     *
     *     @OA\Parameter(name="payslip", in="path", required=true, description="Payslip ID", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Download URL generated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="download_url", type="string")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function download(int $id): JsonResponse
    {
        $payslip = $this->payrollService->getPayslip(activeCompany()->id, $id);
        $url = $this->payrollService->getPayslipDownloadUrl($payslip);

        return $this->success(['download_url' => $url]);
    }
}
