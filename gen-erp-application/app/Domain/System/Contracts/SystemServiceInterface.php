<?php

namespace App\Domain\System\Contracts;

use App\Domain\System\Models\ImportJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for system service operations.
 */
interface SystemServiceInterface
{
    /**
     * Get paginated import jobs for a company.
     */
    public function getImportJobs(int $companyId, ?string $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a specific import job.
     */
    public function getImportJob(int $companyId, int $id): ImportJob;
}