<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function paginateWithFilters(int $perPage = 15, array $filters = []): object
    {
        $query = Category::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        return $query->paginate($perPage);
    }

    public function findWithRelations(int $id, array $relations = []): ?object
    {
        $query = Category::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    public function create(array $data): object
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): object
    {
        $category = Category::findOrFail($id);
        $category->update($data);

        return $category;
    }

    public function delete(int $id): bool
    {
        $category = Category::findOrFail($id);

        return $category->delete();
    }

    public function findByName(string $name): ?object
    {
        return Category::where('name', $name)->first();
    }
}
