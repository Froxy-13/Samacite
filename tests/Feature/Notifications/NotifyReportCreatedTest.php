<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
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
        Notification::fake();

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

        // Check if admin received notification
        Notification::assertSentTo($admin, \App\Notifications\ReportCreatedNotification::class);
    }

    public function test_admin_receives_new_report_notification()
    {
        Notification::fake();

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

        Notification::assertSentTo($admin, \App\Notifications\ReportCreatedNotification::class);
    }

    public function test_notification_contains_report_info()
    {
        Notification::fake();

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

        Notification::assertSentTo(
            $admin,
            \App\Notifications\ReportCreatedNotification::class,
            function ($notification) {
                return isset($notification->report->id) &&
                       isset($notification->report->title);
            }
        );
    }

    public function test_only_admins_receive_new_report_notifications()
    {
        Notification::fake();

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

        // user1 (creator) should receive notification
        Notification::assertSentTo($user1, \App\Notifications\ReportCreatedNotification::class);
        
        // user2 (other user) should NOT receive notification
        Notification::assertNotSentTo($user2, \App\Notifications\ReportCreatedNotification::class);
    }
}
