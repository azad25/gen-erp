<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\Site;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SiteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Site $site
    ) {}
}