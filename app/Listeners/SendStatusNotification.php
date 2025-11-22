<?php

namespace App\Listeners;

use App\Events\StatusChanged;
use App\Jobs\NotifyUserJob;

class SendStatusNotification
{
    public function handle(StatusChanged $event): void
    {
        // Notify the report owner asynchronously
        NotifyUserJob::dispatch(
            $event->report->user,
            "Le statut de votre signalement a changé de {$event->oldStatus} à {$event->newStatus}",
            [
                'report_id' => $event->report->id,
                'type' => 'status_changed',
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus
            ]
        );
    }
}
