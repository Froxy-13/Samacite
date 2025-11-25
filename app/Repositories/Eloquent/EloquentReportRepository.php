<?php

namespace App\Repositories\Eloquent;

use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Services\CacheService;

class EloquentReportRepository implements ReportRepositoryInterface
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function paginateWithFilters(int $perPage = 15, array $filters = [])
    {
        $query = Report::query();

        // Eager load relations to avoid N+1
        $query->with(['user', 'category', 'images']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $id, array $relations = [])
    {
        // Default relations
        if (empty($relations)) {
            $relations = ['user', 'category', 'images'];
        }

        return Report::with($relations)->find($id);
    }

    public function create(array $data): Report
    {
        return Report::create($data);
    }

    public function update(int $id, array $data): Report
    {
        $report = Report::findOrFail($id);
        $report->update($data);
        return $report;
    }

    public function delete(int $id): bool
    {
        $report = Report::findOrFail($id);
        return $report->delete();
    }

    public function findByUser(int $userId, int $perPage = 15)
    {
        return Report::where('user_id', $userId)
            ->with(['user', 'category', 'images'])
            ->paginate($perPage);
    }
}
