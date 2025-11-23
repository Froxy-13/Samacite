<?php

namespace App\Http\Controllers\Stats;

use App\Http\Controllers\BaseController;
use App\Http\Resources\StatsResource;
use App\Models\Category;
use App\Services\StatsService;
use Illuminate\Http\Request;

class StatsController extends BaseController
{
    public function __construct(
        private StatsService $statsService
    ) {}

    /**
     * Obtenir les statistiques globales
     * 
     * @authenticated
     * @permission stats.view
     */
    public function global()
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->can('stats.view')) {
            return $this->forbidden('Vous n\'avez pas la permission d\'accéder aux statistiques');
        }

        $stats = $this->statsService->getGlobalStats();

        return $this->success(
            new StatsResource($stats),
            'Statistiques globales récupérées avec succès'
        );
    }

    /**
     * Obtenir les statistiques d'un utilisateur
     * 
     * @authenticated
     */
    public function user(Request $request, ?int $userId = null)
    {
        $authUser = auth('sanctum')->user();

        if (!$authUser) {
            return $this->unauthorized('Non authentifié');
        }

        // Si userId est fourni, l'admin peut voir les stats d'un autre utilisateur
        if ($userId) {
            if (!$authUser->can('stats.view')) {
                return $this->forbidden('Vous n\'avez pas la permission d\'accéder aux statistiques');
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                return $this->notFound('Utilisateur non trouvé');
            }
        } else {
            // Sinon, montrer les stats de l'utilisateur courant
            $user = $authUser;
        }

        $stats = $this->statsService->getUserStats($user);

        return $this->success(
            new StatsResource($stats),
            'Statistiques utilisateur récupérées avec succès'
        );
    }

    /**
     * Obtenir les statistiques d'une catégorie
     * 
     * @authenticated
     * @permission stats.view
     */
    public function category(int $categoryId)
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->can('stats.view')) {
            return $this->forbidden('Vous n\'avez pas la permission d\'accéder aux statistiques');
        }

        $category = Category::find($categoryId);

        if (!$category) {
            return $this->notFound('Catégorie non trouvée');
        }

        $stats = $this->statsService->getCategoryStats($category);

        return $this->success(
            new StatsResource($stats),
            'Statistiques de la catégorie récupérées avec succès'
        );
    }

    /**
     * Obtenir la timeline des signalements
     * 
     * @authenticated
     * @permission stats.view
     */
    public function timeline(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->can('stats.view')) {
            return $this->forbidden('Vous n\'avez pas la permission d\'accéder aux statistiques');
        }

        // Paramètre de période (par défaut 7 jours)
        $period = $request->query('period', '7days');
        
        // Convertir la période en nombre de jours
        $days = match($period) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            '365days' => 365,
            default => 7
        };

        // Date de début
        $startDate = now()->subDays($days)->startOfDay();

        // Récupérer les signalements groupés par date
        $reportsData = \App\Models\Report::query()
            ->where('created_at', '>=', $startDate)
            ->select(
                \Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN status = "En attente" THEN 1 ELSE 0 END) as pending'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN status = "En cours" THEN 1 ELSE 0 END) as in_progress'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN status = "Résolu" THEN 1 ELSE 0 END) as resolved')
            )
            ->groupBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        // Générer tous les jours de la période, même ceux sans signalements
        $timeline = [];
        $currentDate = $startDate->copy();
        $endDate = now()->endOfDay();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            
            if (isset($reportsData[$dateStr])) {
                $data = $reportsData[$dateStr];
                $timeline[] = [
                    'date' => $dateStr,
                    'total' => (int) $data->count,
                    'by_status' => [
                        'pending' => (int) ($data->pending ?? 0),
                        'in_progress' => (int) ($data->in_progress ?? 0),
                        'resolved' => (int) ($data->resolved ?? 0),
                    ]
                ];
            } else {
                // Ajouter un jour avec 0 signalements
                $timeline[] = [
                    'date' => $dateStr,
                    'total' => 0,
                    'by_status' => [
                        'pending' => 0,
                        'in_progress' => 0,
                        'resolved' => 0,
                    ]
                ];
            }
            
            $currentDate->addDay();
        }

        return $this->success(
            [
                'period' => $period,
                'days' => $days,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'timeline' => $timeline
            ],
            'Timeline des signalements récupérée avec succès'
        );
    }
}
