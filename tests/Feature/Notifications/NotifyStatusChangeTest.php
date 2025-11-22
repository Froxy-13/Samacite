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

class NotifyStatusChangeTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_notification_sent_when_report_status_changed()
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", [
                'status' => 'En cours'
            ])
            ->assertStatus(200);

        Queue::assertPushed(NotifyUserJob::class);
    }

    public function test_user_receives_status_change_notification()
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", [
                'status' => 'En cours'
            ]);

        Queue::assertPushed(NotifyUserJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    public function test_notification_contains_status_info()
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", [
                'status' => 'Résolu'
            ]);

        Queue::assertPushed(NotifyUserJob::class, function ($job) {
            return isset($job->data['old_status']) &&
                   isset($job->data['new_status']) &&
                   $job->data['old_status'] === 'En attente' &&
                   $job->data['new_status'] === 'Résolu';
        });
    }

    public function test_status_change_to_resolved()
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('Utilisateur');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $category = Category::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'En attente'
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/reports/{$report->id}/status", [
                'status' => 'Résolu'
            ])
            ->assertStatus(200);

        Queue::assertPushed(NotifyUserJob::class);
    }
}
