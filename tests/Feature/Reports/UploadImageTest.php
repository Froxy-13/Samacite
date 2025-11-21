<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class UploadImageTest extends TestCase
{
    use RefreshDatabase;
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
        Storage::fake('public');
    }

    public function test_user_can_upload_image_to_report(): void
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

        $file = UploadedFile::fake()->create('test.jpg', 640);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/reports/{$report->id}/images",
            ['image' => $file]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.image_path', fn ($path) => !empty($path));
    }

    public function test_user_without_permission_cannot_upload_image(): void
    {
        $user = User::factory()->create();
        // Don't assign role

        $category = Category::create(['name' => 'Test Category', 'description' => 'Test']);

        $report = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        $file = UploadedFile::fake()->create('test.jpg', 640);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/reports/{$report->id}/images",
            ['image' => $file]
        );

        $response->assertStatus(403);
    }

    public function test_image_validation_fails_with_invalid_file(): void
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

        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/reports/{$report->id}/images",
            ['image' => $file]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_image_upload_fails_without_image_file(): void
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

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/reports/{$report->id}/images",
            []
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_user_cannot_upload_image(): void
    {
        $category = Category::create(['name' => 'Test Category', 'description' => 'Test']);

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $report = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        $file = UploadedFile::fake()->create('test.jpg', 640);

        $response = $this->postJson(
            "/api/reports/{$report->id}/images",
            ['image' => $file]
        );

        $response->assertStatus(401);
    }

    public function test_upload_to_nonexistent_report_returns_404(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $file = UploadedFile::fake()->create('test.jpg', 640);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            '/api/reports/9999/images',
            ['image' => $file]
        );

        $response->assertStatus(404);
    }
}

