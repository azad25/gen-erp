<?php

namespace App\Domain\Integration\Http\Controllers;

use App\Domain\Integration\Http\Requests\CreateIntegrationRequest;
use App\Domain\Integration\Http\Requests\UpdateIntegrationRequest;
use App\Domain\Integration\Http\Resources\IntegrationResource;
use App\Domain\Integration\Services\IntegrationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IntegrationController extends Controller
{
    public function __construct(
        private readonly IntegrationService $integrationService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $integrations = $this->integrationService->getAvailableIntegrations(
            filters: $request->only(['category', 'tier', 'search'])
        );

        return IntegrationResource::collection($integrations);
    }

    public function show(int $id): IntegrationResource
    {
        $integration = $this->integrationService->findById($id);

        return new IntegrationResource($integration);
    }

    public function store(CreateIntegrationRequest $request): IntegrationResource
    {
        $integration = $this->integrationService->create($request->validated());

        return new IntegrationResource($integration);
    }

    public function update(UpdateIntegrationRequest $request, int $id): IntegrationResource
    {
        $integration = $this->integrationService->update($id, $request->validated());

        return new IntegrationResource($integration);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->integrationService->delete($id);

        return response()->json(['message' => 'Integration deleted successfully.']);
    }
}
