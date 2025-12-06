<?php

namespace App\Observers;

use App\Models\Comment;
use App\Notifications\GenericNotification;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        // Récupérer le propriétaire du signalement
        $reportOwner = $comment->report->user;
        
        // Ne pas notifier si c'est l'auteur lui-même qui commente
        if ($comment->user_id === $reportOwner->id) {
            return;
        }
        
        // 🔔 NOTIFICATION : Nouveau commentaire
        $reportOwner->notify(new GenericNotification(
            'Un nouveau commentaire a été ajouté à votre signalement',
            [
                'type' => 'comment_created',
                'message' => 'Un nouveau commentaire a été ajouté à votre signalement',
                'report_id' => $comment->report_id,
                'comment_id' => $comment->id,
                'commenter' => $comment->user->name ?? 'Utilisateur',
            ]
        ));
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        // Optionnel : notifier si besoin
    }
}
