<?php

namespace Tests\Feature\Phase4;

use App\Models\Like;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class Phase4ToggleLikeTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    /**
     * Test le système toggle like/unlike complet
     */
    public function test_complete_like_unlike_toggle_flow()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        // Step 1: User likes a report
        $likeResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/likes');

        $likeResponse->assertStatus(201);
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);

        // Step 2: Check if user liked the report
        $checkResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/likes/check');

        $checkResponse->assertStatus(200);
        $checkResponse->assertJson(['data' => ['liked' => true]]);

        // Step 3: Try to like again - should fail
        $duplicateLike = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/likes');

        $duplicateLike->assertStatus(409);

        // Step 4: Get likes list
        $listResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/likes');

        $listResponse->assertStatus(200);
        $likeData = $listResponse->json('data.data');
        // If likeData is null, get the raw data to debug
        if ($likeData === null) {
            $likeData = $listResponse->json('data');
        }
        $this->assertIsArray($likeData);

        // Step 5: Unlike (remove like)
        $unlikeResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/reports/' . $report->id . '/likes');

        $unlikeResponse->assertStatus(200);
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);

        // Step 6: Check if user still liked the report (should be false now)
        $checkAfterUnlike = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/likes/check');

        $checkAfterUnlike->assertStatus(200);
        $checkAfterUnlike->assertJson(['data' => ['liked' => false]]);

        // Step 7: Try to unlike when no like exists - should fail
        $unlikeNonexistent = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/reports/' . $report->id . '/likes');

        $unlikeNonexistent->assertStatus(404);
    }
}
