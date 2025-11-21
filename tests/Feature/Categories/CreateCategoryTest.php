<?php

namespace Tests\Feature\Categories;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class CreateCategoryTest extends TestCase
{
    use RefreshDatabase;
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/categories', [
            'name' => 'Road Damage',
            'description' => 'Potholes and damaged roads',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Road Damage');
    }

    public function test_user_cannot_create_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/categories', [
            'name' => 'Road Damage',
            'description' => 'Potholes and damaged roads',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_user_cannot_create_category(): void
    {
        $response = $this->postJson('/api/categories', [
            'name' => 'Road Damage',
            'description' => 'Potholes and damaged roads',
        ]);

        $response->assertStatus(401);
    }

    public function test_category_creation_requires_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/categories', [
            'description' => 'Potholes and damaged roads',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Create first category
        $this->actingAs($admin, 'sanctum')->postJson('/api/categories', [
            'name' => 'Road Damage',
            'description' => 'First category',
        ]);

        // Try to create duplicate
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/categories', [
            'name' => 'Road Damage',
            'description' => 'Second category',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
