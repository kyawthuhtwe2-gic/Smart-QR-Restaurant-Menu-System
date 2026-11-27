<?php

namespace App\Services;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * @return array{total_categories: int, total_menu_items: int, available_items: int, unavailable_items: int, total_tables: int, active_qr_codes: int}
     */
    public function stats(): array
    {
        $available = MenuItem::query()->available()->count();
        $total = MenuItem::query()->count();

        return [
            'total_categories' => Category::query()->count(),
            'total_menu_items' => $total,
            'available_items' => $available,
            'unavailable_items' => $total - $available,
            'total_tables' => RestaurantTable::query()->count(),
            'active_qr_codes' => RestaurantTable::query()->where('is_active', true)->count(),
        ];
    }

    /**
     * @return Collection<int, MenuItem>
     */
    public function recentMenuUpdates(int $limit = 8): Collection
    {
        return MenuItem::query()->with('category')->orderByDesc('updated_at')->orderByDesc('id')->limit($limit)->get();
    }
}
