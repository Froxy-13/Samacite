<?php

namespace App\Repositories\Eloquent;

use App\Models\ReportImage;
use App\Repositories\Contracts\ReportImageRepositoryInterface;

class EloquentReportImageRepository implements ReportImageRepositoryInterface
{
    public function getByReportId(int $reportId): object
    {
        return ReportImage::where('report_id', $reportId)->get();
    }

    public function findWithRelations(int $id, array $relations = []): ?object
    {
        $query = ReportImage::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    public function create(array $data): object
    {
        return ReportImage::create($data);
    }

    public function delete(int $id): bool
    {
        $image = ReportImage::findOrFail($id);

        return $image->delete();
    }

    public function deleteByReportId(int $reportId): bool
    {
        return ReportImage::where('report_id', $reportId)->delete();
    }
}
