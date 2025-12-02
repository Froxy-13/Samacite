<?php

namespace App\Listeners;

use App\Events\ReportLiked;
use App\Jobs\NotifyUserJob;

class SendLikeNotification
{
    public function handle(ReportLiked $event): void
    {
        $like = $event->like;
        $report = $like->report;

        // Notify the report owner asynchronously (only if liker is not the owner)
        if ($report->user_id !== $like->user_id) {
            NotifyUserJob::dispatch(
                $report->user,
                'Quelqu\'un a aimé votre signalement',
                [
                    'report_id' => $report->id,
                    'like_id' => $like->id,
                    'type' => 'report_liked',
                    'liker_name' => $like->user->nom . ' ' . $like->user->prenom,
                    'liker_id' => $like->user_id,
                ]
            );
        }
    }
}
