<?php

namespace App\Domain\POS\Http\Controllers;

use App\Domain\POS\Contracts\POSServiceInterface;
use App\Domain\POS\DTOs\OpenSessionData;
use App\Domain\POS\DTOs\CloseSessionData;
use App\Domain\POS\Http\Requests\OpenSessionRequest;
use App\Domain\POS\Http\Requests\CloseSessionRequest;
use App\Domain\POS\Http\Resources\POSSessionResource;
use App\Domain\POS\Models\POSSession;
use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSSessionController extends Controller
{
    public function __construct(
        private readonly POSServiceInterface $posService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        
        $sessions = $this->posService->getSessionHistory($companyId, $request->all());

        return response()->json([
            'success' => true,
            'data' => POSSessionResource::collection($sessions),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'total' => $sessions->total(),
                'per_page' => $sessions->perPage(),
            ],
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $companyId = CompanyContext::activeId();
        
        $sessions = $this->posService->getActiveSessions($companyId);

        return response()->json([
            'success' => true,
            'data' => POSSessionResource::collection($sessions),
        ]);
    }

    public function store(OpenSessionRequest $request): JsonResponse
    {
        try {
            $data = new OpenSessionData(
                companyId: CompanyContext::activeId(),
                branchId: $request->input('branch_id'),
                openedBy: $request->user()->id,
                openingCash: $request->input('opening_cash'),
                notes: $request->input('notes'),
            );

            $session = $this->posService->openSession($data);

            return response()->json([
                'success' => true,
                'message' => 'POS session opened successfully.',
                'data' => new POSSessionResource($session),
            ], 201);
        } catch (\App\Domain\POS\Exceptions\SessionAlreadyOpenException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(POSSession $session): JsonResponse
    {
        $session->load(['branch', 'openedBy', 'closedBy', 'sales']);

        return response()->json([
            'success' => true,
            'data' => new POSSessionResource($session),
        ]);
    }

    public function close(CloseSessionRequest $request, POSSession $session): JsonResponse
    {
        $data = new CloseSessionData(
            sessionId: $session->id,
            closedBy: $request->user()->id,
            closingCash: $request->input('closing_cash'),
            notes: $request->input('notes'),
        );

        $closedSession = $this->posService->closeSession($data);

        return response()->json([
            'success' => true,
            'message' => 'POS session closed successfully.',
            'data' => new POSSessionResource($closedSession),
        ]);
    }

    public function summary(POSSession $session): JsonResponse
    {
        $summary = $this->posService->getSessionSummary($session);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
