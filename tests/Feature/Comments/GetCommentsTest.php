<?php

namespace Tests\Feature\Comments;

use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class GetCommentsTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_user_can_get_report_comments()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();
        
        $comment1 = Comment::factory()->create(['report_id' => $report->id]);
        $comment2 = Comment::factory()->create(['report_id' => $report->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/comments');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'content',
                    'user_id',
                    'report_id',
                    'user' => ['id', 'name', 'email'],
                ]
            ]
        ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_comments_are_paginated()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();
        
        Comment::factory(15)->create(['report_id' => $report->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/comments?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    public function test_unauthenticated_user_cannot_get_comments()
    {
        $report = Report::factory()->create();

        $response = $this->getJson('/api/reports/' . $report->id . '/comments');

        $response->assertStatus(401);
    }

    public function test_get_comments_for_nonexistent_report_returns_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/99999/comments');

        $response->assertStatus(404);
    }
}
