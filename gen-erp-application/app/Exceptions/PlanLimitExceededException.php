<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a company exceeds their plan's resource limit.
 */
class PlanLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $counterKey,
        public readonly int $maxValue,
    ) {
        $label = ucfirst(str_replace('_', ' ', $counterKey));
        $limit = $maxValue === -1 ? 'unlimited' : (string) $maxValue;

        parent::__construct(
            __('Plan limit exceeded for :resource. Maximum allowed: :limit. Please upgrade your plan.', [
                'resource' => $label,
                'limit' => $limit,
            ]),
        );
    }
}
