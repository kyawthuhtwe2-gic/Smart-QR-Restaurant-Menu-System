<?php

namespace App\Http\Resources;

use App\Models\MenuItem;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MenuItem */
class MenuItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => app(ImageService::class)->url($this->image_path),
            'is_available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
