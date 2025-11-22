<?php

namespace App\Repositories\Eloquent;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function paginateWithFilters(int $perPage = 15, array $filters = [])
    {
        $query = Comment::query();

        if (isset($filters['report_id'])) {
            $query->where('report_id', $filters['report_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->with(['user', 'report'])
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function findWithRelations(int $id, array $relations = [])
    {
        $relations = empty($relations) ? ['user', 'report'] : $relations;
        return Comment::with($relations)->find($id);
    }

    public function create(array $data)
    {
        return Comment::create($data);
    }

    public function update(int $id, array $data)
    {
        $comment = Comment::find($id);
        if ($comment) {
            $comment->update($data);
        }
        return $comment;
    }

    public function delete(int $id)
    {
        return Comment::destroy($id);
    }

    public function findByReportId(int $reportId, int $perPage = 15)
    {
        return Comment::where('report_id', $reportId)
            ->with(['user'])
            ->latest('created_at')
            ->paginate($perPage);
    }
}
