<?php

namespace Tests\Feature\Likes;

use App\Models\Like;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class DeleteLikeTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_user_can_unlike_report()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        // Create a like first
        Like::factory()->create([
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);
    }

    public function test_user_cannot_unlike_nonexistent_like()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(404);
    }

    public function test_admin_can_delete_any_like()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        Like::factory()->create([
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);

        // Admin can delete any user's like by deleting via the API with proper authorization
        // In this case, we test that an admin can remove likes through direct Like deletion
        $like = Like::where('report_id', $report->id)
            ->where('user_id', $user->id)
            ->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/likes/' . $like->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_unlike_report()
    {
        $report = Report::factory()->create();

        $response = $this->deleteJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(401);
    }

    public function test_unlike_nonexistent_report_returns_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/reports/99999/likes');

        $response->assertStatus(404);
    }
}
