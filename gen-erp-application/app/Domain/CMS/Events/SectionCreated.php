<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\Section;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SectionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Section $section
    ) {}
}