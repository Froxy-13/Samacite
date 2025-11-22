<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateStatsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        try {
            Log::info('AggregateStatsJob: Starting aggregation');

            // Calculate total reports
            $totalReports = DB::table('reports')->count();
            Cache::put('stats:reports:total', $totalReports, 3600); // 1 hour TTL

            // Calculate reports by status
            $reportsByStatus = DB::table('reports')
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->map(fn($item) => $item->count)
                ->toArray();
            Cache::put('stats:reports:by_status', $reportsByStatus, 3600);

            // Calculate reports by category
            $reportsByCategory = DB::table('reports')
                ->join('categories', 'reports.category_id', '=', 'categories.id')
                ->select('categories.name', DB::raw('count(*) as count'))
                ->groupBy('categories.name')
                ->get()
                ->keyBy('name')
                ->map(fn($item) => $item->count)
                ->toArray();
            Cache::put('stats:reports:by_category', $reportsByCategory, 3600);

            // Calculate total users
            $totalUsers = DB::table('users')->count();
            Cache::put('stats:users:total', $totalUsers, 3600);

            // Calculate users by role
            $usersByRole = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', DB::raw('count(distinct users.id) as count'))
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->groupBy('roles.name')
                ->get()
                ->keyBy('name')
                ->map(fn($item) => $item->count)
                ->toArray();
            Cache::put('stats:users:by_role', $usersByRole, 3600);

            // Calculate average reports per user
            $avgReportsPerUser = $totalUsers > 0 ? $totalReports / $totalUsers : 0;
            Cache::put('stats:reports:average_per_user', round($avgReportsPerUser, 2), 3600);

            Log::info('AggregateStatsJob: Completed successfully', [
                'total_reports' => $totalReports,
                'total_users' => $totalUsers,
                'reports_by_status' => count($reportsByStatus),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('AggregateStatsJob: Error aggregating statistics', [
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
        Log::error('AggregateStatsJob job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
