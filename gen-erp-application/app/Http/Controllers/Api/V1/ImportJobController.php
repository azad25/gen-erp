<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\System\Contracts\SystemServiceInterface;
use App\Http\Resources\ImportJobResource;
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
    public function __construct(
        private readonly SystemServiceInterface $systemService
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/import-jobs",
     *     summary="List all import jobs",
     *     tags={"Import Jobs"},
     *
     *     @OA\Parameter(name="status", in="query", description="Job status", @OA\Schema(type="string")),
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
        $jobs = $this->systemService->getImportJobs(
            activeCompany()->id,
            $request->get('status'),
            $request->integer('per_page', 15)
        );

        return $this->paginated($jobs, ImportJobResource::class);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/import-jobs/{id}",
     *     summary="Get a specific import job",
     *     tags={"Import Jobs"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Import Job ID", @OA\Schema(type="integer")),
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
        $importJob = $this->systemService->getImportJob(activeCompany()->id, $id);

        return $this->success(new ImportJobResource($importJob));
    }
}
