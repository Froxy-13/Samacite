<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Jobs\NotifyUserJob;
use App\Models\Comment;
use App\Models\User;

class SendCommentNotification
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $report = $comment->report;

        // Notify the report owner asynchronously
        if ($report->user_id !== $comment->user_id) {
            NotifyUserJob::dispatch(
                $report->user,
                'Un nouveau commentaire a été ajouté à votre signalement',
                [
                    'report_id' => $report->id,
                    'comment_id' => $comment->id,
                    'type' => 'comment_created',
                    'commenter' => $comment->user->name
                ]
            );
        }

        // Notify other commenters on the same report asynchronously
        $otherCommenters = Comment::where('report_id', $report->id)
            ->where('user_id', '!=', $comment->user_id)
            ->distinct('user_id')
            ->pluck('user_id');

        foreach ($otherCommenters as $userId) {
            $user = User::find($userId);
            if ($user && $user->id !== $report->user_id) {
                NotifyUserJob::dispatch(
                    $user,
                    'Un nouveau commentaire a été ajouté au signalement que vous suivez',
                    [
                        'report_id' => $report->id,
                        'comment_id' => $comment->id,
                        'type' => 'comment_created',
                        'commenter' => $comment->user->name
                    ]
                );
            }
        }
    }
}
