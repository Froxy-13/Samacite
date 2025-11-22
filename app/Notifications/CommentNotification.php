<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification
{
    public function __construct(public Comment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'comment_created',
            'report_id' => $this->comment->report_id,
            'content' => $this->comment->content,
            'user_name' => $this->comment->user->nom . ' ' . $this->comment->user->prenom,
        ];
    }
}
