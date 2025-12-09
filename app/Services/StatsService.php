<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /**
     * Obtenir les statistiques globales
     */
    public function getGlobalStats(): array
    {
        $cacheKey = 'cache:stats:global';
        
        $stats = Cache::get($cacheKey);
        
        if ($stats) {
            return $stats;
        }

        $totalReports = Report::count();
        $reportsByStatus = $this->getReportsByStatus();
        $resolvedCount = $reportsByStatus['Résolu']['count'] ?? 0;
        
        $stats = [
            'total_reports' => $totalReports,
            'reports_by_status' => $reportsByStatus,
            'reports_by_category' => $this->getReportsByCategory(),
            'total_users' => User::count(),
            'users_by_role' => $this->getUsersByRole(),
            'average_reports_per_user' => $this->getAverageReportsPerUser(),
            'resolution_rate' => $totalReports > 0 ? round(($resolvedCount / $totalReports) * 100, 1) : 0,
        ];

        Cache::put($cacheKey, $stats, 3600);
        return $stats;
    }

    /**
     * Obtenir les statistiques d'un utilisateur
     */
    public function getUserStats(User $user): array
    {
        $cacheKey = "cache:stats:user:{$user->id}";
        
        $stats = Cache::get($cacheKey);
        
        if ($stats) {
            return $stats;
        }

        $totalComments = 0;
        $totalLikesGiven = 0;
        
        try {
            $totalComments = DB::table('comments')->where('user_id', $user->id)->count();
            $totalLikesGiven = DB::table('likes')->where('user_id', $user->id)->count();
        } catch (\Exception $e) {
            // Table doesn't exist or relationship issue
        }

        $stats = [
            'user_id' => $user->id,
            'total_reports' => $user->reports()->count(),
            'reports_by_status' => $this->getUserReportsByStatus($user),
            'reports_by_category' => $this->getUserReportsByCategory($user),
            'total_comments' => $totalComments,
            'total_likes_given' => $totalLikesGiven,
            'total_likes_received' => 0,
        ];

        Cache::put($cacheKey, $stats, 3600);
        return $stats;
    }

    /**
     * Obtenir les statistiques d'une catégorie
     */
    public function getCategoryStats(Category $category): array
    {
        $cacheKey = "stats:category:{$category->id}";
        
        $stats = Cache::get($cacheKey);
        
        if ($stats) {
            return $stats;
        }

        $totalReports = $category->reports()->count();
        // Use pluck + unique instead of select + distinct for accurate count
        $totalUsers = $category->reports()->pluck('user_id')->unique()->count();
        
        $stats = [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'total_reports' => $totalReports,
            'reports_by_status' => $this->getCategoryReportsByStatus($category),
            'total_users_reported' => $totalUsers,
            'average_reports_per_user' => $totalUsers > 0 ? round($totalReports / $totalUsers, 2) : 0,
        ];

        Cache::put($cacheKey, $stats, 3600);
        return $stats;
    }

    /**
     * Obtenir les signalements par statut (global)
     */
    private function getReportsByStatus(): array
    {
        $statuses = ['En attente', 'En cours', 'Résolu'];
        $result = [];
        $totalReports = Report::count();

        foreach ($statuses as $status) {
            $count = Report::where('status', $status)->count();
            $result[$status] = [
                'count' => $count,
                'percentage' => $totalReports > 0 ? round(($count / $totalReports) * 100, 2) : 0
            ];
        }

        return $result;
    }

    /**
     * Obtenir les signalements par catégorie (global)
     */
    private function getReportsByCategory(): array
    {
        $categories = Category::withCount('reports')
            ->where('reports_count', '>', 0)
            ->get(['id', 'name', 'reports_count']);
        
        $totalReports = Report::count();

        return $categories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'count' => $cat->reports_count,
            'percentage' => $totalReports > 0 ? round(($cat->reports_count / $totalReports) * 100, 2) : 0
        ])->toArray();
    }

    /**
     * Obtenir les utilisateurs par rôle
     */
    private function getUsersByRole(): array
    {
        $result = [];
        
        try {
            $result['Admin'] = User::whereHas('roles', function ($query) {
                $query->where('name', 'Admin');
            })->count();

            $result['Utilisateur'] = User::whereHas('roles', function ($query) {
                $query->where('name', 'Utilisateur');
            })->count();
        } catch (\Exception $e) {
            $result['Admin'] = 0;
            $result['Utilisateur'] = 0;
        }

        return $result;
    }

    /**
     * Calculer la moyenne de signalements par utilisateur
     */
    private function getAverageReportsPerUser(): float
    {
        $totalUsers = User::count();
        
        if ($totalUsers === 0) {
            return 0;
        }

        $totalReports = Report::count();
        return round($totalReports / $totalUsers, 2);
    }

    /**
     * Obtenir les signalements d'un utilisateur par statut
     */
    private function getUserReportsByStatus(User $user): array
    {
        $statuses = ['En attente', 'En cours', 'Résolu'];
        $result = [];

        foreach ($statuses as $status) {
            $result[$status] = $user->reports()->where('status', $status)->count();
        }

        return $result;
    }

    /**
     * Obtenir les signalements d'un utilisateur par catégorie
     */
    private function getUserReportsByCategory(User $user): array
    {
        return $user->reports()
            ->with('category')
            ->get()
            ->groupBy('category.name')
            ->map(fn($group, $categoryName) => [
                'name' => $categoryName,
                'count' => $group->count()
            ])
            ->values()
            ->toArray();
    }

    /**
     * Obtenir les signalements d'une catégorie par statut
     */
    private function getCategoryReportsByStatus(Category $category): array
    {
        $statuses = ['En attente', 'En cours', 'Résolu'];
        $result = [];

        foreach ($statuses as $status) {
            $result[$status] = $category->reports()->where('status', $status)->count();
        }

        return $result;
    }

    /**
     * Invalider le cache des statistiques
     */
    public function invalidateCache(): void
    {
        Cache::forget('stats:global');
    }
}
