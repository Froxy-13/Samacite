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
            return $this->notFound('Report not found');
        }

        $perPage = request('per_page', 15);
        $comments = $this->commentRepository->findByReportId($reportId, $perPage);

        return $this->success(
            CommentResource::collection($comments),
            'Comments retrieved successfully'
        );
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(StoreCommentRequest $request, $reportId)
    {
        $report = Report::find($reportId);
        if (!$report) {
            return $this->notFound('Report not found');
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

        return $this->success(
            new CommentResource($comment),
            'Comment created successfully',
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
            return $this->notFound('Comment not found');
        }

        $this->authorize('delete', $comment);

        $this->commentRepository->delete($id);

        return $this->success([], 'Comment deleted successfully');
    }
}
