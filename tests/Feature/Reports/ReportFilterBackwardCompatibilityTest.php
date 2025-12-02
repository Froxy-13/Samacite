<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ReportFilterBackwardCompatibilityTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();

        $this->user = User::factory()->create();
        $this->user->assignRole('Utilisateur');

        $this->category = Category::create([
            'name' => 'Test Category',
            'icon' => 'test_icon',
        ]);
    }

    /** @test */
    public function it_supports_mobile_format_with_filter_prefix()
    {
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'En attente',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'En cours',
        ]);

        // Mobile format: filter[status]
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?filter[status]=En attente');

        $response->assertStatus(200);
        $reports = $response->json('data');
        
        $this->assertCount(1, $reports);
        $this->assertEquals('En attente', $reports[0]['status']);
    }

    /** @test */
    public function it_supports_admin_format_without_filter_prefix()
    {
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'En attente',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'En cours',
        ]);

        // Admin format: status (direct)
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?status=En cours');

        $response->assertStatus(200);
        $reports = $response->json('data');
        
        $this->assertCount(1, $reports);
        $this->assertEquals('En cours', $reports[0]['status']);
    }

    /** @test */
    public function it_supports_category_filter_both_formats()
    {
        $category2 = Category::create([
            'name' => 'Category 2',
            'icon' => 'icon_2',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id,
        ]);

        // Test mobile format
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports?filter[category_id]={$this->category->id}");
        
        $this->assertCount(1, $response1->json('data'));

        // Test admin format
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports?category_id={$category2->id}");
        
        $this->assertCount(1, $response2->json('data'));
    }

    /** @test */
    public function mobile_format_takes_precedence_over_admin_format()
    {
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'En attente',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'En cours',
        ]);

        // Send both formats - mobile format should win
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?filter[status]=En attente&status=En cours');

        $response->assertStatus(200);
        $reports = $response->json('data');
        
        // Should use filter[status]=En attente (mobile format)
        $this->assertCount(1, $reports);
        $this->assertEquals('En attente', $reports[0]['status']);
    }

    /** @test */
    public function it_supports_combined_filters_mixed_formats()
    {
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'En cours',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'Résolu',
        ]);

        // Mix: filter[status] (mobile) + category_id (admin)
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports?filter[status]=En cours&category_id={$this->category->id}");

        $response->assertStatus(200);
        $reports = $response->json('data');
        
        $this->assertCount(1, $reports);
        $this->assertEquals('En cours', $reports[0]['status']);
        $this->assertEquals($this->category->id, $reports[0]['category_id']);
    }
}
