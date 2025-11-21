<?php

namespace App\Repositories\Contracts;

interface CategoryRepositoryInterface
{
    /**
     * Get all categories with pagination
     */
    public function paginateWithFilters(int $perPage = 15, array $filters = []): object;

    /**
     * Find category by ID
     */
    public function findWithRelations(int $id, array $relations = []): ?object;

    /**
     * Create a new category
     */
    public function create(array $data): object;

    /**
     * Update a category
     */
    public function update(int $id, array $data): object;

    /**
     * Delete a category
     */
    public function delete(int $id): bool;

    /**
     * Find by name
     */
    public function findByName(string $name): ?object;
}
