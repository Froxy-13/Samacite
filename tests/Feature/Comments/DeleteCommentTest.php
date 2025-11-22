<?php

namespace Tests\Feature\Comments;

use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class DeleteCommentTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_comment_owner_can_delete_comment()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();
        $comment = Comment::factory()->create([
            'report_id' => $report->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/comments/' . $comment->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_admin_can_delete_any_comment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $report = Report::factory()->create();
        $comment = Comment::factory()->create([
            'report_id' => $report->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/comments/' . $comment->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_other_user_comment()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $report = Report::factory()->create();
        $comment = Comment::factory()->create([
            'report_id' => $report->id,
            'user_id' => $user1->id,
        ]);

        $response = $this->actingAs($user2, 'sanctum')
            ->deleteJson('/api/comments/' . $comment->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_unauthenticated_user_cannot_delete_comment()
    {
        $comment = Comment::factory()->create();

        $response = $this->deleteJson('/api/comments/' . $comment->id);

        $response->assertStatus(401);
    }

    public function test_delete_nonexistent_comment_returns_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/comments/99999');

        $response->assertStatus(404);
    }
}
