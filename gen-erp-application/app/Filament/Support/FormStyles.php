<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;

/**
 * Enterprise-level form styling utilities for consistent UI across all resources.
 */
class FormStyles
{
    /**
     * Create a modern section with gradient header.
     */
    public static function section(string $heading, array $schema): Section
    {
        return Section::make($heading)
            ->schema($schema)
            ->columns(2)
            ->collapsible()
            ->description('')
            ->extraAttributes([
                'class' => 'modern-form-section',
            ]);
    }

    /**
     * Create a compact section for related fields.
     */
    public static function compactSection(string $heading, array $schema): Section
    {
        return Section::make($heading)
            ->schema($schema)
            ->columns(3)
            ->compact()
            ->description('')
            ->extraAttributes([
                'class' => 'modern-form-section-compact',
            ]);
    }

    /**
     * Create modern tabs with gradient active state.
     */
    public static function tabs(array $tabs): Tabs
    {
        return Tabs::make('Tabs')
            ->tabs($tabs)
            ->extraAttributes([
                'class' => 'modern-tabs',
            ]);
    }

    /**
     * Get standard column configuration for forms.
     */
    public static function columns(): array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }

    /**
     * Get compact column configuration.
     */
    public static function compactColumns(): array
    {
        return [
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
        ];
    }
}
