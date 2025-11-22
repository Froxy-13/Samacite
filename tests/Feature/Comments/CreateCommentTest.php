<?php

namespace Tests\Feature\Comments;

use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class CreateCommentTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_user_can_create_comment()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/comments', [
                'content' => 'This is a test comment',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'content',
                'user_id',
                'report_id',
            ]
        ]);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment',
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);
    }

    public function test_user_without_permission_cannot_create_comment()
    {
        $user = User::factory()->create();
        $user->removeRole('Utilisateur');

        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/comments', [
                'content' => 'This is a test comment',
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_create_comment()
    {
        $report = Report::factory()->create();

        $response = $this->postJson('/api/reports/' . $report->id . '/comments', [
            'content' => 'This is a test comment',
        ]);

        $response->assertStatus(401);
    }

    public function test_comment_validation_fails_without_content()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/comments', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('content');
    }

    public function test_comment_content_must_not_be_empty()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/' . $report->id . '/comments', [
                'content' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_comment_on_nonexistent_report_returns_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports/99999/comments', [
                'content' => 'This is a test comment',
            ]);

        $response->assertStatus(404);
    }
}
