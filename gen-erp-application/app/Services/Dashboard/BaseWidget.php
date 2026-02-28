<?php

namespace App\Services\Dashboard;

use App\Models\Company;

/**
 * Abstract base for all dashboard widgets.
 */
abstract class BaseWidget
{
    public function __construct(
        protected Company $company,
        protected array $settings = [],
    ) {}

    /**
     * Returns data for rendering the widget.
     *
     * @return array<string, mixed>
     */
    abstract public function getData(): array;

    /**
     * Returns the Blade view name for this widget.
     */
    abstract public function getViewName(): string;

    /**
     * Title — override in subclass for dynamic titles.
     */
    public function getTitle(): string
    {
        return '';
    }
}
