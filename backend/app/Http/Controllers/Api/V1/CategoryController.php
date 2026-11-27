<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\IndexCategoryRequest;
use App\Http\Requests\Category\ReorderCategoriesRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories) {}

    public function index(IndexCategoryRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Category::class);

        return ApiResponse::paginated($this->categories->paginate($request->filters()), CategoryResource::class, 'Categories retrieved successfully');
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);

        $category = $this->categories->create($request->validated());

        return ApiResponse::success(new CategoryResource($category), 'Category created successfully', 201);
    }

    public function show(Category $category): JsonResponse
    {
        Gate::authorize('view', $category);

        return ApiResponse::success(new CategoryResource($category->loadCount('menuItems')), 'Category retrieved successfully');
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        Gate::authorize('update', $category);

        return ApiResponse::success(new CategoryResource($this->categories->update($category, $request->validated())), 'Category updated successfully');
    }

    public function destroy(Category $category): JsonResponse
    {
        Gate::authorize('delete', $category);

        if ($this->categories->hasMenuItems($category)) {
            return ApiResponse::error('Category contains menu items and cannot be deleted', 422, [
                'category' => ['Move or delete its menu items first.'],
            ]);
        }

        $this->categories->delete($category);

        return ApiResponse::success(null, 'Category deleted successfully');
    }

    public function reorder(ReorderCategoriesRequest $request): JsonResponse
    {
        Gate::authorize('update', new Category);

        $this->categories->reorder($request->validated('ids'));

        return ApiResponse::success(null, 'Categories reordered successfully');
    }
}
