<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class GetCategoriesTest extends TestCase
{
    use RefreshDatabase;
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_user_can_get_all_categories(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        Category::create(['name' => 'Road Damage', 'description' => 'Potholes']);
        Category::create(['name' => 'Water Issue', 'description' => 'Water problems']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.name', 'Road Damage')
            ->assertJsonPath('data.1.name', 'Water Issue');
    }

    public function test_admin_can_get_all_categories(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Category::create(['name' => 'Road Damage', 'description' => 'Potholes']);
        Category::create(['name' => 'Water Issue', 'description' => 'Water problems']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_unauthenticated_user_cannot_get_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(401);
    }

    public function test_categories_are_paginated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        // Create 25 categories
        for ($i = 1; $i <= 25; $i++) {
            Category::create(['name' => "Category $i", 'description' => "Description $i"]);
        }

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Should have pagination data
        $this->assertLessThanOrEqual(15, count($response->json('data')));
    }

    public function test_get_single_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Road Damage', 'description' => 'Potholes']);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Road Damage');
    }

    public function test_get_nonexistent_category_returns_404(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories/9999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
