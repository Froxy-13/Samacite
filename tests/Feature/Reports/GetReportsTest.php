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

    public function test_user_can_view_only_own_reports()
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
            ->assertJsonCount(1, 'data');

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
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    public function test_unauthenticated_user_cannot_view_reports()
    {
        $response = $this->getJson('/api/reports');
        $response->assertStatus(401);
    }

    public function test_user_without_permission_cannot_view_reports()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        $user->revokePermissionTo('report.view.own');

        $response = $this->actingAs($user)->getJson('/api/reports');
        $response->assertStatus(403);
    }
}
