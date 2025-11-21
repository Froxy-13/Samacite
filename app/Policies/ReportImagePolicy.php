<?php

namespace App\Policies;

use App\Models\ReportImage;
use App\Models\User;

class ReportImagePolicy
{
    /**
     * Create image
     */
    public function create(User $user): bool
    {
        return $user->can('file.upload');
    }

    /**
     * Update image
     */
    public function update(User $user, ReportImage $image): bool
    {
        return $user->id === $image->report->user_id || $user->hasRole('Admin');
    }

    /**
     * Delete image
     */
    public function delete(User $user, ReportImage $image): bool
    {
        return $user->id === $image->report->user_id || $user->hasRole('Admin');
    }
}
