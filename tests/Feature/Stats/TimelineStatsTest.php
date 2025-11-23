<?php

namespace Tests\Feature\Stats;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_timeline_endpoint_is_accessible(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/timeline');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'period',
                'days',
                'start_date',
                'end_date',
                'timeline'
            ]
        ]);
    }

    public function test_timeline_with_7_days_period(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Créer des reports
        Report::factory(5)->create(['created_at' => now()]);
        Report::factory(3)->create(['created_at' => now()->subDays(1)]);
        Report::factory(2)->create(['created_at' => now()->subDays(6)]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/timeline?period=7days');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals('7days', $data['period']);
        $this->assertEquals(7, $data['days']);
        $this->assertIsArray($data['timeline']);
    }

    public function test_timeline_with_30_days_period(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/timeline?period=30days');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals('30days', $data['period']);
        $this->assertEquals(30, $data['days']);
    }

    public function test_timeline_returns_all_7_days(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        // Créer des reports seulement le premier jour
        Report::factory(5)->create(['created_at' => now()->startOfDay()]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/timeline?period=7days');

        $response->assertStatus(200);
        $timeline = $response->json('data.timeline');
        
        // Vérifier que nous avons exactement 8 jours (aujourd'hui + 7 jours précédents)
        $this->assertCount(8, $timeline);
        
        // Vérifier que chaque jour a les clés requises
        foreach ($timeline as $item) {
            $this->assertArrayHasKey('date', $item);
            $this->assertArrayHasKey('total', $item);
            $this->assertArrayHasKey('by_status', $item);
            $this->assertIsInt($item['total']);
        }
        
        // Vérifier qu'il y a au moins un jour avec 0 signalements (les jours sans reports)
        $daysWithZero = array_filter($timeline, fn($item) => $item['total'] === 0);
        $this->assertGreaterThan(0, count($daysWithZero));
    }

    public function test_timeline_requires_admin_permission(): void
    {
        $user = User::factory()->create();
        // Pas d'assignation de rôle Admin

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/timeline');

        $response->assertStatus(403);
    }

    public function test_timeline_requires_authentication(): void
    {
        $response = $this->getJson('/api/stats/timeline');

        $response->assertStatus(401);
    }

    public function test_timeline_with_invalid_period_defaults_to_7_days(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/timeline?period=invalid');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals(7, $data['days']);
    }
}
