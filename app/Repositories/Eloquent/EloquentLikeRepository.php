<?php

namespace App\Repositories\Eloquent;

use App\Models\Like;
use App\Repositories\Contracts\LikeRepositoryInterface;

class EloquentLikeRepository implements LikeRepositoryInterface
{
    public function paginateWithFilters(int $perPage = 15, array $filters = [])
    {
        $query = Like::query();

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
        return Like::with($relations)->find($id);
    }

    public function create(array $data)
    {
        return Like::create($data);
    }

    public function delete(int $id)
    {
        return Like::destroy($id);
    }

    public function findByReportIdAndUserId(int $reportId, int $userId)
    {
        return Like::where('report_id', $reportId)
            ->where('user_id', $userId)
            ->first();
    }

    public function findByReportId(int $reportId, int $perPage = 15)
    {
        return Like::where('report_id', $reportId)
            ->with(['user'])
            ->latest('created_at')
            ->paginate($perPage);
    }
}
