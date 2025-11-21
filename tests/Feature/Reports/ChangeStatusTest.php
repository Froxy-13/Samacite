<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ChangeStatusTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user;
    protected User $admin;
    protected Category $category;
    protected Report $report;

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

        $this->report = Report::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);
    }

    public function test_user_cannot_change_report_status()
    {
        $response = $this->actingAs($this->user)->patchJson("/api/reports/{$this->report->id}/status", [
            'status' => 'En cours',
        ]);

        $response->assertStatus(403);

        // Verify status was not changed
        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'status' => 'En attente',
        ]);
    }

    public function test_admin_can_change_report_to_in_progress()
    {
        $response = $this->actingAs($this->admin)->patchJson("/api/reports/{$this->report->id}/status", [
            'status' => 'En cours',
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['status' => 'En cours']]);

        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'status' => 'En cours',
        ]);
    }

    public function test_admin_can_change_report_to_resolved()
    {
        $response = $this->actingAs($this->admin)->patchJson("/api/reports/{$this->report->id}/status", [
            'status' => 'Résolu',
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['status' => 'Résolu']]);

        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'status' => 'Résolu',
        ]);
    }

    public function test_invalid_status_fails()
    {
        $response = $this->actingAs($this->admin)->patchJson("/api/reports/{$this->report->id}/status", [
            'status' => 'Invalid Status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_change_status_nonexistent_report_returns_404()
    {
        $response = $this->actingAs($this->admin)->patchJson('/api/reports/999/status', [
            'status' => 'En cours',
        ]);

        $response->assertStatus(404);
    }
}

