<?php

namespace App\Observers;

use App\Models\Report;
use App\Services\CacheService;

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
    }

    /**
     * Handle the Report "updated" event.
     */
    public function updated(Report $report): void
    {
        $this->cacheService->invalidateReports();
        $this->cacheService->invalidateStats();
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
