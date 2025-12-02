<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GenericNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $message,
        protected array $data = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        // If data contains a type, use it; otherwise default to 'generic'
        $type = $this->data['type'] ?? 'generic';
        
        // Merge the message and data, ensuring type is at root level
        return array_merge(
            [
                'type' => $type,
                'message' => $this->message,
            ],
            $this->data
        );
    }
}
