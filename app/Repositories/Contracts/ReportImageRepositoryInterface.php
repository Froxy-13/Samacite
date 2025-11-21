<?php

namespace App\Repositories\Contracts;

interface ReportImageRepositoryInterface
{
    /**
     * Get all images for a report
     */
    public function getByReportId(int $reportId): object;

    /**
     * Find image by ID
     */
    public function findWithRelations(int $id, array $relations = []): ?object;

    /**
     * Create a new image
     */
    public function create(array $data): object;

    /**
     * Delete an image
     */
    public function delete(int $id): bool;

    /**
     * Delete all images for a report
     */
    public function deleteByReportId(int $reportId): bool;
}
