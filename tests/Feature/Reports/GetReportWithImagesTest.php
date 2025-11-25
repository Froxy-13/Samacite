<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class GetReportWithImagesTest extends TestCase
{
    use RefreshDatabase;
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_report_includes_images_in_response(): void
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

        // Create multiple images for the report
        ReportImage::create([
            'report_id' => $report->id,
            'image_path' => 'reports/image1.jpg',
        ]);

        ReportImage::create([
            'report_id' => $report->id,
            'image_path' => 'reports/image2.jpg',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.title', 'Test Report')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'user',
                    'category',
                    'images' => [
                        '*' => [
                            'id',
                            'report_id',
                            'image_path',
                            'image_url',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'created_at',
                    'updated_at',
                ]
            ]);

        // Check that images array has 2 items
        $this->assertCount(2, $response->json('data.images'));
    }

    public function test_report_list_includes_images(): void
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

        ReportImage::create([
            'report_id' => $report->id,
            'image_path' => 'reports/image1.jpg',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Check that the first report has images
        $reports = $response->json('data');
        $this->assertIsArray($reports);
        $this->assertArrayHasKey('images', $reports[0]);
        $this->assertCount(1, $reports[0]['images']);
        $this->assertEquals('reports/image1.jpg', $reports[0]['images'][0]['image_path']);
    }
}
