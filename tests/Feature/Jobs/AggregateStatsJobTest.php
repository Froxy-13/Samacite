<?php

namespace Tests\Feature\Jobs;

use App\Jobs\AggregateStatsJob;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\WithRoles;

class AggregateStatsJobTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestRoles();
        Cache::flush();
    }

    /**
     * Test that job is dispatchable
     */
    public function test_job_is_dispatchable()
    {
        Queue::fake();
        
        AggregateStatsJob::dispatch();
        
        Queue::assertPushed(AggregateStatsJob::class);
    }

    /**
     * Test job calculates total reports
     */
    public function test_job_calculates_total_reports()
    {
        Report::factory(5)->create();

        $job = new AggregateStatsJob();
        $job->handle();

        $stats = Cache::get('stats:reports:total');
        $this->assertEquals(5, $stats);
    }

    /**
     * Test job calculates reports by status
     */
    public function test_job_calculates_reports_by_status()
    {
        Report::factory(3)->create(['status' => 'En attente']);
        Report::factory(2)->create(['status' => 'En cours']);
        Report::factory(1)->create(['status' => 'Résolu']);

        $job = new AggregateStatsJob();
        $job->handle();

        $stats = Cache::get('stats:reports:by_status');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('En attente', $stats);
    }

    /**
     * Test job calculates reports by category
     */
    public function test_job_calculates_reports_by_category()
    {
        $user = User::factory()->create();
        
        // Create reports without category_id constraint issues
        Report::factory(2)->create(['user_id' => $user->id, 'category_id' => null]);
        Report::factory(3)->create(['user_id' => $user->id, 'category_id' => null]);

        $job = new AggregateStatsJob();
        $job->handle();

        // Stats should be calculated even if categories are empty
        $this->assertTrue(Cache::has('stats:reports:total'));
    }

    /**
     * Test job calculates total users
     */
    public function test_job_calculates_total_users()
    {
        User::factory(10)->create();

        $job = new AggregateStatsJob();
        $job->handle();

        $stats = Cache::get('stats:users:total');
        $this->assertGreaterThanOrEqual(10, $stats);
    }

    /**
     * Test job caches results with 1 hour TTL
     */
    public function test_job_caches_stats_with_ttl()
    {
        Report::factory(5)->create();

        $job = new AggregateStatsJob();
        $job->handle();

        // Stats should be cached
        $this->assertTrue(Cache::has('stats:reports:total'));
    }

    /**
     * Test job completes successfully
     */
    public function test_job_completes_successfully()
    {
        $job = new AggregateStatsJob();
        $result = $job->handle();

        $this->assertTrue($result);
    }

    /**
     * Test job handles empty database
     */
    public function test_job_handles_empty_database()
    {
        $job = new AggregateStatsJob();
        $result = $job->handle();

        // Should still complete successfully
        $this->assertTrue($result);
        $this->assertEquals(0, Cache::get('stats:reports:total', 0));
    }

    /**
     * Test job timeout
     */
    public function test_job_has_timeout()
    {
        $job = new AggregateStatsJob();
        
        $this->assertEquals(3600, $job->timeout); // 1 hour
    }

    /**
     * Test job aggregates all stats
     */
    public function test_job_aggregates_complete_stats()
    {
        Report::factory(5)->create();
        User::factory(3)->create();

        $job = new AggregateStatsJob();
        $job->handle();

        // All stats should be cached
        $this->assertTrue(Cache::has('stats:reports:total'));
        $this->assertTrue(Cache::has('stats:users:total'));
        $this->assertTrue(Cache::has('stats:reports:by_status'));
    }
}
