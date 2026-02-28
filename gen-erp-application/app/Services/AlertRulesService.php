<?php

namespace App\Services;

use App\Enums\AlertOperator;
use App\Models\AlertLog;
use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Evaluates alert rules against entity changes and dispatches notifications.
 */
class AlertRulesService
{
    /**
     * Called after any model save — checks all active alert rules for that entity type.
     */
    public function evaluate(string $entityType, Model $entity): void
    {
        $companyId = $entity->company_id ?? CompanyContext::activeId();

        $rules = AlertRule::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $fieldValue = $entity->{$rule->trigger_field} ?? null;

            if ($this->checkRule($rule, $entity)) {
                $this->dispatch($rule, $entity, $fieldValue);
            }
        }
    }

    /**
     * Check a single rule against an entity.
     */
    public function checkRule(AlertRule $rule, Model $entity): bool
    {
        $fieldValue = $entity->{$rule->trigger_field} ?? null;
        $triggerValue = $rule->trigger_value;
        $operator = AlertOperator::tryFrom($rule->operator);

        if (! $operator) {
            return false;
        }

        return match ($operator) {
            AlertOperator::LT => is_numeric($fieldValue) && is_numeric($triggerValue) && $fieldValue < $triggerValue,
            AlertOperator::LTE => is_numeric($fieldValue) && is_numeric($triggerValue) && $fieldValue <= $triggerValue,
            AlertOperator::GT => is_numeric($fieldValue) && is_numeric($triggerValue) && $fieldValue > $triggerValue,
            AlertOperator::GTE => is_numeric($fieldValue) && is_numeric($triggerValue) && $fieldValue >= $triggerValue,
            AlertOperator::EQ => (string) $fieldValue === (string) $triggerValue,
            AlertOperator::NEQ => (string) $fieldValue !== (string) $triggerValue,
            AlertOperator::CONTAINS => is_string($fieldValue) && str_contains($fieldValue, $triggerValue ?? ''),
            AlertOperator::NOT_CONTAINS => is_string($fieldValue) && ! str_contains($fieldValue, $triggerValue ?? ''),
            AlertOperator::IS_NULL => $fieldValue === null || $fieldValue === '',
            AlertOperator::NOT_NULL => $fieldValue !== null && $fieldValue !== '',
        };
    }

    /**
     * Dispatch notifications for a triggered rule.
     */
    public function dispatch(AlertRule $rule, Model $entity, mixed $triggerValue): void
    {
        $companyId = $entity->company_id ?? CompanyContext::activeId();

        // Cooldown enforcement
        if ($rule->cooldown_minutes > 0) {
            $recentLog = AlertLog::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('alert_rule_id', $rule->id)
                ->where('entity_type', $rule->entity_type)
                ->where('entity_id', $entity->getKey())
                ->where('created_at', '>=', now()->subMinutes($rule->cooldown_minutes))
                ->exists();

            if ($recentLog) {
                return;
            }
        }

        // Once repeat behaviour — only fire once per entity
        if ($rule->repeat_behaviour === 'once') {
            $alreadyFired = AlertLog::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('alert_rule_id', $rule->id)
                ->where('entity_type', $rule->entity_type)
                ->where('entity_id', $entity->getKey())
                ->exists();

            if ($alreadyFired) {
                return;
            }
        }

        // Write alert log
        AlertLog::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'alert_rule_id' => $rule->id,
            'entity_type' => $rule->entity_type,
            'entity_id' => $entity->getKey(),
            'triggered_value' => (string) $triggerValue,
            'channels_sent' => $rule->channels ?? [],
            'recipients_count' => count($rule->target_roles ?? []),
        ]);

        // Update last triggered
        $rule->withoutGlobalScopes()->where('id', $rule->id)->update([
            'last_triggered_at' => now(),
        ]);

        // TODO: Phase 3+ — dispatch actual notifications (in_app, email, sms)
        Log::info('Alert rule triggered', [
            'rule_id' => $rule->id,
            'entity_type' => $rule->entity_type,
            'entity_id' => $entity->getKey(),
            'trigger_value' => $triggerValue,
            'channels' => $rule->channels,
        ]);
    }
}
