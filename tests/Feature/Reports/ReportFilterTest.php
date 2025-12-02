<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ReportFilterTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user;
    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();

        // Create test users
        $this->user = User::factory()->create();
        $this->user->assignRole('Utilisateur');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        // Create a category
        $this->category = Category::create([
            'name' => 'Test Category',
            'icon' => 'test_icon',
        ]);
    }

    /** @test */
    public function it_filters_reports_by_status_en_attente()
    {
        // Create reports with different statuses
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report En attente',
            'status' => 'En attente',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report En cours',
            'status' => 'En cours',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report Résolu',
            'status' => 'Résolu',
        ]);

        // Request with filter[status]=En attente
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?filter[status]=En attente&include=user,category,images');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'status'],
                ],
            ]);

        $reports = $response->json('data');
        
        // Should only return "En attente" reports
        $this->assertNotEmpty($reports);
        foreach ($reports as $report) {
            $this->assertEquals('En attente', $report['status']);
        }
    }

    /** @test */
    public function it_filters_reports_by_status_en_cours()
    {
        // Create reports with different statuses
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report En attente',
            'status' => 'En attente',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report En cours 1',
            'status' => 'En cours',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report En cours 2',
            'status' => 'En cours',
        ]);

        // Request with filter[status]=En cours
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?filter[status]=En cours&include=user,category,images');

        $response->assertStatus(200);

        $reports = $response->json('data');
        
        // Should only return "En cours" reports
        $this->assertCount(2, $reports);
        foreach ($reports as $report) {
            $this->assertEquals('En cours', $report['status']);
        }
    }

    /** @test */
    public function it_filters_reports_by_status_resolu()
    {
        // Create reports with different statuses
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report En attente',
            'status' => 'En attente',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report Résolu',
            'status' => 'Résolu',
        ]);

        // Request with filter[status]=Résolu
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?filter[status]=Résolu&include=user,category,images');

        $response->assertStatus(200);

        $reports = $response->json('data');
        
        // Should only return "Résolu" reports
        $this->assertCount(1, $reports);
        $this->assertEquals('Résolu', $reports[0]['status']);
    }

    /** @test */
    public function it_returns_all_reports_without_filter()
    {
        // Create reports with different statuses
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

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'Résolu',
        ]);

        // Request without status filter
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?include=user,category,images');

        $response->assertStatus(200);

        $reports = $response->json('data');
        
        // Should return all 3 reports
        $this->assertCount(3, $reports);
    }

    /** @test */
    public function it_filters_reports_by_category()
    {
        $category2 = Category::create([
            'name' => 'Category 2',
            'icon' => 'icon_2',
        ]);

        // Create reports with different categories
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Report Category 1',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id,
            'title' => 'Report Category 2',
        ]);

        // Request with filter[category_id]
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports?filter[category_id]={$this->category->id}&include=user,category,images");

        $response->assertStatus(200);

        $reports = $response->json('data');
        
        // Should only return category 1 reports
        $this->assertCount(1, $reports);
        $this->assertEquals($this->category->id, $reports[0]['category']['id']);
    }

    /** @test */
    public function it_combines_status_and_category_filters()
    {
        $category2 = Category::create([
            'name' => 'Category 2',
            'icon' => 'icon_2',
        ]);

        // Create reports with different statuses and categories
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

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category2->id,
            'status' => 'En cours',
        ]);

        // Request with both filters
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports?filter[status]=En cours&filter[category_id]={$this->category->id}&include=user,category,images");

        $response->assertStatus(200);

        $reports = $response->json('data');
        
        // Should only return "En cours" + category 1
        $this->assertCount(1, $reports);
        $this->assertEquals('En cours', $reports[0]['status']);
        $this->assertEquals($this->category->id, $reports[0]['category']['id']);
    }

    /** @test */
    public function it_filters_reports_by_user_id()
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Utilisateur');

        // Create reports for different users
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        Report::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $this->category->id,
        ]);

        // Request with user_id filter
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reports?user_id={$this->user->id}&include=user,category,images");

        $response->assertStatus(200);

        $reports = $response->json('data');
        
        // Should only return current user's reports
        $this->assertCount(1, $reports);
        $this->assertEquals($this->user->id, $reports[0]['user']['id']);
    }

    /** @test */
    public function it_filters_reports_by_search_term()
    {
        // Create reports with different titles
        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Nid de poule dangereux',
            'description' => 'Description',
        ]);

        Report::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'title' => 'Lampadaire cassé',
            'description' => 'Description',
        ]);

        // Request with search filter
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reports?search=poule&include=user,category,images');

        $response->assertStatus(200);

        $reports = $response->json('data');
        
        // Should only return reports matching "poule"
        $this->assertCount(1, $reports);
        $this->assertStringContainsString('poule', $reports[0]['title']);
    }
}
