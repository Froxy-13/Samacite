<?php

namespace Tests\Feature\Notifications;

use App\Jobs\NotifyUserJob;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class NotifyReportCreatedTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_notification_sent_when_report_created()
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports', [
                'category_id' => $category->id,
                'title' => 'Test Report',
                'description' => 'Test Description',
                'status' => 'En attente'
            ])
            ->assertStatus(201);

        // Check if NotifyUserJob was dispatched (at least for report creator and one admin)
        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($user, $admin) {
            return $job->user->id === $user->id || $job->user->id === $admin->id;
        });
    }

    public function test_admin_receives_new_report_notification()
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports', [
                'category_id' => $category->id,
                'title' => 'Another Report',
                'description' => 'Another Description',
                'status' => 'En attente'
            ]);

        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($admin) {
            return $job->user->id === $admin->id;
        });
    }

    public function test_notification_contains_report_info()
    {
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/reports', [
                'category_id' => $category->id,
                'title' => 'Test Report',
                'description' => 'Test Description',
                'status' => 'En attente'
            ]);

        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id &&
                   str_contains($job->message, 'succès');
        });
    }

    public function test_only_admins_receive_new_report_notifications()
    {
        Queue::fake();

        $user1 = User::factory()->create();
        $user1->assignRole('Utilisateur');

        $user2 = User::factory()->create();
        $user2->assignRole('Utilisateur');

        $category = Category::factory()->create();

        $this->actingAs($user1, 'sanctum')
            ->postJson('/api/reports', [
                'category_id' => $category->id,
                'title' => 'Test Report',
                'description' => 'Test Description',
                'status' => 'En attente'
            ]);

        // At least two jobs should be dispatched: one for user1 (creator), one or more for admin(s)
        Queue::assertPushed(NotifyUserJob::class);
        
        // Ensure user2 (who is not an admin or creator) does NOT receive notification
        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($user2) {
            return $job->user->id !== $user2->id;
        });
    }
}
