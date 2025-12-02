<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class GetReportsTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user;
    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('Utilisateur');
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
        
        $this->category = Category::create([
            'name' => 'Route Endommagée',
            'description' => 'Problèmes routiers',
        ]);

        // Create test reports
        Report::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report 1',
            'description' => 'Description 1',
            'status' => 'En attente',
        ]);

        Report::create([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'title' => 'Report 2',
            'description' => 'Description 2',
            'status' => 'En cours',
        ]);
    }

    public function test_user_can_view_all_reports()
    {
        $response = $this->actingAs($this->user)->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'description', 'status', 'user_id'],
                ],
            ])
            ->assertJsonCount(2, 'data'); // ✅ Now sees all reports (both user and admin)

        // Verify reports from different users are included
        $reports = $response->json('data');
        $userIds = array_column($reports, 'user_id');
        $this->assertContains($this->user->id, $userIds);
        $this->assertContains($this->admin->id, $userIds);
    }

    public function test_user_can_filter_by_user_id()
    {
        $response = $this->actingAs($this->user)->getJson('/api/reports?user_id=' . $this->user->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Verify only this user's reports are returned
        $report = $response->json('data.0');
        $this->assertEquals($this->user->id, $report['user_id']);
    }

    public function test_admin_can_view_all_reports()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_report_includes_user_and_category_relations()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/reports');

        $response->assertStatus(200);
        
        $report = $response->json('data.0');
        $this->assertArrayHasKey('user', $report);
        $this->assertArrayHasKey('category', $report);
    }

    public function test_reports_are_paginated()
    {
        // Create more reports
        for ($i = 0; $i < 20; $i++) {
            Report::create([
                'user_id' => $this->admin->id,
                'category_id' => $this->category->id,
                'title' => "Report $i",
                'description' => "Description $i",
                'status' => 'En attente',
            ]);
        }

        $response = $this->actingAs($this->admin)->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
        
        // Check that pagination data is available
        $this->assertIsArray($response->json('data'));
    }

    public function test_unauthenticated_user_cannot_view_reports()
    {
        $response = $this->getJson('/api/reports');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_view_reports()
    {
        // Create a user without any role
        $user = User::factory()->create();
        // Don't assign any role

        $response = $this->actingAs($user)->getJson('/api/reports');
        $response->assertStatus(403);
    }

    public function test_user_can_view_details_of_own_report()
    {
        $report = Report::where('user_id', $this->user->id)->first();
        
        $response = $this->actingAs($this->user)->getJson("/api/reports/{$report->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'description', 'status', 'user_id'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $report->id,
                    'user_id' => $this->user->id,
                ],
            ]);
    }

    public function test_user_can_view_details_of_other_user_report()
    {
        // Get report created by admin
        $adminReport = Report::where('user_id', $this->admin->id)->first();
        
        // Regular user should be able to view admin's report details
        $response = $this->actingAs($this->user)->getJson("/api/reports/{$adminReport->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'description', 'status', 'user_id'],
            ])
            ->assertJson([
                'data' => [
                    'id' => $adminReport->id,
                    'user_id' => $this->admin->id,
                ],
            ]);
    }

    public function test_get_nonexistent_report_returns_404()
    {
        $response = $this->actingAs($this->user)->getJson('/api/reports/9999');
        
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Signalement non trouvé',
            ]);
    }
}
