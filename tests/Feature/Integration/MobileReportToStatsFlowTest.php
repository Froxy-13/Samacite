<?php

namespace Tests\Feature\Integration;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class MobileReportToStatsFlowTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_mobile_app_creates_report_and_stats_update(): void
    {
        // Setup
        $user = User::factory()->create();
        $user->givePermissionTo('report.create');
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');
        
        $category = Category::factory()->create(['name' => 'Route']);

        // Step 1: Mobile app creates a report (as authenticated user)
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/reports', [
            'title' => 'Cratère sur la Route de Ngor',
            'description' => 'Cratère importante à la route de Ngor',
            'latitude' => 14.7667,
            'longitude' => -17.5000,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));

        // Verify report was created
        $this->assertDatabaseHas('reports', [
            'title' => 'Cratère sur la Route de Ngor',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Step 2: Clear cache to ensure fresh calculation
        Cache::forget('cache:stats:global');

        // Step 3: Admin checks global stats (should now include the new report)
        $statsResponse = $this->actingAs($adminUser, 'sanctum')->getJson('/api/stats/global');

        $statsResponse->assertStatus(200);
        $this->assertTrue($statsResponse->json('success'));

        $data = $statsResponse->json('data');
        $this->assertEquals(1, $data['total_reports'], 'Stats should show 1 report');
        $this->assertArrayHasKey('reports_by_status', $data);
        $this->assertArrayHasKey('reports_by_category', $data);
        
        // Verify category stats
        $categoryStats = collect($data['reports_by_category'])->firstWhere('name', 'Route');
        $this->assertNotNull($categoryStats, 'Category should appear in stats');
        $this->assertEquals(1, $categoryStats['count']);

        // Verify status breakdown
        $this->assertArrayHasKey('En attente', $data['reports_by_status']);
        $this->assertEquals(1, $data['reports_by_status']['En attente']['count']);
    }

    public function test_multiple_reports_increase_stats(): void
    {
        // Setup
        $user1 = User::factory()->create();
        $user1->givePermissionTo('report.create');
        $user2 = User::factory()->create();
        $user2->givePermissionTo('report.create');
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');
        
        $category = Category::factory()->create(['name' => 'Eau']);

        // Create 3 reports
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($i % 2 == 0 ? $user1 : $user2, 'sanctum')->postJson('/api/reports', [
                'title' => 'Report ' . ($i + 1),
                'description' => 'Description ' . ($i + 1),
                'latitude' => 14.7667 + $i * 0.01,
                'longitude' => -17.5000 + $i * 0.01,
                'category_id' => $category->id,
                'status' => $i == 0 ? 'En attente' : ($i == 1 ? 'En cours' : 'Résolu')
            ]);
        }

        // Clear cache
        Cache::forget('cache:stats:global');

        // Check stats
        $statsResponse = $this->actingAs($adminUser, 'sanctum')->getJson('/api/stats/global');

        $data = $statsResponse->json('data');
        $this->assertEquals(3, $data['total_reports']);
        // We have 3 users: user1, user2, and adminUser
        $this->assertEquals(3, $data['total_users']);
        
        // Verify that status breakdown exists and has data
        $this->assertIsArray($data['reports_by_status']);
        $this->assertNotEmpty($data['reports_by_status']);
    }

    public function test_notifications_are_sent_when_report_created(): void
    {
        // Setup
        $user = User::factory()->create();
        $user->givePermissionTo('report.create');
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');
        
        $category = Category::factory()->create();

        // Create a report
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/reports', [
            'title' => 'Test Report',
            'description' => 'Test',
            'latitude' => 14.7667,
            'longitude' => -17.5000,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $response->assertStatus(201);

        // Check that admin received notification
        $notificationsResponse = $this->actingAs($adminUser, 'sanctum')->getJson('/api/notifications');
        
        $notificationsResponse->assertStatus(200);
        $this->assertTrue($notificationsResponse->json('success'));
        
        // Should have notifications (might be more if test suite runs multiple tests)
        $notifications = $notificationsResponse->json('data.data');
        $this->assertGreaterThanOrEqual(1, count($notifications));
    }

    public function test_cache_invalidation_on_status_change(): void
    {
        // Setup
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');
        
        $category = Category::factory()->create();

        // Create a report
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        // Get initial stats
        $initialStats = $this->actingAs($adminUser, 'sanctum')->getJson('/api/stats/global');
        $initialResolved = $initialStats->json('data.reports_by_status.Résolu.count') ?? 0;
        $this->assertEquals(0, $initialResolved);

        // Change report status
        $this->actingAs($user, 'sanctum')->patchJson("/api/reports/{$report->id}/status", [
            'status' => 'Résolu'
        ]);

        // Force cache refresh
        Cache::forget('cache:stats:global');

        // Get updated stats
        $updatedStats = $this->actingAs($adminUser, 'sanctum')->getJson('/api/stats/global');
        $updatedResolved = $updatedStats->json('data.reports_by_status.Résolu.count');
        
        $this->assertEquals(1, $updatedResolved, 'Stats should show resolved count increased to 1');
    }

    public function test_timeline_stats_show_report_evolution(): void
    {
        // Setup
        $user = User::factory()->create();
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Admin');
        
        $category = Category::factory()->create();

        // Create reports today and yesterday
        $today = now();
        $yesterday = $today->copy()->subDay();

        Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => $yesterday
        ]);

        Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => $today
        ]);

        // Get timeline stats (7 days)
        $timelineResponse = $this->actingAs($adminUser, 'sanctum')->getJson('/api/stats/timeline?period=7days');

        $timelineResponse->assertStatus(200);
        $this->assertTrue($timelineResponse->json('success'));

        $data = $timelineResponse->json('data');
        $this->assertEquals(7, $data['days']);
        $this->assertIsArray($data['timeline']);
    }
}
