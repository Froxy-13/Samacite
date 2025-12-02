<?php

namespace App\Notifications;

use App\Models\Like;
use Illuminate\Notifications\Notification;

class ReportLikedNotification extends Notification
{
    public function __construct(public Like $like)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'report_liked',
            'report_id' => $this->like->report_id,
            'report_title' => $this->like->report->title,
            'liker_name' => $this->like->user->nom . ' ' . $this->like->user->prenom,
            'liker_id' => $this->like->user_id,
        ];
    }
}
