<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use App\Http\Resources\LikeResource;
use App\Models\Like;
use App\Models\Report;
use App\Repositories\Contracts\LikeRepositoryInterface;

class LikeController extends BaseController
{
    private LikeRepositoryInterface $likeRepository;

    public function __construct(LikeRepositoryInterface $likeRepository)
    {
        $this->likeRepository = $likeRepository;
    }

    /**
     * Display a listing of likes for a report.
     */
    public function index($reportId)
    {
        $report = Report::find($reportId);
        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        $perPage = request('per_page', 15);
        $likes = $this->likeRepository->findByReportId($reportId, $perPage);

        return $this->success(
            LikeResource::collection($likes),
            'Likes récupérés avec succès'
        );
    }

    /**
     * Store a like (toggle like/unlike).
     */
    public function store($reportId)
    {
        $report = Report::find($reportId);
        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        $this->authorize('create', Like::class);

        $userId = auth('sanctum')->user()->id;

        // Check if user already liked this report
        $existingLike = $this->likeRepository->findByReportIdAndUserId($reportId, $userId);

        if ($existingLike) {
            return $this->error(
                'L\'utilisateur a déjà aimé ce signalement',
                409
            );
        }

        $like = $this->likeRepository->create([
            'report_id' => $reportId,
            'user_id' => $userId,
        ]);

        return $this->success(
            new LikeResource($like),
            'Signalement aimé avec succès',
            201
        );
    }

    /**
     * Remove the like from storage (unlike).
     */
    public function destroy($reportId)
    {
        $report = Report::find($reportId);
        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        $userId = auth('sanctum')->user()->id;
        $like = $this->likeRepository->findByReportIdAndUserId($reportId, $userId);

        if (!$like) {
            return $this->notFound('Like non trouvé');
        }

        $this->authorize('delete', $like);

        $this->likeRepository->delete($like->id);

        return $this->success([], 'Signalement non aimé avec succès');
    }

    /**
     * Check if current user liked the report.
     */
    public function check($reportId)
    {
        $report = Report::find($reportId);
        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        $userId = auth('sanctum')->user()->id;
        $liked = $this->likeRepository->findByReportIdAndUserId($reportId, $userId) !== null;

        return $this->success(
            ['liked' => $liked],
            'Statut du like récupéré avec succès'
        );
    }

    /**
     * Remove a like by ID (admin only).
     */
    public function destroyById($id)
    {
        $like = Like::find($id);
        if (!$like) {
            return $this->notFound('Like non trouvé');
        }

        $this->authorize('delete', $like);

        $this->likeRepository->delete($like->id);

        return $this->success([], 'Like supprimé avec succès');
    }
}
