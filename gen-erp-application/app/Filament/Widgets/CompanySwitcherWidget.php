<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class CompanySwitcherWidget extends Widget
{
    protected static string $view = 'filament.widgets.company-switcher';
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = -3;
    protected static bool $isDiscovered = false; // register manually
}
