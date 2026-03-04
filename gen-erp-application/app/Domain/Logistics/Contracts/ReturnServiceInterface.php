<?php

namespace App\Domain\Logistics\Contracts;

use App\Domain\Logistics\DTOs\ReturnRequestData;
use App\Domain\Logistics\Models\ShipmentReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReturnServiceInterface
{
    /**
     * Request a return for a shipment
     */
    public function requestReturn(ReturnRequestData $data): ShipmentReturn;

    /**
     * Approve a return request
     */
    public function approveReturn(int $returnId, int $approvedBy): ShipmentReturn;

    /**
     * Reject a return request
     */
    public function rejectReturn(int $returnId, int $rejectedBy, string $reason = null): ShipmentReturn;

    /**
     * Process refund for a return
     */
    public function processRefund(int $returnId, float $amount, string $method): ShipmentReturn;

    /**
     * Mark return as received
     */
    public function markAsReceived(int $returnId): ShipmentReturn;

    /**
     * Get return by ID
     */
    public function getReturn(int $returnId): ShipmentReturn;

    /**
     * List returns with filters
     */
    public function listReturns(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Upload return images
     */
    public function uploadReturnImages(int $returnId, array $images): array;

    /**
     * Get return statistics
     */
    public function getReturnStatistics(array $filters = []): array;
}