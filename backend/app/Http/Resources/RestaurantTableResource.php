<?php

namespace App\Http\Resources;

use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RestaurantTable */
class RestaurantTableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'table_number' => $this->table_number,
            'is_active' => $this->is_active,
            'menu_url' => $this->menuUrl(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
