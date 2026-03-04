<?php

namespace App\Domain\Logistics\Http\Controllers;

use App\Domain\Logistics\Contracts\ReturnServiceInterface;
use App\Domain\Logistics\DTOs\ReturnRequestData;
use App\Domain\Logistics\Http\Requests\CreateReturnRequest;
use App\Domain\Logistics\Http\Resources\ShipmentReturnResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReturnController extends Controller
{
    public function __construct(
        private readonly ReturnServiceInterface $returnService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'reason', 'shipment_id', 'date_from', 'date_to', 'search'
        ]);

        $returns = $this->returnService->listReturns(
            $filters,
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ShipmentReturnResource::collection($returns->items()),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ]
        ]);
    }

    public function store(CreateReturnRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['requested_by'] = auth()->id();

        // Process uploaded images
        if ($request->hasFile('images')) {
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $path = \Storage::disk('public')->put('returns/temp', $image);
                $imageUrls[] = \Storage::disk('public')->url($path);
            }
            $validatedData['images'] = $imageUrls;
        }

        $returnData = ReturnRequestData::fromArray($validatedData);
        $return = $this->returnService->requestReturn($returnData);

        return response()->json([
            'success' => true,
            'message' => __('logistics.return.requested'),
            'data' => new ShipmentReturnResource($return)
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $return = $this->returnService->getReturn($id);

        return response()->json([
            'success' => true,
            'data' => new ShipmentReturnResource($return)
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $return = $this->returnService->approveReturn($id, auth()->id());

        return response()->json([
            'success' => true,
            'message' => __('logistics.return.approved'),
            'data' => new ShipmentReturnResource($return)
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $return = $this->returnService->rejectReturn(
            $id,
            auth()->id(),
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => __('logistics.return.rejected'),
            'data' => new ShipmentReturnResource($return)
        ]);
    }

    public function markReceived(int $id): JsonResponse
    {
        $return = $this->returnService->markAsReceived($id);

        return response()->json([
            'success' => true,
            'message' => __('logistics.return.received'),
            'data' => new ShipmentReturnResource($return)
        ]);
    }

    public function processRefund(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string|in:bank_transfer,mobile_banking,cash,store_credit',
        ]);

        $return = $this->returnService->processRefund(
            $id,
            $request->amount,
            $request->method
        );

        return response()->json([
            'success' => true,
            'message' => __('logistics.return.refunded'),
            'data' => new ShipmentReturnResource($return)
        ]);
    }

    public function uploadImages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $uploadedImages = $this->returnService->uploadReturnImages($id, $request->file('images'));

        return response()->json([
            'success' => true,
            'message' => __('logistics.return.images_uploaded'),
            'data' => ['images' => $uploadedImages]
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->only(['company_id', 'date_from', 'date_to']);
        $statistics = $this->returnService->getReturnStatistics($filters);

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}