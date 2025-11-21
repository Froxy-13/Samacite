<?php

namespace App\Policies;

use App\Models\User;

class CategoryPolicy
{
    /**
     * View categories
     */
    public function view(User $user): bool
    {
        return $user->can('report.view.own') || $user->can('report.view.any');
    }

    /**
     * Manage categories (Admin only)
     */
    public function manage(User $user): bool
    {
        return $user->hasRole('Admin') && $user->can('dashboard.access');
    }

    /**
     * Create category
     */
    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    /**
     * Update category
     */
    public function update(User $user): bool
    {
        return $this->manage($user);
    }

    /**
     * Delete category
     */
    public function delete(User $user): bool
    {
        return $this->manage($user);
    }
}
