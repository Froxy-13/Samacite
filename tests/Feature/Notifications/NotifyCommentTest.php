<?php

namespace Tests\Feature\Notifications;

use App\Jobs\NotifyUserJob;
use App\Models\User;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class NotifyCommentTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_notification_sent_when_comment_created()
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $this->actingAs($user2, 'sanctum')
            ->postJson("/api/reports/{$report->id}/comments", [
                'content' => 'Great report!'
            ])
            ->assertStatus(201);

        Queue::assertPushed(NotifyUserJob::class);
    }

    public function test_report_owner_receives_comment_notification()
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $this->actingAs($user2, 'sanctum')
            ->postJson("/api/reports/{$report->id}/comments", [
                'content' => 'Nice report!'
            ]);

        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($user1) {
            return $job->user->id === $user1->id;
        });
    }

    public function test_notification_contains_comment_info()
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $this->actingAs($user2, 'sanctum')
            ->postJson("/api/reports/{$report->id}/comments", [
                'content' => 'Test comment'
            ]);

        Queue::assertPushed(NotifyUserJob::class, function ($job) {
            return isset($job->data['comment_id']);
        });
    }

    public function test_other_commenters_receive_notifications_too()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');

        $user3 = User::factory()->create();
        $user3->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user1->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        // User2 comments
        $this->actingAs($user2, 'sanctum')
            ->postJson("/api/reports/{$report->id}/comments", [
                'content' => 'First comment'
            ]);

        // Now fake for second comment
        Queue::fake();

        // User3 comments
        $this->actingAs($user3, 'sanctum')
            ->postJson("/api/reports/{$report->id}/comments", [
                'content' => 'Second comment'
            ]);

        // User2 should receive notification about user3's comment
        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($user2) {
            return $job->user->id === $user2->id;
        });
    }
}
