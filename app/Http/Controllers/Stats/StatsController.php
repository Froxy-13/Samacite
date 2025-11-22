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
}
