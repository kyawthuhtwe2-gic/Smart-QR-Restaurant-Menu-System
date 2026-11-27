<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PublicMenuService
{
    public function __construct(private readonly TableService $tables) {}

    /**
     * Validate a scanned token. Returns null when the QR code must be rejected:
     * malformed, unknown or regenerated token, inactive or deleted table,
     * or a missing or inactive restaurant.
     *
     * @return array{table: RestaurantTable, restaurant: Restaurant}|null
     */
    public function context(string $token): ?array
    {
        $table = $this->tables->resolvePublicTable($token);
        $restaurant = Restaurant::current();

        if (! $table || ! $restaurant || ! $restaurant->is_active) {
            return null;
        }

        return ['table' => $table, 'restaurant' => $restaurant];
    }

    /**
     * Active categories that currently have at least one available item,
     * each with its available items. Always read fresh from the database.
     *
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()
            ->active()
            ->ordered()
            ->whereHas('menuItems', fn (Builder $q) => $q->available())
            ->with(['menuItems' => fn ($q) => $q->available()->ordered()])
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function items(array $filters): LengthAwarePaginator
    {
        $query = MenuItem::query()
            ->available()
            ->whereHas('category', fn (Builder $q) => $q->active());

        if (filled($filters['search'] ?? null)) {
            $term = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('description', 'like', $term));
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', $filters['category_id']);
        }

        if (($filters['is_featured'] ?? null) !== null) {
            $query->where('is_featured', $filters['is_featured']);
        }

        return $query->ordered()->paginate($filters['per_page'] ?? 50);
    }
}
