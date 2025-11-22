<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // Create permissions
        $permissions = [
            // Dashboard
            'dashboard.access',

            // Users
            'user.view',
            'user.manage',

            // Reports
            'report.create',
            'report.view.own',
            'report.view.any',
            'report.update',
            'report.assign',
            'report.change_state',
            'report.delete',

            // Comments
            'report.comment',

            // Likes
            'report.like',

            // Notifications
            'notification.send',

            // Statistics
            'stats.view',

            // Settings
            'settings.manage',

            // Files
            'file.upload',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin role - has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        // User role - can manage their own reports and view all categories
        $userRole = Role::firstOrCreate(['name' => 'Utilisateur']);
        $userRole->syncPermissions([
            'report.create',
            'report.view.own',
            'report.update',
            'report.delete',
            'report.comment',
            'report.like',
            'file.upload',
        ]);
    }
}
