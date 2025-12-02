<?php

namespace App\Events;

use App\Models\Like;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportLiked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Like $like)
    {
    }
}
