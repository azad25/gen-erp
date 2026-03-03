<?php

namespace App\Domain\Shared\Jobs;

use App\Domain\Shared\Commands\CommandInterface;
use App\Domain\Shared\Bus\CommandBus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for executing commands asynchronously.
 */
class AsyncCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    public array $backoff = [30, 60, 120]; // Exponential backoff

    public function __construct(
        private readonly CommandInterface $command
    ) {
        $this->onQueue('commands');
    }

    public function handle(CommandBus $commandBus): void
    {
        try {
            Log::info('Executing async command', [
                'command_id' => $this->command->getCommandId(),
                'command_type' => get_class($this->command),
                'initiated_by' => $this->command->getInitiatedBy(),
            ]);

            $result = $commandBus->execute($this->command);

            Log::info('Async command completed successfully', [
                'command_id' => $this->command->getCommandId(),
                'command_type' => get_class($this->command),
            ]);

        } catch (\Exception $e) {
            Log::error('Async command failed', [
                'command_id' => $this->command->getCommandId(),
                'command_type' => get_class($this->command),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Async command permanently failed', [
            'command_id' => $this->command->getCommandId(),
            'command_type' => get_class($this->command),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Could dispatch a failure notification event here
    }
}