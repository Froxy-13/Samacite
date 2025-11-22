<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Repositories\Eloquent\EloquentReportRepository;
use App\Services\CacheService;
use Illuminate\Http\Request;

class ReportController extends BaseController
{
    protected EloquentReportRepository $reportRepository;

    public function __construct(CacheService $cacheService)
    {
        $this->reportRepository = new EloquentReportRepository($cacheService);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Check permission using policy
        $this->authorize('viewAny', Report::class);

        // Regular users see only their own reports
        if (!$user->hasRole('Admin')) {
            $reports = $this->reportRepository->findByUser($user->id);
        } else {
            // Admin can view all and filter
            $filters = $request->only(['status', 'category_id', 'search']);
            $reports = $this->reportRepository->paginateWithFilters(15, $filters);
        }

        return $this->success(
            ReportResource::collection($reports),
            'Signalements récupérés avec succès'
        );
    }

    public function show($id)
    {
        $report = $this->reportRepository->findWithRelations($id);

        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        // Check authorization using policy
        $this->authorize('view', $report);

        return $this->success(
            new ReportResource($report),
            'Signalement récupéré avec succès'
        );
    }

    public function store(Request $request)
    {
        // Check permission using policy - wrapped in try-catch to handle authorization
        try {
            $this->authorize('create', Report::class);
        } catch (\Illuminate\Auth\AuthorizationException $e) {
            return $this->forbidden('Vous n\'avez pas la permission de créer des signalements');
        }

        // Validate
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $validated['user_id'] = auth('sanctum')->user()->id;

        $report = $this->reportRepository->create($validated);
        $report = $this->reportRepository->findWithRelations($report->id);

        return $this->success(
            new ReportResource($report),
            'Signalement créé avec succès',
            201
        );
    }

    public function update(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        // Check authorization using policy
        $this->authorize('update', $report);

        // Validate
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        $report = $this->reportRepository->update($id, $validated);
        $report = $this->reportRepository->findWithRelations($id);

        return $this->success(
            new ReportResource($report),
            'Signalement mis à jour avec succès'
        );
    }

    public function destroy($id)
    {
        $report = Report::find($id);

        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        // Check authorization using policy
        $this->authorize('delete', $report);

        $this->reportRepository->delete($id);

        return $this->success(null, 'Signalement supprimé avec succès');
    }

    public function changeStatus(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        // Check authorization using policy - only admins can change status
        $this->authorize('changeStatus', $report);

        // Validate
        $validated = $request->validate([
            'status' => 'required|in:En attente,En cours,Résolu',
        ]);

        $report->updateStatus($validated['status']);
        $report = $this->reportRepository->findWithRelations($id);

        return $this->success(
            new ReportResource($report),
            'Statut du signalement mis à jour avec succès'
        );
    }
}

