<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Import Jobs",
 *     description="Import job tracking"
 * )
 * REST API v1 controller for Import Job tracking.
 */
class ImportJobController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/import-jobs",
     *     summary="List all import jobs",
     *     tags={"Import Jobs"},
     *     @OA\Parameter(name="status", in="query", description="Job status", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ImportJob")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $jobs = ImportJob::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($jobs);
    }

    /**
     * @OA\Get(
     *     path="/import-jobs/{id}",
     *     summary="Get a specific import job",
     *     tags={"Import Jobs"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Import Job ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/ImportJob")
     *         )
     *     )
     * )
     */
    public function show(ImportJob $importJob): JsonResponse
    {
        return $this->success($importJob);
    }
}
