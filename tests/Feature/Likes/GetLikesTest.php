<?php

namespace Tests\Feature\Likes;

use App\Models\Like;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class GetLikesTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    public function test_user_can_get_report_likes()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        Like::factory(3)->create(['report_id' => $report->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'report_id',
                    'user' => ['id', 'name', 'email'],
                ]
            ]
        ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_likes_are_paginated()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        Like::factory(15)->create(['report_id' => $report->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/likes?per_page=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    public function test_user_can_check_if_they_liked_report()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        Like::factory()->create([
            'user_id' => $user->id,
            'report_id' => $report->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/likes/check');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => ['liked' => true],
        ]);
    }

    public function test_user_can_check_if_they_did_not_like_report()
    {
        $user = User::factory()->create();
        $user->assignRole('Utilisateur');
        
        $report = Report::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/' . $report->id . '/likes/check');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => ['liked' => false],
        ]);
    }

    public function test_unauthenticated_user_cannot_get_likes()
    {
        $report = Report::factory()->create();

        $response = $this->getJson('/api/reports/' . $report->id . '/likes');

        $response->assertStatus(401);
    }

    public function test_get_likes_for_nonexistent_report_returns_404()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/99999/likes');

        $response->assertStatus(404);
    }
}
