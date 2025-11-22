<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessReportImages implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour
    public int $tries = 3;

    public function __construct(protected Report $report)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        try {
            // Check if report exists
            if (!$this->report->exists) {
                Log::warning('ProcessReportImages: Report not found', [
                    'report_id' => $this->report->id,
                ]);
                return false;
            }

            // Get all images for this report
            $images = $this->report->images()->get();

            if ($images->isEmpty()) {
                Log::info('ProcessReportImages: No images to process', [
                    'report_id' => $this->report->id,
                ]);
                return true;
            }

            // Process each image (in real implementation, would resize/compress)
            foreach ($images as $image) {
                $this->processImage($image);
            }

            Log::info('ProcessReportImages: Completed successfully', [
                'report_id' => $this->report->id,
                'images_processed' => $images->count(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('ProcessReportImages: Error processing images', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process individual image (resize, compress, etc.)
     */
    private function processImage($image): void
    {
        try {
            // In production, would use intervention/image to:
            // 1. Resize original to max dimensions
            // 2. Generate thumbnail (150x150)
            // 3. Optimize/compress
            // 4. Save to storage
            
            Log::info('Processing image', [
                'image_id' => $image->id,
                'path' => $image->image_path,
            ]);

            // Placeholder for actual image processing
            // $image->update(['processed' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessReportImages job failed', [
            'report_id' => $this->report->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
