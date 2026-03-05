<?php

namespace App\Domain\POS\Actions;

use App\Domain\POS\DTOs\CloseSessionData;
use App\Domain\POS\Events\POSSessionClosed;
use App\Domain\POS\Exceptions\SessionClosedException;
use App\Domain\POS\Models\POSSession;
use App\Domain\POS\Models\POSSale;

class ClosePOSSessionAction
{
    public function execute(CloseSessionData $data): POSSession
    {
        $session = POSSession::findOrFail($data->sessionId);

        if (!$session->isOpen()) {
            throw new SessionClosedException('Session is already closed.');
        }

        // Calculate expected cash
        $cashSales = POSSale::where('pos_session_id', $session->id)
            ->where('status', 'completed')
            ->sum('total_amount');

        $cashRefunds = POSSale::where('pos_session_id', $session->id)
            ->where('status', 'refunded')
            ->sum('total_amount');

        $expectedCash = $session->opening_cash + $cashSales - $cashRefunds;

        $session->update([
            'closed_by' => $data->closedBy,
            'closing_cash' => $data->closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $data->closingCash - $expectedCash,
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $data->notes,
        ]);

        event(new POSSessionClosed($session));

        return $session->fresh();
    }
}
