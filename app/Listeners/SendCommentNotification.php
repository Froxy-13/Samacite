<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Notifications\CommentNotification;

class SendCommentNotification
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $report = $comment->report;

        // Notify the report owner
        if ($report->user_id !== $comment->user_id) {
            $report->user->notify(new CommentNotification($comment));
        }

        // Notify other commenters on the same report
        $otherCommenters = Comment::where('report_id', $report->id)
            ->where('user_id', '!=', $comment->user_id)
            ->distinct('user_id')
            ->pluck('user_id');

        foreach ($otherCommenters as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user && $user->id !== $report->user_id) {
                $user->notify(new CommentNotification($comment));
            }
        }
    }
}
