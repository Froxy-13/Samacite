<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class DeleteReportTest extends TestCase
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
            'title' => 'Report to Delete',
            'description' => 'This report will be deleted',
            'status' => 'En attente',
        ]);
    }

    public function test_user_can_delete_own_report()
    {
        $response = $this->actingAs($this->user)->deleteJson("/api/reports/{$this->report->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('reports', [
            'id' => $this->report->id,
        ]);
    }

    public function test_user_cannot_delete_other_user_report()
    {
        $response = $this->actingAs($this->otherUser)->deleteJson("/api/reports/{$this->report->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('reports', [
            'id' => $this->report->id,
        ]);
    }

    public function test_admin_can_delete_any_report()
    {
        $response = $this->actingAs($this->admin)->deleteJson("/api/reports/{$this->report->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('reports', [
            'id' => $this->report->id,
        ]);
    }

    public function test_user_without_permission_cannot_delete_other_user_report()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $response = $this->actingAs($user)->deleteJson("/api/reports/{$this->report->id}");

        $response->assertStatus(403);
    }

    public function test_delete_nonexistent_report_returns_404()
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/reports/999');

        $response->assertStatus(404);
    }
}
