<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
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
        Notification::fake();

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

        Notification::assertSentTo($user, \App\Notifications\StatusChangedNotification::class);
    }

    public function test_user_receives_status_change_notification()
    {
        Notification::fake();

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

        Notification::assertSentTo($user, \App\Notifications\StatusChangedNotification::class);
    }

    public function test_notification_contains_status_info()
    {
        Notification::fake();

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

        Notification::assertSentTo(
            $user,
            \App\Notifications\StatusChangedNotification::class,
            function ($notification) {
                return $notification->oldStatus === 'En attente' &&
                       $notification->newStatus === 'Résolu';
            }
        );
    }

    public function test_status_change_to_resolved()
    {
        Notification::fake();

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

        Notification::assertSentTo($user, \App\Notifications\StatusChangedNotification::class);
    }
}
