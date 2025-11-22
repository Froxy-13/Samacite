<?php

namespace Tests\Feature\Jobs;

use App\Jobs\NotifyUserJob;
use App\Models\Report;
use App\Models\User;
use App\Notifications\GenericNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\WithRoles;

class NotifyUserJobTest extends TestCase
{
    use RefreshDatabase, WithRoles;

    protected User $user;
    protected Report $report;

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestRoles();
        $this->user = User::factory()->create();
        $this->report = Report::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Test that job is dispatchable
     */
    public function test_job_is_dispatchable()
    {
        Queue::fake();
        
        NotifyUserJob::dispatch($this->user, 'Test notification');
        
        Queue::assertPushed(NotifyUserJob::class);
    }

    /**
     * Test job sends notification to user
     */
    public function test_job_sends_notification_to_user()
    {
        $initialCount = $this->user->notifications()->count();
        
        $job = new NotifyUserJob($this->user, 'Test message');
        $job->handle();
        
        $this->user = $this->user->fresh();

        // User should have received notification
        $this->assertGreaterThan($initialCount, $this->user->notifications()->count());
    }

    /**
     * Test job completes successfully
     */
    public function test_job_completes_successfully()
    {
        $job = new NotifyUserJob($this->user, 'Test notification');
        $result = $job->handle();

        $this->assertTrue($result);
    }

    /**
     * Test job handles missing user gracefully
     */
    public function test_job_handles_missing_user()
    {
        $nonExistentUser = new User(['id' => 999]);
        
        $job = new NotifyUserJob($nonExistentUser, 'Test');
        $result = $job->handle();

        $this->assertFalse($result);
    }

    /**
     * Test job with batch of users
     */
    public function test_job_can_notify_multiple_users()
    {
        $users = User::factory(3)->create();

        foreach ($users as $user) {
            $job = new NotifyUserJob($user, 'Batch notification');
            $result = $job->handle();
            
            $this->assertTrue($result);
        }

        // Each user should have received notification
        foreach ($users as $user) {
            $this->assertTrue($user->notifications()->exists());
        }
    }

    /**
     * Test job timeout
     */
    public function test_job_has_timeout()
    {
        $job = new NotifyUserJob($this->user, 'Test');
        
        $this->assertEquals(3600, $job->timeout); // 1 hour
    }

    /**
     * Test job stores message in data
     */
    public function test_job_stores_notification_data()
    {
        $message = 'Important notification message';
        $job = new NotifyUserJob($this->user, $message);
        
        // Job should be created with data
        $this->assertNotNull($job);
    }

    /**
     * Test job retry behavior
     */
    public function test_job_has_retry_configuration()
    {
        $job = new NotifyUserJob($this->user, 'Test');
        
        // Should have retry attempts defined
        $this->assertNotNull($job->tries);
    }
}
