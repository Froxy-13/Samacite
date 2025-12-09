<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $beforeCount = $reportOwner->notifications()->count();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes")
            ->assertStatus(201);

        // Check notification was created in database via Observer
        $this->assertEquals($beforeCount + 1, $reportOwner->notifications()->count());
        
        // Skip report_created notification and get report_liked
        $notification = $reportOwner->notifications()
            ->orderBy('created_at', 'desc')
            ->skip($beforeCount)
            ->first();
        $this->assertEquals('report_liked', $notification->data['type']);
        $this->assertEquals($report->id, $notification->data['report_id']);
    }

    public function test_report_owner_receives_like_notification()
    {
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

        $beforeCount = $reportOwner->notifications()->count();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes");

        // Check notification sent to report owner (skip report_created)
        $notification = $reportOwner->notifications()
            ->orderBy('created_at', 'desc')
            ->skip($beforeCount)
            ->first();
        $this->assertNotNull($notification);
        $this->assertEquals('report_liked', $notification->data['type']);
    }

    public function test_notification_contains_like_info()
    {
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

        $beforeCount = $reportOwner->notifications()->count();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes");

        // Check notification data contains like info (skip report_created)
        $notification = $reportOwner->notifications()
            ->orderBy('created_at', 'desc')
            ->skip($beforeCount)
            ->first();
        $this->assertNotNull($notification);
        $this->assertEquals('report_liked', $notification->data['type']);
        $this->assertEquals($report->id, $notification->data['report_id']);
        $this->assertArrayHasKey('like_id', $notification->data);
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

        // Count notifications before (should have report_created)
        $beforeCount = $reportOwner->notifications()->count();

        $this->actingAs($reportOwner, 'sanctum')
            ->postJson("/api/reports/{$report->id}/likes");

        // No new notification should be added (self-notification prevention)
        $this->assertEquals($beforeCount, $reportOwner->notifications()->count());
    }
}
