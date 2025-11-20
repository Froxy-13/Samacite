<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class UserPermissionTest extends TestCase
{
    use WithRolesAndPermissions;

    /**
     * Test regular user can create reports
     */
    public function test_user_can_create_reports()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertTrue($user->hasPermissionTo('report.create'));
    }

    /**
     * Test regular user can view own reports
     */
    public function test_user_can_view_own_reports()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertTrue($user->hasPermissionTo('report.view.own'));
    }

    /**
     * Test regular user can comment on reports
     */
    public function test_user_can_comment()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertTrue($user->hasPermissionTo('report.comment'));
    }

    /**
     * Test regular user can upload files
     */
    public function test_user_can_upload_files()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertTrue($user->hasPermissionTo('file.upload'));
    }

    /**
     * Test regular user cannot view any reports
     */
    public function test_user_cannot_view_any_reports()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertFalse($user->hasPermissionTo('report.view.any'));
    }

    /**
     * Test regular user cannot update reports
     */
    public function test_user_cannot_update_reports()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertFalse($user->hasPermissionTo('report.update'));
    }

    /**
     * Test regular user cannot delete reports
     */
    public function test_user_cannot_delete_reports()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertFalse($user->hasPermissionTo('report.delete'));
    }

    /**
     * Test regular user cannot change report status
     */
    public function test_user_cannot_change_status()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertFalse($user->hasPermissionTo('report.change_state'));
    }

    /**
     * Test regular user cannot access dashboard
     */
    public function test_user_cannot_access_dashboard()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertFalse($user->hasPermissionTo('dashboard.access'));
    }

    /**
     * Test regular user cannot view stats
     */
    public function test_user_cannot_view_stats()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertFalse($user->hasPermissionTo('stats.view'));
    }

    /**
     * Test regular user cannot manage settings
     */
    public function test_user_cannot_manage_settings()
    {
        $user = User::factory()->create()->assignRole('Utilisateur');

        $this->assertFalse($user->hasPermissionTo('settings.manage'));
    }
}
