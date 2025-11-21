<?php

namespace Tests\Traits;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait WithRolesAndPermissions
{
    use RefreshDatabase;

    /**
     * Set up roles and permissions for testing
     */
    public function setUpRolesAndPermissions(): void
    {
        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }
}
