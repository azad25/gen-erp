<?php

namespace App\Domain\CRM\Contracts;

use App\Domain\CRM\Models\Pipeline;
use App\Domain\CRM\Models\PipelineStage;
use Illuminate\Database\Eloquent\Collection;

interface PipelineServiceInterface
{
    public function create(array $data, int $companyId, int $createdBy): Pipeline;
    
    public function update(Pipeline $pipeline, array $data): Pipeline;
    
    public function delete(Pipeline $pipeline): bool;
    
    public function findById(int $id, int $companyId): ?Pipeline;
    
    public function findByUuid(string $uuid, int $companyId): ?Pipeline;
    
    public function getForCompany(int $companyId): Collection;
    
    public function getActive(int $companyId): Collection;
    
    public function getDefault(int $companyId): ?Pipeline;
    
    public function setAsDefault(Pipeline $pipeline): Pipeline;
    
    public function activate(Pipeline $pipeline): Pipeline;
    
    public function deactivate(Pipeline $pipeline): Pipeline;
    
    public function createStage(Pipeline $pipeline, array $data, int $createdBy): PipelineStage;
    
    public function updateStage(PipelineStage $stage, array $data): PipelineStage;
    
    public function deleteStage(PipelineStage $stage): bool;
    
    public function reorderStages(Pipeline $pipeline, array $stageOrder): void;
    
    public function duplicatePipeline(Pipeline $pipeline, string $newName, int $createdBy): Pipeline;
    
    public function getPipelineMetrics(Pipeline $pipeline): array;
}