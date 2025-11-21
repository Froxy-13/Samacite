<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class DeleteImageTest extends TestCase
{
    use RefreshDatabase;
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_report_owner_can_delete_image(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Test Category', 'description' => 'Test']);

        $report = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        $image = ReportImage::create([
            'report_id' => $report->id,
            'image_path' => 'reports/test.jpg',
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/images/{$image->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('report_images', ['id' => $image->id]);
    }

    public function test_admin_can_delete_any_image(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Test Category', 'description' => 'Test']);

        $report = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        $image = ReportImage::create([
            'report_id' => $report->id,
            'image_path' => 'reports/test.jpg',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/images/{$image->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('report_images', ['id' => $image->id]);
    }

    public function test_user_cannot_delete_other_user_image(): void
    {
        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Test Category', 'description' => 'Test']);

        $report = Report::create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        $image = ReportImage::create([
            'report_id' => $report->id,
            'image_path' => 'reports/test.jpg',
        ]);

        $response = $this->actingAs($user2, 'sanctum')->deleteJson("/api/images/{$image->id}");

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_delete_nonexistent_image_returns_404(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/images/9999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_user_cannot_delete_image(): void
    {
        $response = $this->deleteJson('/api/images/1');

        $response->assertStatus(401);
    }
}

