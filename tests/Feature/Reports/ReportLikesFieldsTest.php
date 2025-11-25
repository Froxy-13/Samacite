<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Like;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ReportLikesFieldsTest extends TestCase
{
    use RefreshDatabase;
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_report_includes_likes_count_and_is_liked_when_not_authenticated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Test', 'description' => 'Test']);

        $report = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test',
            'status' => 'En attente',
        ]);

        // Créer 3 likes par d'autres utilisateurs
        for ($i = 0; $i < 3; $i++) {
            $otherUser = User::factory()->create();
            $otherUser->assignRole('Utilisateur');
            Like::create([
                'report_id' => $report->id,
                'user_id' => $otherUser->id,
            ]);
        }

        // Requête sans authentification
        $response = $this->getJson("/api/reports/{$report->id}");

        $response->assertStatus(401); // Non authentifié
    }

    public function test_report_includes_likes_count_and_is_liked_false_when_user_has_not_liked(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Test', 'description' => 'Test']);

        $report = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test',
            'status' => 'En attente',
        ]);

        // Créer 3 likes par d'autres utilisateurs
        for ($i = 0; $i < 3; $i++) {
            $otherUser = User::factory()->create();
            $otherUser->assignRole('Utilisateur');
            Like::create([
                'report_id' => $report->id,
                'user_id' => $otherUser->id,
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.likes_count', 3)
            ->assertJsonPath('data.is_liked', false);
    }

    public function test_report_includes_likes_count_and_is_liked_true_when_user_has_liked(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Test', 'description' => 'Test']);

        $report = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test',
            'status' => 'En attente',
        ]);

        // Créer 2 likes par d'autres utilisateurs
        for ($i = 0; $i < 2; $i++) {
            $otherUser = User::factory()->create();
            $otherUser->assignRole('Utilisateur');
            Like::create([
                'report_id' => $report->id,
                'user_id' => $otherUser->id,
            ]);
        }

        // L'utilisateur courant like aussi
        Like::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.likes_count', 3)
            ->assertJsonPath('data.is_liked', true);
    }

    public function test_reports_list_includes_likes_count_and_is_liked(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::create(['name' => 'Test', 'description' => 'Test']);

        // Report 1: L'utilisateur a liké
        $report1 = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Report 1',
            'description' => 'Test',
            'status' => 'En attente',
        ]);
        Like::create(['report_id' => $report1->id, 'user_id' => $user->id]);

        // Report 2: L'utilisateur n'a pas liké
        $report2 = Report::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Report 2',
            'description' => 'Test',
            'status' => 'En attente',
        ]);

        $otherUser = User::factory()->create();
        $otherUser->assignRole('Utilisateur');
        Like::create(['report_id' => $report2->id, 'user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $reports = $response->json('data');
        $this->assertIsArray($reports);
        $this->assertCount(2, $reports);

        // Vérifier report1
        $report1Data = collect($reports)->firstWhere('id', $report1->id);
        $this->assertNotNull($report1Data);
        $this->assertEquals(1, $report1Data['likes_count']);
        $this->assertTrue($report1Data['is_liked']);

        // Vérifier report2
        $report2Data = collect($reports)->firstWhere('id', $report2->id);
        $this->assertNotNull($report2Data);
        $this->assertEquals(1, $report2Data['likes_count']);
        $this->assertFalse($report2Data['is_liked']);
    }
}
