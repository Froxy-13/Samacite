<?php

namespace Tests\Feature\Stats;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class UserStatsTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    /**
     * Test endpoint statistiques utilisateur accessible
     */
    public function test_user_stats_endpoint_is_accessible()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/user')
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'total_reports',
                    'reports_by_status',
                    'reports_by_category',
                    'total_comments',
                    'total_likes_given',
                    'total_likes_received'
                ]
            ]);
    }

    /**
     * Test nombre de signalements de l'utilisateur
     */
    public function test_user_total_reports()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::factory()->create();
        Report::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/user');

        $this->assertEquals(3, $response->json('data.total_reports'));
    }

    /**
     * Test signalements de l'utilisateur par statut
     */
    public function test_user_reports_by_status()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::factory()->create();

        Report::factory(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);
        Report::factory(1)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En cours'
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/user');

        $byStatus = $response->json('data.reports_by_status');
        
        $this->assertArrayHasKey('En attente', $byStatus);
        $this->assertArrayHasKey('En cours', $byStatus);
        $this->assertEquals(2, $byStatus['En attente']);
        $this->assertEquals(1, $byStatus['En cours']);
    }

    /**
     * Test signalements de l'utilisateur par catégorie
     */
    public function test_user_reports_by_category()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $cat1 = Category::factory()->create(['name' => 'Nids-de-poule']);
        $cat2 = Category::factory()->create(['name' => 'Éclairage']);

        Report::factory(2)->create(['user_id' => $user->id, 'category_id' => $cat1->id]);
        Report::factory(1)->create(['user_id' => $user->id, 'category_id' => $cat2->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/user');

        $byCategory = $response->json('data.reports_by_category');
        
        $this->assertIsArray($byCategory);
        $categories = array_column($byCategory, 'count', 'name');
        $this->assertEquals(2, $categories['Nids-de-poule']);
        $this->assertEquals(1, $categories['Éclairage']);
    }

    /**
     * Test utilisateur voit seulement ses statistiques
     */
    public function test_user_sees_only_own_stats()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');

        $category = Category::factory()->create();

        Report::factory(5)->create([
            'user_id' => $user1->id,
            'category_id' => $category->id
        ]);
        Report::factory(10)->create([
            'user_id' => $user2->id,
            'category_id' => $category->id
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson('/api/stats/user');

        $this->assertEquals($user1->id, $response->json('data.user_id'));
        $this->assertEquals(5, $response->json('data.total_reports'));
    }

    /**
     * Test admin peut voir les stats d'un utilisateur spécifique
     */
    public function test_admin_can_view_user_stats()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::factory()->create();
        Report::factory(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/stats/user/{$user->id}");

        $this->assertEquals(200, $response->status());
        $this->assertEquals($user->id, $response->json('data.user_id'));
        $this->assertEquals(3, $response->json('data.total_reports'));
    }

    /**
     * Test non authentifié rejeté
     */
    public function test_unauthenticated_user_rejected()
    {
        $this->getJson('/api/stats/user')
            ->assertStatus(401);
    }

    /**
     * Test stats vide si aucun signalement
     */
    public function test_user_stats_empty()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/user');

        $this->assertEquals(0, $response->json('data.total_reports'));
    }
}
