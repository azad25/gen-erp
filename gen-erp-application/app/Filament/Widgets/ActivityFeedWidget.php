<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ActivityFeedWidget extends Widget
{
    protected static string $view = 'filament.widgets.activity-feed-widget';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];
}
