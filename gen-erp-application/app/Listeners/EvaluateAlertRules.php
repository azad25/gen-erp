<?php

namespace App\Listeners;

use App\Events\ModelSaved;
use App\Services\AlertRulesService;

/**
 * Listens for ModelSaved events and evaluates alert rules.
 */
class EvaluateAlertRules
{
    public function __construct(
        private readonly AlertRulesService $alertRulesService,
    ) {}

    public function handle(ModelSaved $event): void
    {
        $this->alertRulesService->evaluate($event->entityType, $event->entity);
    }
}
