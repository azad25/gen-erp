<?php

namespace App\Domain\Integration\Http\Controllers;

use App\Domain\Integration\Http\Requests\InstallIntegrationRequest;
use App\Domain\Integration\Http\Requests\UpdateCompanyIntegrationRequest;
use App\Domain\Integration\Http\Resources\CompanyIntegrationResource;
use App\Domain\Integration\Services\CompanyIntegrationService;
use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyIntegrationController extends Controller
{
    public function __construct(
        private readonly CompanyIntegrationService $companyIntegrationService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = CompanyContext::activeId();
        
        $integrations = $this->companyIntegrationService->getCompanyIntegrations(
            companyId: $companyId,
            filters: $request->only(['status', 'search'])
        );

        return CompanyIntegrationResource::collection($integrations);
    }

    public function show(int $id): CompanyIntegrationResource
    {
        $companyId = CompanyContext::activeId();
        $integration = $this->companyIntegrationService->findById($id, $companyId);

        return new CompanyIntegrationResource($integration);
    }

    public function store(InstallIntegrationRequest $request): CompanyIntegrationResource
    {
        $companyId = CompanyContext::activeId();
        
        $integration = $this->companyIntegrationService->install(
            companyId: $companyId,
            integrationId: $request->integer('integration_id'),
            config: $request->input('config', [])
        );

        return new CompanyIntegrationResource($integration);
    }

    public function update(UpdateCompanyIntegrationRequest $request, int $id): CompanyIntegrationResource
    {
        $companyId = CompanyContext::activeId();
        
        $integration = $this->companyIntegrationService->updateConfig(
            id: $id,
            companyId: $companyId,
            config: $request->input('config', []),
            fieldMaps: $request->input('field_maps', [])
        );

        return new CompanyIntegrationResource($integration);
    }

    public function destroy(int $id): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        $this->companyIntegrationService->uninstall($id, $companyId);

        return response()->json(['message' => 'Integration uninstalled successfully.']);
    }

    public function activate(int $id): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        $this->companyIntegrationService->activate($id, $companyId);

        return response()->json(['message' => 'Integration activated successfully.']);
    }

    public function deactivate(int $id): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        $this->companyIntegrationService->deactivate($id, $companyId);

        return response()->json(['message' => 'Integration deactivated successfully.']);
    }

    public function sync(int $id): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        $this->companyIntegrationService->triggerSync($id, $companyId);

        return response()->json(['message' => 'Sync triggered successfully.']);
    }
}
