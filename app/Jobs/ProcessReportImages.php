<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProcessReportImages implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour
    public int $tries = 3;

    // Configuration de compression
    private const MAX_WIDTH = 1920;
    private const MAX_HEIGHT = 1920;
    private const THUMBNAIL_SIZE = 300;
    private const QUALITY = 85;

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
            $fullPath = storage_path('app/public/' . $image->image_path);
            
            if (!file_exists($fullPath)) {
                Log::warning('Image file not found', [
                    'image_id' => $image->id,
                    'path' => $fullPath,
                ]);
                return;
            }

            $originalSize = filesize($fullPath);

            // Charger et compresser l'image
            $img = Image::read($fullPath);
            
            // Redimensionner si nécessaire (garde le ratio)
            if ($img->width() > self::MAX_WIDTH || $img->height() > self::MAX_HEIGHT) {
                $img->scale(
                    width: self::MAX_WIDTH,
                    height: self::MAX_HEIGHT
                );
            }

            // Sauvegarder avec compression
            $img->save($fullPath, quality: self::QUALITY);

            // Créer une miniature
            $this->createThumbnail($image->image_path);

            $newSize = filesize($fullPath);
            $reduction = round((($originalSize - $newSize) / $originalSize) * 100, 2);

            Log::info('Image processed successfully', [
                'image_id' => $image->id,
                'original_size' => $this->formatBytes($originalSize),
                'new_size' => $this->formatBytes($newSize),
                'reduction' => $reduction . '%',
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create thumbnail for image
     */
    private function createThumbnail(string $imagePath): void
    {
        try {
            $fullPath = storage_path('app/public/' . $imagePath);
            $thumbnailPath = str_replace('reports/', 'reports/thumbnails/', $imagePath);
            $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);

            // Créer le dossier thumbnails s'il n'existe pas
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Créer la miniature
            $img = Image::read($fullPath);
            $img->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
            $img->save($thumbnailFullPath, quality: self::QUALITY);

            Log::info('Thumbnail created', [
                'original' => $imagePath,
                'thumbnail' => $thumbnailPath,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to create thumbnail', [
                'path' => $imagePath,
                'error' => $e->getMessage(),
            ]);
            // Ne pas faire échouer le job si la miniature échoue
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
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
