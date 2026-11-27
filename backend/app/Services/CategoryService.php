<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Category::query()->withCount('menuItems');

        if (filled($filters['search'] ?? null)) {
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where('name', 'like', $term);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        $query->orderBy($filters['sort_by'] ?? 'sort_order', $filters['sort_dir'] ?? 'asc')->orderBy('id');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        $data['is_active'] ??= true;
        $data['sort_order'] ??= ((int) Category::query()->max('sort_order')) + 1;

        return Category::create($data)->loadCount('menuItems');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->loadCount('menuItems');
    }

    public function hasMenuItems(Category $category): bool
    {
        return $category->menuItems()->exists();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    /**
     * Persist a new ordering: position in the list becomes sort_order.
     *
     * @param  list<int>  $ids
     */
    public function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                Category::query()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });
    }
}
