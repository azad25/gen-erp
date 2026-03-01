<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Attendance;
use App\Services\HRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Attendance",
 *     description="Attendance management"
 * )
 * REST API v1 controller for Attendance operations.
 */
class AttendanceController extends BaseApiController
{
    public function __construct(
        private HRService $hrService
    ) {}

    /**
     * @OA\Get(
     *     path="/attendance",
     *     summary="List all attendance records",
     *     tags={"Attendance"},
     *     @OA\Parameter(name="employee_id", in="query", description="Employee ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="date", in="query", description="Date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="status", in="query", description="Status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/Attendance")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $attendance = Attendance::query()
            ->when($request->get('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->when($request->get('date'), fn ($q, $d) => $q->where('attendance_date', $d))
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['employee'])
            ->orderBy('attendance_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($attendance);
    }

    /**
     * @OA\Get(
     *     path="/attendance/{id}",
     *     summary="Get a specific attendance record",
     *     tags={"Attendance"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Attendance ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Attendance")
     *         )
     *     )
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
     *     summary="Create a new attendance record",
     *     tags={"Attendance"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="employee_id", type="integer"),
     *             @OA\Property(property="attendance_date", type="string", format="date"),
     *             @OA\Property(property="check_in", type="string", format="time"),
     *             @OA\Property(property="check_out", type="string", format="time"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attendance created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/Attendance"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'attendance_date' => ['required', 'date'],
            'check_in' => ['required', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;

        $attendance = $this->hrService->markAttendance($validated);

        return $this->success($attendance->load(['employee']), 'Attendance marked', 201);
    }

    public function update(Request $request, Attendance $attendance): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['sometimes', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['sometimes', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $attendance->update($validated);

        return $this->success($attendance->fresh(), 'Attendance updated');
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();

        return $this->success(null, 'Attendance deleted');
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
     *             @OA\Property(property="attendance", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk attendance marked",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function bulkMark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*.employee_id' => ['required', 'exists:employees,id'],
            'attendance.*.check_in' => ['required', 'date_format:H:i:s'],
            'attendance.*.check_out' => ['nullable', 'date_format:H:i:s'],
            'attendance.*.status' => ['required', 'string'],
        ]);

        $validated['company_id'] = activeCompany()?->id;

        $results = $this->hrService->bulkMarkAttendance($validated);

        return $this->success($results, 'Bulk attendance marked');
    }
}
