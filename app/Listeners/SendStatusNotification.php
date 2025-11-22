<?php

namespace App\Listeners;

use App\Events\StatusChanged;
use App\Notifications\StatusChangedNotification;

class SendStatusNotification
{
    public function handle(StatusChanged $event): void
    {
        // Notify the report owner
        $event->report->user->notify(
            new StatusChangedNotification(
                $event->report,
                $event->oldStatus,
                $event->newStatus
            )
        );
    }
}
