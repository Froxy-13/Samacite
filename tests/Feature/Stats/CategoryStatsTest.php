<?php

namespace Tests\Feature\Stats;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class CategoryStatsTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    /**
     * Test endpoint statistiques catégorie accessible
     */
    public function test_category_stats_endpoint_is_accessible()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$category->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'category_id',
                    'category_name',
                    'total_reports',
                    'reports_by_status',
                    'total_users_reported',
                    'average_reports_per_user'
                ]
            ]);
    }

    /**
     * Test nombre total de signalements pour une catégorie
     */
    public function test_category_total_reports()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();
        $reporter = User::factory()->create();
        Report::factory(5)->create(['category_id' => $category->id, 'user_id' => $reporter->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$category->id}");

        $this->assertEquals(5, $response->json('data.total_reports'));
        $this->assertEquals($category->id, $response->json('data.category_id'));
        $this->assertEquals($category->name, $response->json('data.category_name'));
    }

    /**
     * Test répartition par statut pour une catégorie
     */
    public function test_category_reports_by_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();
        $reporter = User::factory()->create();

        Report::factory(2)->create([
            'category_id' => $category->id,
            'status' => 'En attente',
            'user_id' => $reporter->id
        ]);
        Report::factory(3)->create([
            'category_id' => $category->id,
            'status' => 'En cours',
            'user_id' => $reporter->id
        ]);
        Report::factory(1)->create([
            'category_id' => $category->id,
            'status' => 'Résolu',
            'user_id' => $reporter->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$category->id}");

        $byStatus = $response->json('data.reports_by_status');
        
        $this->assertEquals(2, $byStatus['En attente']);
        $this->assertEquals(3, $byStatus['En cours']);
        $this->assertEquals(1, $byStatus['Résolu']);
    }

    /**
     * Test nombre d'utilisateurs qui ont signalé dans une catégorie
     */
    public function test_category_total_users_reported()
    {
        Cache::flush();
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Create reports directly, not using factory to avoid nested user creation
        for ($i = 0; $i < 2; $i++) {
            Report::factory()->forUser($user1)->forCategory($category)->create();
        }
        for ($i = 0; $i < 3; $i++) {
            Report::factory()->forUser($user2)->forCategory($category)->create();
        }
        for ($i = 0; $i < 1; $i++) {
            Report::factory()->forUser($user3)->forCategory($category)->create();
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$category->id}");

        // Verify the response is correct
        $totalReports = $response->json('data.total_reports');
        $totalUsers = $response->json('data.total_users_reported');
        
        // Should have 6 reports and 3 users
        $this->assertEquals(6, $totalReports, "Total reports should be 6");
        $this->assertEquals(3, $totalUsers, "Total users should be 3, got " . $totalUsers);
    }

    /**
     * Test moyenne de signalements par utilisateur pour une catégorie
     */
    public function test_category_average_reports_per_user()
    {
        Cache::flush();
        
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            Report::factory()->forUser($user1)->forCategory($category)->create();
        }
        for ($i = 0; $i < 2; $i++) {
            Report::factory()->forUser($user2)->forCategory($category)->create();
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$category->id}");

        // 6 reports / 2 users = 3.0
        $average = $response->json('data.average_reports_per_user');
        $this->assertEquals(3.0, $average);
    }

    /**
     * Test catégorie non existante retourne 404
     */
    public function test_nonexistent_category_returns_404()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/category/999')
            ->assertStatus(404);
    }

    /**
     * Test permission stats.view requise
     */
    public function test_stats_requires_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/stats/category/{$category->id}")
            ->assertStatus(403);
    }

    /**
     * Test non authentifié rejeté
     */
    public function test_unauthenticated_user_rejected()
    {
        $category = Category::factory()->create();

        $this->getJson("/api/stats/category/{$category->id}")
            ->assertStatus(401);
    }

    /**
     * Test stats vide si aucun signalement
     */
    public function test_category_stats_empty()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$category->id}");

        $this->assertEquals(0, $response->json('data.total_reports'));
        $this->assertEquals(0, $response->json('data.total_users_reported'));
    }

    /**
     * Test comparaison entre deux catégories
     */
    public function test_compare_two_categories()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        Report::factory(5)->create(['category_id' => $cat1->id]);
        Report::factory(10)->create(['category_id' => $cat2->id]);

        $response1 = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$cat1->id}");

        $response2 = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/category/{$cat2->id}");

        $this->assertEquals(5, $response1->json('data.total_reports'));
        $this->assertEquals(10, $response2->json('data.total_reports'));
    }
}
