<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\HRService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Attendance",
 *     description="Attendance tracking"
 * )
 * REST API v1 controller for Attendance operations.
 */
class AttendanceController extends BaseApiController
{
    public function __construct(
        private readonly HRService $hrService
    ) {}

    /**
     * @OA\Get(
     *     path="/attendance",
     *     summary="List attendance records",
     *     tags={"Attendance"},
     *     @OA\Parameter(name="employee_id", in="query", description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date", in="query", description="Date (Y-m-d)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $attendance = Attendance::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->get('date'), fn ($q, $d) => $q->whereDate('date', $d))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['employee'])
            ->orderBy('date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($attendance);
    }

    /**
     * @OA\Get(
     *     path="/attendance/{id}",
     *     summary="Get a specific attendance record",
     *     tags={"Attendance"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Attendance ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Successful response")
     * )
     */
    public function show(Attendance $attendance): JsonResponse
    {
        $attendance->load(['employee']);

        return $this->success($attendance);
    }

    /**
     * @OA\Post(
     *     path="/attendance",
     *     summary="Mark attendance for an employee",
     *     tags={"Attendance"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="check_in", type="string"),
     *             @OA\Property(property="check_out", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Attendance marked")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:present,absent,late,half_day,leave'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::where('company_id', $companyId)->findOrFail($validated['employee_id']);
        $data = collect($validated)->except(['employee_id', 'date'])->toArray();

        $attendance = $this->hrService->markAttendance(
            $employee,
            Carbon::parse($validated['date']),
            $data,
        );

        return $this->success($attendance->load(['employee']), __('Attendance marked'), 201);
    }

    /**
     * Attendance can only be updated for notes/check times.
     */
    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:present,absent,late,half_day,leave'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->update($validated);

        return $this->success($attendance->fresh()->load(['employee']), __('Attendance updated'));
    }

    /**
     * Attendance records cannot be deleted.
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        return $this->error(__('Attendance records cannot be deleted.'), 403);
    }

    /**
     * @OA\Post(
     *     path="/attendance/bulk",
     *     summary="Bulk mark attendance",
     *     tags={"Attendance"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="records", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Attendance marked")
     * )
     */
    public function bulk(Request $request): JsonResponse
    {
        $companyId = activeCompany()->id;

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'records.*.status' => ['required', 'string', 'in:present,absent,late,half_day,leave'],
            'records.*.check_in' => ['nullable', 'date_format:H:i'],
            'records.*.check_out' => ['nullable', 'date_format:H:i'],
        ]);

        $result = $this->hrService->bulkMarkAttendance(
            activeCompany(),
            Carbon::parse($validated['date']),
            $validated['records'],
        );

        return $this->success($result, __('Bulk attendance marked'), 201);
    }
}
