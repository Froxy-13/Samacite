<?php

namespace App\Observers;

use App\Models\Report;
use App\Services\CacheService;
use App\Notifications\GenericNotification;

class ReportObserver
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Report "created" event.
     */
    public function created(Report $report): void
    {
        $this->cacheService->invalidateReports();
        $this->cacheService->invalidateStats();
        
        // 🔔 NOTIFICATION : Signalement créé
        $report->user->notify(new GenericNotification(
            'Votre signalement a été créé avec succès',
            [
                'type' => 'report_created',
                'message' => 'Votre signalement a été créé avec succès',
                'report_id' => $report->id,
            ]
        ));
    }

    /**
     * Handle the Report "updated" event.
     */
    public function updated(Report $report): void
    {
        $this->cacheService->invalidateReports();
        $this->cacheService->invalidateStats();
        
        // 🔔 NOTIFICATION : Changement de statut
        if ($report->isDirty('status')) {
            $oldStatus = $report->getOriginal('status');
            $newStatus = $report->status;
            
            $report->user->notify(new GenericNotification(
                "Le statut de votre signalement a changé de {$oldStatus} à {$newStatus}",
                [
                    'type' => 'status_changed',
                    'message' => "Le statut de votre signalement a changé de {$oldStatus} à {$newStatus}",
                    'report_id' => $report->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]
            ));
        }
    }

    /**
     * Handle the Report "deleted" event.
     */
    public function deleted(Report $report): void
    {
        $this->cacheService->invalidateReports();
        $this->cacheService->invalidateStats();
    }

    /**
     * Handle the Report "restored" event.
     */
    public function restored(Report $report): void
    {
        $this->cacheService->invalidateReports();
        $this->cacheService->invalidateStats();
    }

    /**
     * Handle the Report "force deleted" event.
     */
    public function forceDeleted(Report $report): void
    {
        $this->cacheService->invalidateReports();
        $this->cacheService->invalidateStats();
    }
}
