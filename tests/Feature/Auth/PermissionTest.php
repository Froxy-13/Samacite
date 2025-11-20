<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class PermissionTest extends TestCase
{
    use WithRolesAndPermissions;

    /**
     * Test admin can access dashboard
     */
    public function test_admin_has_dashboard_access_permission()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('dashboard.access'));
    }

    /**
     * Test admin can view users
     */
    public function test_admin_can_view_users()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('user.view'));
    }

    /**
     * Test admin can manage users
     */
    public function test_admin_can_manage_users()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('user.manage'));
    }

    /**
     * Test admin has all report permissions
     */
    public function test_admin_has_all_report_permissions()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('report.create'));
        $this->assertTrue($admin->hasPermissionTo('report.view.own'));
        $this->assertTrue($admin->hasPermissionTo('report.view.any'));
        $this->assertTrue($admin->hasPermissionTo('report.update'));
        $this->assertTrue($admin->hasPermissionTo('report.assign'));
        $this->assertTrue($admin->hasPermissionTo('report.change_state'));
        $this->assertTrue($admin->hasPermissionTo('report.delete'));
    }

    /**
     * Test admin can send notifications
     */
    public function test_admin_can_send_notifications()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('notification.send'));
    }

    /**
     * Test admin can view stats
     */
    public function test_admin_can_view_stats()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('stats.view'));
    }

    /**
     * Test admin can manage settings
     */
    public function test_admin_can_manage_settings()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('settings.manage'));
    }

    /**
     * Test admin can upload files
     */
    public function test_admin_can_upload_files()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('file.upload'));
    }

    /**
     * Test admin can comment on reports
     */
    public function test_admin_can_comment()
    {
        $admin = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($admin->hasPermissionTo('report.comment'));
    }
}
