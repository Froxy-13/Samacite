<?php

namespace Tests\Feature\Stats;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class GlobalStatsTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    /**
     * Test statistiques globales - endpoint accessible
     */
    public function test_global_stats_endpoint_is_accessible()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global')
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_reports',
                    'reports_by_status',
                    'reports_by_category',
                    'total_users',
                    'users_by_role',
                    'average_reports_per_user'
                ]
            ]);
    }

    /**
     * Test nombre total de signalements
     */
    public function test_total_reports_count()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();
        Report::factory(5)->create(['category_id' => $category->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global');

        $this->assertEquals(5, $response->json('data.total_reports'));
    }

    /**
     * Test répartition par statut
     */
    public function test_reports_by_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        Report::factory(2)->create([
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);
        Report::factory(3)->create([
            'category_id' => $category->id,
            'status' => 'En cours'
        ]);
        Report::factory(1)->create([
            'category_id' => $category->id,
            'status' => 'Résolu'
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global');

        $byStatus = $response->json('data.reports_by_status');
        
        $this->assertArrayHasKey('En attente', $byStatus);
        $this->assertArrayHasKey('En cours', $byStatus);
        $this->assertArrayHasKey('Résolu', $byStatus);
        $this->assertEquals(2, $byStatus['En attente']['count']);
        $this->assertEquals(3, $byStatus['En cours']['count']);
        $this->assertEquals(1, $byStatus['Résolu']['count']);
    }

    /**
     * Test répartition par catégorie
     */
    public function test_reports_by_category()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category1 = Category::factory()->create(['name' => 'Nids-de-poule']);
        $category2 = Category::factory()->create(['name' => 'Éclairage']);

        Report::factory(3)->create(['category_id' => $category1->id]);
        Report::factory(2)->create(['category_id' => $category2->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global');

        $byCategory = $response->json('data.reports_by_category');
        
        $this->assertIsArray($byCategory);
        $categoryNames = array_map(fn($cat) => $cat['name'], $byCategory);
        $this->assertContains('Nids-de-poule', $categoryNames);
        $this->assertContains('Éclairage', $categoryNames);
    }

    /**
     * Test nombre total d'utilisateurs
     */
    public function test_total_users_count()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        User::factory(5)->create()->each(fn($u) => $u->assignRole('Utilisateur'));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global');

        // +1 for admin
        $this->assertGreaterThanOrEqual(6, $response->json('data.total_users'));
    }

    /**
     * Test répartition par rôle
     */
    public function test_users_by_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        User::factory(3)->create()->each(fn($u) => $u->assignRole('Utilisateur'));

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global');

        $byRole = $response->json('data.users_by_role');
        
        $this->assertArrayHasKey('Admin', $byRole);
        $this->assertArrayHasKey('Utilisateur', $byRole);
        $this->assertGreaterThan(0, $byRole['Admin']);
        $this->assertGreaterThan(0, $byRole['Utilisateur']);
    }

    /**
     * Test moyenne de signalements par utilisateur
     */
    public function test_average_reports_per_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');
        Report::factory(4)->create([
            'user_id' => $user1->id,
            'category_id' => $category->id
        ]);

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');
        Report::factory(2)->create([
            'user_id' => $user2->id,
            'category_id' => $category->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global');

        $average = $response->json('data.average_reports_per_user');
        // 6 reports / 3 users (2 utilisateurs + 1 admin)
        $this->assertIsNumeric($average);
        $this->assertGreaterThan(0, $average);
    }

    /**
     * Test permission stats.view requise
     */
    public function test_stats_requires_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/global')
            ->assertStatus(403);
    }

    /**
     * Test non authentifié rejeté
     */
    public function test_unauthenticated_user_rejected()
    {
        $this->getJson('/api/stats/global')
            ->assertStatus(401);
    }

    /**
     * Test statistiques avec base vide
     */
    public function test_stats_with_empty_database()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/stats/global');

        $this->assertEquals(0, $response->json('data.total_reports'));
        $this->assertEquals(0, $response->json('data.average_reports_per_user'));
    }
}
