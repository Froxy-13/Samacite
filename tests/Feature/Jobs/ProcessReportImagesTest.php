<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessReportImages;
use App\Models\Report;
use App\Models\ReportImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithRoles;

class ProcessReportImagesTest extends TestCase
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
        
        ProcessReportImages::dispatch($this->report);
        
        Queue::assertPushed(ProcessReportImages::class);
    }

    /**
     * Test job processes images for a report
     */
    public function test_job_processes_images_for_report()
    {
        Storage::fake('public');

        // Create test image
        $image = ReportImage::create([
            'report_id' => $this->report->id,
            'image_path' => 'reports/test.jpg',
        ]);

        $job = new ProcessReportImages($this->report);
        $job->handle();

        // Image should still exist
        $this->assertDatabaseHas('report_images', [
            'id' => $image->id,
            'report_id' => $this->report->id,
        ]);
    }

    /**
     * Test job marks report as processed
     */
    public function test_job_completes_successfully()
    {
        $job = new ProcessReportImages($this->report);
        $result = $job->handle();

        $this->assertTrue($result);
    }

    /**
     * Test job handles missing report gracefully
     */
    public function test_job_handles_missing_report()
    {
        $nonExistentReport = new Report(['id' => 999]);
        
        $job = new ProcessReportImages($nonExistentReport);
        $result = $job->handle();

        // Should return false or handle gracefully
        $this->assertFalse($result);
    }

    /**
     * Test job processes multiple images
     */
    public function test_job_processes_multiple_images()
    {
        Storage::fake('public');

        // Create multiple images
        $images = ReportImage::factory(3)->create([
            'report_id' => $this->report->id,
        ]);

        $job = new ProcessReportImages($this->report);
        $result = $job->handle();

        $this->assertTrue($result);
        $this->assertEquals(3, $this->report->images()->count());
    }

    /**
     * Test job timeout
     */
    public function test_job_has_timeout()
    {
        $job = new ProcessReportImages($this->report);
        
        $this->assertEquals(3600, $job->timeout); // 1 hour
    }

    /**
     * Test job is queued on default queue
     */
    public function test_job_is_queued_on_default()
    {
        $job = new ProcessReportImages($this->report);
        
        $this->assertNull($job->queue);
    }
}
