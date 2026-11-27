<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class MenuItemService
{
    public const IMAGE_DIR = 'menu-items';

    public function __construct(private readonly ImageService $images) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = MenuItem::query()->with('category');

        if (filled($filters['search'] ?? null)) {
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where('name', 'like', $term);
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', $filters['category_id']);
        }

        foreach (['is_available', 'is_featured'] as $flag) {
            if (array_key_exists($flag, $filters) && $filters[$flag] !== null) {
                $query->where($flag, $filters[$flag]);
            }
        }

        if (filled($filters['min_price'] ?? null)) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (filled($filters['max_price'] ?? null)) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $query->orderBy($filters['sort_by'] ?? 'sort_order', $filters['sort_dir'] ?? 'asc')->orderBy('id');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): MenuItem
    {
        $path = $image ? $this->images->store($image, self::IMAGE_DIR) : null;

        try {
            $item = DB::transaction(function () use ($data, $path) {
                $data['image_path'] = $path;
                $data['sort_order'] ??= ((int) MenuItem::query()->where('category_id', $data['category_id'])->max('sort_order')) + 1;

                return MenuItem::create($data);
            });
        } catch (Throwable $e) {
            $this->images->delete($path, self::IMAGE_DIR);

            throw $e;
        }

        return $item->load('category');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MenuItem $item, array $data, ?UploadedFile $image = null, bool $removeImage = false): MenuItem
    {
        $oldPath = $item->image_path;
        $newPath = $image ? $this->images->store($image, self::IMAGE_DIR) : null;

        try {
            DB::transaction(function () use ($item, $data, $newPath, $removeImage) {
                if ($newPath) {
                    $data['image_path'] = $newPath;
                } elseif ($removeImage) {
                    $data['image_path'] = null;
                }

                $item->update($data);
            });
        } catch (Throwable $e) {
            $this->images->delete($newPath, self::IMAGE_DIR);

            throw $e;
        }

        // Old file is removed only after the database change has committed.
        if ($oldPath && $oldPath !== $item->image_path) {
            $this->images->delete($oldPath, self::IMAGE_DIR);
        }

        return $item->load('category');
    }

    public function setAvailability(MenuItem $item, bool $available): MenuItem
    {
        $item->update(['is_available' => $available]);

        return $item->load('category');
    }

    public function delete(MenuItem $item): void
    {
        $oldPath = $item->image_path;

        DB::transaction(function () use ($item) {
            $item->update(['image_path' => null]);
            $item->delete();
        });

        $this->images->delete($oldPath, self::IMAGE_DIR);
    }
}
