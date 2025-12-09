<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine if the user can view all reports
     */
    public function viewAny(User $user): bool
    {
        return $user->can('report.view.own') || $user->hasRole('Admin');
    }

    /**
     * Determine if the user can view a specific report
     */
    public function view(User $user, Report $report): bool
    {
        // Any authenticated user can view any report details
        return true;
    }

    /**
     * Determine if the user can create reports
     */
    public function create(User $user): bool
    {
        return $user->can('report.create');
    }

    /**
     * Determine if the user can update a report
     */
    public function update(User $user, Report $report): bool
    {
        // Only the owner or admin can update
        return $user->id === $report->user_id || $user->hasRole('Admin');
    }

    /**
     * Determine if the user can delete a report
     */
    public function delete(User $user, Report $report): bool
    {
        // Only the owner or admin can delete
        return $user->id === $report->user_id || $user->hasRole('Admin');
    }

    /**
     * Determine if the user can change the status of a report
     * Only admins can change report status
     */
    public function changeStatus(User $user, Report $report): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine if the user can archive a report
     * Only admins can archive reports
     */
    public function archive(User $user, Report $report): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine if the user can unarchive a report
     * Only admins can unarchive reports
     */
    public function unarchive(User $user, Report $report): bool
    {
        return $user->hasRole('Admin');
    }
}
