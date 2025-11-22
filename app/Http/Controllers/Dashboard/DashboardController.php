<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseController;
use App\Http\Resources\StatsResource;
use App\Services\StatsService;

class DashboardController extends BaseController
{
    public function __construct(
        private StatsService $statsService
    ) {}

    /**
     * Obtenir les données du dashboard admin
     * 
     * @authenticated
     * @permission dashboard.access
     */
    public function index()
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->can('dashboard.access')) {
            return $this->forbidden('Vous n\'avez pas la permission d\'accéder au dashboard');
        }

        // Récupérer toutes les statistiques pour le dashboard
        $globalStats = $this->statsService->getGlobalStats();
        
        // Récupérer les 5 derniers signalements
        $recentReports = \App\Models\Report::with(['user', 'category'])
            ->latest()
            ->take(5)
            ->get();

        // Récupérer les catégories les plus actives
        $topCategories = \App\Models\Category::withCount('reports')
            ->orderBy('reports_count', 'desc')
            ->take(5)
            ->get();

        $dashboardData = [
            'stats' => $globalStats,
            'recent_reports' => $recentReports->map(fn($report) => [
                'id' => $report->id,
                'title' => $report->title,
                'status' => $report->status,
                'category' => $report->category?->name,
                'user' => $report->user?->name,
                'created_at' => $report->created_at
            ]),
            'top_categories' => $topCategories->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'reports_count' => $cat->reports_count
            ]),
        ];

        return $this->success(
            $dashboardData,
            'Données du dashboard récupérées avec succès'
        );
    }
}
