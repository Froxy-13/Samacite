<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

class StatusChangedNotification extends Notification
{
    public function __construct(
        public Report $report,
        public string $oldStatus,
        public string $newStatus
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'status_changed',
            'report_id' => $this->report->id,
            'title' => $this->report->title,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
        ];
    }
}
