<?php

namespace Tests\Feature\Likes;

use App\Models\Like;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class CreateLikeTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_user_can_like_report()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'user_id',
                'report_id',
            ]
        ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);
    }

    public function test_user_cannot_like_same_report_twice()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        // First like
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/likes');

        // Try to like again - should fail or be counted as unlike
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/likes');

        // Second attempt should return 409 Conflict (already liked)
        $response->assertStatus(409);
    }

    public function test_user_without_permission_cannot_like_report()
    {
        $user = User::factory()->create();
        $user->removeRole('Utilisateur');

        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_like_report()
    {
        $report = Report::factory()->create();

        $response = $this->postJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(401);
    }

    public function test_like_nonexistent_report_returns_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/99999/likes');

        $response->assertStatus(404);
    }
}
