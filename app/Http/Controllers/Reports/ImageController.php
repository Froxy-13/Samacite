<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ReportImageResource;
use App\Jobs\ProcessReportImages;
use App\Models\Report;
use App\Models\ReportImage;
use App\Repositories\Contracts\ReportImageRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends BaseController
{
    public function __construct(
        private ReportImageRepositoryInterface $imageRepository
    ) {}

    /**
     * Upload an image to a report
     */
    public function store(Request $request, int $reportId)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->unauthorized('Non authentifié');
        }

        if (!$user->can('file.upload')) {
            return $this->forbidden('Vous n\'avez pas la permission d\'uploader des images');
        }

        $report = Report::find($reportId);

        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        // Check if user owns the report or is admin
        if ($report->user_id !== $user->id && !$user->hasRole('Admin')) {
            return $this->forbidden('Vous ne pouvez pas uploader des images pour ce signalement');
        }

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $path = $validated['image']->store('reports', 'public');

        $image = $this->imageRepository->create([
            'report_id' => $reportId,
            'image_path' => $path,
        ]);

        // Dispatch job to process the image (compression, resizing, etc.)
        ProcessReportImages::dispatch($report);

        return $this->success(
            new ReportImageResource($image),
            'Image uploadée avec succès',
            201
        );
    }

    /**
     * Delete an image
     */
    public function destroy(int $id)
    {
        $image = ReportImage::find($id);

        if (!$image) {
            return $this->notFound('Image non trouvée');
        }

        // Check if user owns the report or is admin
        $user = auth('sanctum')->user();
        if (!$user || ($image->report->user_id !== $user->id && !$user->hasRole('Admin'))) {
            return $this->forbidden('Vous ne pouvez pas supprimer cette image');
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $this->imageRepository->delete($id);

        return $this->success(null, 'Image supprimée avec succès');
    }

    /**
     * Get images for a report
     */
    public function getByReport(int $reportId)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->unauthorized('Non authentifié');
        }

        $report = Report::find($reportId);

        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        // Check if user can view this report
        if ($report->user_id !== $user->id && !$user->hasRole('Admin')) {
            return $this->forbidden('Vous ne pouvez pas voir les images de ce signalement');
        }

        $images = $this->imageRepository->getByReportId($reportId);

        return $this->success(
            ReportImageResource::collection($images),
            'Images récupérées avec succès'
        );
    }
}
