<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ArchiveReportTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    /**
     * Test archiving a report
     */
    public function test_admin_can_archive_report()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $user->givePermissionTo('report.create');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Verify report is not archived initially
        $this->assertNull($report->archived_at);

        // Archive the report
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reports/{$report->id}/archive");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Verify report is archived
        $archivedReport = Report::find($report->id);
        $this->assertNotNull($archivedReport->archived_at, 'archived_at should not be null after archiving');
        $this->assertTrue($archivedReport->isArchived());
    }

    /**
     * Test unarchiving a report
     */
    public function test_admin_can_unarchive_report()
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

        // Verify report is archived
        $this->assertTrue($report->isArchived());

        // Unarchive the report
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reports/{$report->id}/unarchive");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Verify report is no longer archived
        $unarchivedReport = Report::find($report->id);
        $this->assertNull($unarchivedReport->archived_at, 'archived_at should be null after unarchiving');
        $this->assertFalse($unarchivedReport->isArchived());
    }

    /**
     * Test non-admin cannot archive report
     */
    public function test_non_admin_cannot_archive_report()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('report.create');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Try to archive the report
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/reports/{$report->id}/archive");

        $response->assertStatus(403);

        // Verify report is not archived
        $this->assertFalse(Report::find($report->id)->isArchived());
    }

    /**
     * Test archive with non-existent report
     */
    public function test_archive_nonexistent_report()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/reports/999/archive');

        $response->assertStatus(404);
    }

    /**
     * Test querying archived reports only
     */
    public function test_query_archived_reports()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create some reports
        Report::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'archived_at' => null,
        ]);

        // Create archived reports
        Report::factory(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'archived_at' => now(),
        ]);

        // Query archived reports
        $archived = Report::archived()->get();
        $this->assertEquals(2, $archived->count());

        // Query non-archived reports
        $notArchived = Report::notArchived()->get();
        $this->assertEquals(3, $notArchived->count());
    }

    /**
     * Test archive and unarchive preserve other data
     */
    public function test_archive_preserves_report_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $originalData = [
            'title' => 'Important Issue',
            'description' => 'This is important',
            'status' => 'En cours',
            'latitude' => 14.7667,
            'longitude' => -17.5,
        ];

        $report = Report::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ], $originalData));

        // Archive the report
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/reports/{$report->id}/archive");

        // Verify data is preserved
        $archivedReport = Report::find($report->id);
        $this->assertEquals($originalData['title'], $archivedReport->title);
        $this->assertEquals($originalData['description'], $archivedReport->description);
        $this->assertEquals($originalData['status'], $archivedReport->status);
        $this->assertEquals($originalData['latitude'], $archivedReport->latitude);
        $this->assertEquals($originalData['longitude'], $archivedReport->longitude);
    }
}
