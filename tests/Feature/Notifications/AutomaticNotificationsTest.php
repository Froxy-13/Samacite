<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\Report;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class AutomaticNotificationsTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user1;
    protected User $user2;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
        
        // Créer des utilisateurs de test
        $this->user1 = User::factory()->create(['nom' => 'Diop', 'prenom' => 'Amadou']);
        $this->user1->assignRole('Utilisateur');
        
        $this->user2 = User::factory()->create(['nom' => 'Fall', 'prenom' => 'Fatou']);
        $this->user2->assignRole('Utilisateur');
        
        // Créer une catégorie de test
        $this->category = Category::factory()->create();
    }

    public function test_notification_sent_when_report_created(): void
    {
        // Compter les notifications avant
        $beforeCount = $this->user1->notifications()->count();

        // Créer un signalement
        $report = Report::create([
            'title' => 'Test Report',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 14.6928,
            'longitude' => -17.4467,
            'status' => 'en_attente',
            'category_id' => $this->category->id,
            'user_id' => $this->user1->id,
        ]);

        // Vérifier qu'une notification a été créée
        $afterCount = $this->user1->fresh()->notifications()->count();
        $this->assertGreaterThan($beforeCount, $afterCount);

        // Vérifier le contenu de la notification
        $notification = $this->user1->fresh()->notifications()->first();
        $data = $notification->data;
        $this->assertEquals('report_created', $data['type']);
        $this->assertEquals($report->id, $data['report_id']);
    }

    public function test_notification_sent_when_comment_added(): void
    {
        // Créer un signalement de user1
        $report = Report::create([
            'title' => 'Test Report',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 14.6928,
            'longitude' => -17.4467,
            'status' => 'en_attente',
            'category_id' => $this->category->id,
            'user_id' => $this->user1->id,
        ]);

        $beforeCount = $this->user1->fresh()->notifications()->count();

        // User2 commente le signalement de user1
        $comment = Comment::create([
            'report_id' => $report->id,
            'user_id' => $this->user2->id,
            'content' => 'Test comment',
        ]);

        // Vérifier qu'une notification a été envoyée à user1
        $afterCount = $this->user1->fresh()->notifications()->count();
        $this->assertGreaterThan($beforeCount, $afterCount);

        // Vérifier le contenu (prendre la dernière notification après le count initial)
        $notification = $this->user1->fresh()->notifications()->orderBy('created_at', 'desc')->skip($beforeCount)->first();
        $data = $notification->data;
        $this->assertEquals('comment_created', $data['type']);
        $this->assertEquals($comment->id, $data['comment_id']);
    }

    public function test_no_notification_when_owner_comments_own_report(): void
    {
        // Créer un signalement
        $report = Report::create([
            'title' => 'Test Report',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 14.6928,
            'longitude' => -17.4467,
            'status' => 'en_attente',
            'category_id' => $this->category->id,
            'user_id' => $this->user1->id,
        ]);

        $beforeCount = $this->user1->fresh()->notifications()->count();

        // User1 commente son propre signalement
        Comment::create([
            'report_id' => $report->id,
            'user_id' => $this->user1->id,
            'content' => 'Own comment',
        ]);

        // Pas de notification supplémentaire (déjà 1 pour la création du report)
        $afterCount = $this->user1->fresh()->notifications()->count();
        $this->assertEquals($beforeCount, $afterCount);
    }

    public function test_notification_sent_when_report_liked(): void
    {
        // Créer un signalement de user1
        $report = Report::create([
            'title' => 'Test Report',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 14.6928,
            'longitude' => -17.4467,
            'status' => 'en_attente',
            'category_id' => $this->category->id,
            'user_id' => $this->user1->id,
        ]);

        $beforeCount = $this->user1->fresh()->notifications()->count();

        // User2 like le signalement
        $like = Like::create([
            'report_id' => $report->id,
            'user_id' => $this->user2->id,
        ]);

        // Vérifier la notification
        $afterCount = $this->user1->fresh()->notifications()->count();
        $this->assertGreaterThan($beforeCount, $afterCount);

        $notification = $this->user1->fresh()->notifications()->orderBy('created_at', 'desc')->skip($beforeCount)->first();
        $data = $notification->data;
        $this->assertEquals('report_liked', $data['type']);
        $this->assertEquals($like->id, $data['like_id']);
    }

    public function test_no_notification_when_owner_likes_own_report(): void
    {
        // Créer un signalement
        $report = Report::create([
            'title' => 'Test Report',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 14.6928,
            'longitude' => -17.4467,
            'status' => 'en_attente',
            'category_id' => $this->category->id,
            'user_id' => $this->user1->id,
        ]);

        $beforeCount = $this->user1->fresh()->notifications()->count();

        // User1 like son propre signalement
        Like::create([
            'report_id' => $report->id,
            'user_id' => $this->user1->id,
        ]);

        // Pas de notification supplémentaire
        $afterCount = $this->user1->fresh()->notifications()->count();
        $this->assertEquals($beforeCount, $afterCount);
    }

    public function test_notification_sent_when_status_changes(): void
    {
        // Créer un signalement
        $report = Report::create([
            'title' => 'Test Report',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 14.6928,
            'longitude' => -17.4467,
            'status' => 'en_attente',
            'category_id' => $this->category->id,
            'user_id' => $this->user1->id,
        ]);

        $beforeCount = $this->user1->fresh()->notifications()->count();

        // Changer le statut
        $report->update(['status' => 'en_cours']);

        // Vérifier la notification
        $afterCount = $this->user1->fresh()->notifications()->count();
        $this->assertGreaterThan($beforeCount, $afterCount);

        $notification = $this->user1->fresh()->notifications()->orderBy('created_at', 'desc')->skip($beforeCount)->first();
        $data = $notification->data;
        $this->assertEquals('status_changed', $data['type']);
        $this->assertEquals('en_attente', $data['old_status']);
        $this->assertEquals('en_cours', $data['new_status']);
    }

    public function test_admins_receive_notification_when_report_created(): void
    {
        // Créer un admin
        $admin = User::factory()->create(['nom' => 'Admin', 'prenom' => 'Alpha']);
        $admin->assignRole('Admin');

        $beforeCount = $admin->notifications()->count();

        // Créer un signalement par user1
        $report = Report::create([
            'title' => 'Test Report for Admin',
            'description' => 'Test Description',
            'address' => 'Test Address',
            'latitude' => 14.6928,
            'longitude' => -17.4467,
            'status' => 'en_attente',
            'category_id' => $this->category->id,
            'user_id' => $this->user1->id,
        ]);

        // Vérifier que l'admin a reçu une notification
        $afterCount = $admin->fresh()->notifications()->count();
        $this->assertGreaterThan($beforeCount, $afterCount);

        // Vérifier le contenu de la notification
        $notification = $admin->fresh()->notifications()->orderBy('created_at', 'desc')->first();
        $data = $notification->data;
        $this->assertEquals('report_created', $data['type']);
        $this->assertEquals($report->id, $data['report_id']);
        $this->assertStringContainsString('nouveau signalement', $data['message']);
    }
}
