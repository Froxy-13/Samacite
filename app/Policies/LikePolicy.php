<?php

namespace App\Policies;

use App\Models\Like;
use App\Models\User;

class LikePolicy
{
    /**
     * Determine whether the user can view any likes.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the like.
     */
    public function view(User $user, Like $like): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create likes.
     */
    public function create(User $user): bool
    {
        return $user->can('report.like') || $user->hasRole('Utilisateur');
    }

    /**
     * Determine whether the user can delete the like.
     */
    public function delete(User $user, Like $like): bool
    {
        return $user->id === $like->user_id || $user->hasRole('Admin');
    }
}
