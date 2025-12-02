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

class NotifyLikeTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_notification_sent_when_report_liked()
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $reportOwner = User::factory()->create();
        $reportOwner->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::create([
            'user_id' => $reportOwner->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes")
            ->assertStatus(201);

        // Check if NotifyUserJob was dispatched to report owner
        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($reportOwner) {
            return $job->user->id === $reportOwner->id &&
                   str_contains($job->message, 'aimé');
        });
    }

    public function test_report_owner_receives_like_notification()
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $reportOwner = User::factory()->create();
        $reportOwner->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::create([
            'user_id' => $reportOwner->id,
            'category_id' => $category->id,
            'title' => 'Another Report',
            'description' => 'Another Description',
            'status' => 'En attente',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes");

        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($reportOwner) {
            return $job->user->id === $reportOwner->id;
        });
    }

    public function test_notification_contains_like_info()
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $reportOwner = User::factory()->create();
        $reportOwner->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::create([
            'user_id' => $reportOwner->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes");

        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($reportOwner, $report) {
            return $job->user->id === $reportOwner->id &&
                   isset($job->data['report_id']) &&
                   $job->data['report_id'] === $report->id &&
                   $job->data['type'] === 'report_liked';
        });
    }

    public function test_no_notification_when_owner_likes_own_report()
    {
        $reportOwner = User::factory()->create();
        $reportOwner->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $report = Report::create([
            'user_id' => $reportOwner->id,
            'category_id' => $category->id,
            'title' => 'Test Report',
            'description' => 'Test Description',
            'status' => 'En attente',
        ]);

        // Start faking queue AFTER report creation to avoid counting report creation notifications
        Queue::fake();

        $this->actingAs($reportOwner, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes");

        // No notification should be sent when owner likes their own report
        Queue::assertNotPushed(NotifyUserJob::class);
    }
}
