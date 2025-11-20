<?php

namespace Tests\Traits;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait WithRolesAndPermissions
{
    use RefreshDatabase;

    /**
     * Seed the database before each test
     */
    public function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }
}
