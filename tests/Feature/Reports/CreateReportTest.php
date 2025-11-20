<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class CreateReportTest extends TestCase
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
    }

    public function test_user_can_create_report()
    {
        $response = $this->actingAs($this->user)->postJson('/api/reports', [
            'title' => 'Pothole in Street',
            'description' => 'Large hole in the road',
            'category_id' => $this->category->id,
            'latitude' => 14.7167,
            'longitude' => -17.4677,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'description', 'status', 'user_id'],
            ]);

        $this->assertDatabaseHas('reports', [
            'user_id' => $this->user->id,
            'title' => 'Pothole in Street',
            'status' => 'En attente',
        ]);
    }

    public function test_user_without_permission_cannot_create_report()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        $user->revokePermissionTo('report.create');

        $response = $this->actingAs($user)->postJson('/api/reports', [
            'title' => 'Test Report',
            'description' => 'Test',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_unauthenticated_user_cannot_create_report()
    {
        $response = $this->postJson('/api/reports', [
            'title' => 'Test Report',
            'description' => 'Test',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_report_validation_fails_without_required_fields()
    {
        $response = $this->actingAs($this->user)->postJson('/api/reports', [
            'title' => '',
            'description' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    public function test_report_title_must_not_exceed_255_characters()
    {
        $response = $this->actingAs($this->user)->postJson('/api/reports', [
            'title' => str_repeat('a', 256),
            'description' => 'Test description',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }
}
