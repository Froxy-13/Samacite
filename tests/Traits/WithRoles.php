<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait WithRoles
{
    /**
     * Create test roles and permissions
     */
    protected function createTestRoles(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $userRole = Role::create(['name' => 'Utilisateur', 'guard_name' => 'web']);

        // Create permissions
        $permissions = [
            'dashboard.access',
            'user.view',
            'user.manage',
            'report.create',
            'report.view.own',
            'report.view.any',
            'report.update',
            'report.delete',
            'report.change_state',
            'report.comment',
            'file.upload',
            'stats.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to Admin
        $adminRole->givePermissionTo($permissions);

        // Assign limited permissions to Utilisateur
        $userRole->givePermissionTo([
            'report.create',
            'report.view.own',
            'report.comment',
            'file.upload',
        ]);
    }
}
