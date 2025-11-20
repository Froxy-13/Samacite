<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class UpdateReportTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user;
    protected User $otherUser;
    protected User $admin;
    protected Category $category;
    protected Report $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('Utilisateur');
        
        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('Utilisateur');
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
        
        $this->category = Category::create([
            'name' => 'Route Endommagée',
            'description' => 'Problèmes routiers',
        ]);

        $this->report = Report::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
            'status' => 'En attente',
        ]);
    }

    public function test_user_can_update_own_report()
    {
        $response = $this->actingAs($this->user)->patchJson("/api/reports/{$this->report->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'description'],
            ]);

        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);
    }

    public function test_user_cannot_update_other_user_report()
    {
        $response = $this->actingAs($this->otherUser)->patchJson("/api/reports/{$this->report->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_report()
    {
        $response = $this->actingAs($this->admin)->patchJson("/api/reports/{$this->report->id}", [
            'title' => 'Admin Updated Title',
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
            'title' => 'Admin Updated Title',
        ]);
    }

    public function test_update_with_invalid_data_fails()
    {
        $response = $this->actingAs($this->user)->patchJson("/api/reports/{$this->report->id}", [
            'title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_update_nonexistent_report_returns_404()
    {
        $response = $this->actingAs($this->user)->patchJson('/api/reports/999', [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(404);
    }
}
