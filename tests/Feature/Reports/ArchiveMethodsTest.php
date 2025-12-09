<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ArchiveMethodsTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    /**
     * Test POST method for archive
     */
    public function test_archive_with_post_method()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reports/{$report->id}/archive");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertTrue(Report::find($report->id)->isArchived());
    }

    /**
     * Test PATCH method for archive
     */
    public function test_archive_with_patch_method()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/archive");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertTrue(Report::find($report->id)->isArchived());
    }

    /**
     * Test POST method for unarchive
     */
    public function test_unarchive_with_post_method()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reports/{$report->id}/unarchive");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertFalse(Report::find($report->id)->isArchived());
    }

    /**
     * Test PATCH method for unarchive
     */
    public function test_unarchive_with_patch_method()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/unarchive");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertFalse(Report::find($report->id)->isArchived());
    }
}
