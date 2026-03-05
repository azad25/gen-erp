<?php

namespace App\Domain\POS\Actions;

use App\Domain\POS\DTOs\OpenSessionData;
use App\Domain\POS\Events\POSSessionOpened;
use App\Domain\POS\Exceptions\SessionAlreadyOpenException;
use App\Domain\POS\Models\POSSession;

class OpenPOSSessionAction
{
    public function execute(OpenSessionData $data): POSSession
    {
        $existingOpen = POSSession::where('branch_id', $data->branchId)
            ->where('status', 'open')
            ->exists();

        if ($existingOpen) {
            throw new SessionAlreadyOpenException();
        }

        $session = POSSession::create($data->toArray());

        event(new POSSessionOpened($session));

        return $session;
    }
}
