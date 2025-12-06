<?php

namespace App\Observers;

use App\Models\Like;
use App\Notifications\GenericNotification;

class LikeObserver
{
    /**
     * Handle the Like "created" event.
     */
    public function created(Like $like): void
    {
        // Récupérer le propriétaire du signalement
        $reportOwner = $like->report->user;
        
        // Ne pas notifier si c'est l'auteur lui-même qui like
        if ($like->user_id === $reportOwner->id) {
            return;
        }
        
        // 🔔 NOTIFICATION : Like reçu
        $reportOwner->notify(new GenericNotification(
            'Quelqu\'un a aimé votre signalement',
            [
                'type' => 'report_liked',
                'message' => 'Quelqu\'un a aimé votre signalement',
                'report_id' => $like->report_id,
                'like_id' => $like->id,
                'liker_name' => $like->user->name ?? 'Utilisateur',
                'liker_id' => $like->user_id,
            ]
        ));
    }

    /**
     * Handle the Like "deleted" event.
     */
    public function deleted(Like $like): void
    {
        // Optionnel : notifier si besoin
    }
}
