<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TableService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = RestaurantTable::query();

        if (filled($filters['search'] ?? null)) {
            $query->where('table_number', 'like', '%'.addcslashes($filters['search'], '%_\\').'%');
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('table_number')->orderBy('id')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RestaurantTable
    {
        $data['is_active'] ??= true;

        return RestaurantTable::create($data);
    }

    /**
     * Updating a table never touches its QR token: the QR URL stays stable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        $table->update($data);

        return $table;
    }

    public function setActive(RestaurantTable $table, bool $active): RestaurantTable
    {
        $table->update(['is_active' => $active]);

        return $table;
    }

    /**
     * Issue a new token inside a row lock so concurrent calls cannot interleave.
     */
    public function regenerateQr(RestaurantTable $table): RestaurantTable
    {
        return DB::transaction(function () use ($table) {
            $locked = RestaurantTable::query()->whereKey($table->getKey())->lockForUpdate()->firstOrFail();

            return $locked->regenerateToken();
        });
    }

    public function delete(RestaurantTable $table): void
    {
        $table->delete();
    }

    /**
     * Resolve a scanned token to a table the public may use.
     * Returns null for malformed, unknown (including regenerated) or inactive tokens,
     * or when the restaurant itself is inactive or missing.
     */
    public function resolvePublicTable(string $token): ?RestaurantTable
    {
        if (! RestaurantTable::isWellFormedToken($token)) {
            return null;
        }

        $table = RestaurantTable::query()->where('qr_token', $token)->where('is_active', true)->first();

        if (! $table || ! Restaurant::current()?->is_active) {
            return null;
        }

        return $table;
    }
}
