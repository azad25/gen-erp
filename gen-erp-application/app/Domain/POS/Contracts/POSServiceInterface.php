<?php

namespace App\Domain\POS\Contracts;

use App\Domain\POS\DTOs\CreatePOSSaleData;
use App\Domain\POS\DTOs\OpenSessionData;
use App\Domain\POS\DTOs\CloseSessionData;
use App\Domain\POS\Models\POSSale;
use App\Domain\POS\Models\POSSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface POSServiceInterface
{
    public function openSession(OpenSessionData $data): POSSession;
    
    public function closeSession(CloseSessionData $data): POSSession;
    
    public function createSale(CreatePOSSaleData $data): POSSale;
    
    public function voidSale(POSSale $sale): POSSale;
    
    public function getSessionSummary(POSSession $session): array;
    
    public function getActiveSessions(int $companyId): LengthAwarePaginator;
    
    public function getSessionHistory(int $companyId, array $filters = []): LengthAwarePaginator;
    
    public function getSales(int $sessionId): LengthAwarePaginator;
}
