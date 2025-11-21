<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * Get all categories
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->unauthorized('Non authentifié');
        }

        if (!$user->can('report.view.own') && !$user->can('report.view.any')) {
            return $this->forbidden('Vous n\'avez pas la permission de voir les catégories');
        }

        $filters = $request->only(['search']);
        $categories = $this->categoryRepository->paginateWithFilters(15, $filters);

        return $this->success(
            CategoryResource::collection($categories),
            'Catégories récupérées avec succès'
        );
    }

    /**
     * Get single category
     */
    public function show(int $id)
    {
        $user = auth('sanctum')->user();
        
        if (!$user) {
            return $this->unauthorized('Non authentifié');
        }

        if (!$user->can('report.view.own') && !$user->can('report.view.any')) {
            return $this->forbidden('Vous n\'avez pas la permission de voir les catégories');
        }

        $category = $this->categoryRepository->findWithRelations($id);

        if (!$category) {
            return $this->notFound('Catégorie non trouvée');
        }

        return $this->success(
            new CategoryResource($category),
            'Catégorie récupérée avec succès'
        );
    }

    /**
     * Create a new category (Admin only)
     */
    public function store(Request $request)
    {
        $user = auth('sanctum')->user();
        
        if (!$user) {
            return $this->unauthorized('Non authentifié');
        }

        if (!$user->hasRole('Admin') || !$user->can('dashboard.access')) {
            return $this->forbidden('Seuls les administrateurs peuvent créer des catégories');
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $category = $this->categoryRepository->create($validated);

        return $this->success(
            new CategoryResource($category),
            'Catégorie créée avec succès',
            201
        );
    }

    /**
     * Update a category (Admin only)
     */
    public function update(Request $request, int $id)
    {
        $user = auth('sanctum')->user();
        
        if (!$user) {
            return $this->unauthorized('Non authentifié');
        }

        if (!$user->hasRole('Admin') || !$user->can('dashboard.access')) {
            return $this->forbidden('Seuls les administrateurs peuvent modifier les catégories');
        }

        $category = $this->categoryRepository->findWithRelations($id);

        if (!$category) {
            return $this->notFound('Catégorie non trouvée');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:categories,name,' . $id . '|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $category = $this->categoryRepository->update($id, $validated);

        return $this->success(
            new CategoryResource($category),
            'Catégorie mise à jour avec succès'
        );
    }

    /**
     * Delete a category (Admin only)
     */
    public function destroy(int $id)
    {
        $user = auth('sanctum')->user();
        
        if (!$user) {
            return $this->unauthorized('Non authentifié');
        }

        if (!$user->hasRole('Admin') || !$user->can('dashboard.access')) {
            return $this->forbidden('Seuls les administrateurs peuvent supprimer les catégories');
        }

        $category = $this->categoryRepository->findWithRelations($id);

        if (!$category) {
            return $this->notFound('Catégorie non trouvée');
        }

        $this->categoryRepository->delete($id);

        return $this->success(null, 'Catégorie supprimée avec succès');
    }
}
