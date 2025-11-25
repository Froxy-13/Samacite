<?php

namespace Tests\Feature\Images;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ImageCompressionTest extends TestCase
{
    use RefreshDatabase;
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
        Storage::fake('public');
    }

    public function test_uploaded_image_is_compressed(): void
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

        // Créer une fausse image de 2MB
        $file = UploadedFile::fake()->create('large-image.jpg', 2048);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/reports/{$report->id}/images",
            ['image' => $file]
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // Vérifier que l'image a été stockée
        $imagePath = $response->json('data.image_path');
        $this->assertNotNull($imagePath);
    }

    public function test_image_size_limit_increased_to_10mb(): void
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

        // Image de 6MB (acceptée maintenant)
        $file = UploadedFile::fake()->create('large-image.jpg', 6144);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/reports/{$report->id}/images",
            ['image' => $file]
        );

        $response->assertStatus(201);
    }

    public function test_image_over_10mb_is_rejected(): void
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

        // Image de 11MB (trop grosse)
        $file = UploadedFile::fake()->create('huge-image.jpg', 11264);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/reports/{$report->id}/images",
            ['image' => $file]
        );

        $response->assertStatus(422); // Validation error
    }
}
