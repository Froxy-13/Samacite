<?php

namespace App\Listeners;

use App\Events\ReportCreated;
use App\Jobs\NotifyUserJob;
use App\Models\User;

class SendReportNotification
{
    public function handle(ReportCreated $event): void
    {
        // Notify the report creator asynchronously
        NotifyUserJob::dispatch(
            $event->report->user,
            'Votre signalement a été créé avec succès',
            ['report_id' => $event->report->id, 'type' => 'report_created']
        );
        
        // Notify all admins asynchronously
        $admins = User::role('Admin')->get();
        
        foreach ($admins as $admin) {
            NotifyUserJob::dispatch(
                $admin,
                'Un nouveau signalement a été créé',
                ['report_id' => $event->report->id, 'type' => 'report_created', 'admin' => true]
            );
        }
    }
}
