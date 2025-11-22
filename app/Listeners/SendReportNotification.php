<?php

namespace App\Listeners;

use App\Events\ReportCreated;
use App\Models\User;
use App\Notifications\ReportCreatedNotification;

class SendReportNotification
{
    public function handle(ReportCreated $event): void
    {
        // Notify the report creator
        $event->report->user->notify(new ReportCreatedNotification($event->report));
        
        // Notify all admins
        $admins = User::role('Admin')->get();
        
        foreach ($admins as $admin) {
            $admin->notify(new ReportCreatedNotification($event->report));
        }
    }
}
