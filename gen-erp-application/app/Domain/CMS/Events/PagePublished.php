<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\Page;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagePublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Page $page
    ) {}
}