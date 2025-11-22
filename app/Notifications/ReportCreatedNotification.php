<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

class ReportCreatedNotification extends Notification
{
    public function __construct(public Report $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'report_created',
            'report_id' => $this->report->id,
            'title' => $this->report->title,
            'user_name' => $this->report->user->nom . ' ' . $this->report->user->prenom,
        ];
    }
}
