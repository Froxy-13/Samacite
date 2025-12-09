<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Report;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Pagination\Paginator;

class CommentController extends BaseController
{
    private CommentRepositoryInterface $commentRepository;

    public function __construct(CommentRepositoryInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Display a listing of comments for a report.
     */
    public function index($reportId)
    {
        $report = Report::find($reportId);
        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        $perPage = request('per_page', 15);
        $comments = $this->commentRepository->findByReportId($reportId, $perPage);

        return $this->success(
            CommentResource::collection($comments),
            'Commentaires récupérés avec succès'
        );
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(StoreCommentRequest $request, $reportId)
    {
        $report = Report::find($reportId);
        if (!$report) {
            return $this->notFound('Signalement non trouvé');
        }

        $this->authorize('create', Comment::class);

        $data = array_merge(
            $request->validated(),
            [
                'report_id' => $reportId,
                'user_id' => auth('sanctum')->user()->id,
            ]
        );

        $comment = $this->commentRepository->create($data);

        // Notification automatique via CommentObserver::created()
        // Pas besoin de dispatch manuel

        return $this->success(
            new CommentResource($comment),
            'Commentaire créé avec succès',
            201
        );
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return $this->notFound('Commentaire non trouvé');
        }

        $this->authorize('delete', $comment);

        $this->commentRepository->delete($id);

        return $this->success([], 'Commentaire supprimé avec succès');
    }
}
