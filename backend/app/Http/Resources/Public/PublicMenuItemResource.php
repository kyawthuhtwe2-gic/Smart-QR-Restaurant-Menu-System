<?php

namespace App\Http\Resources\Public;

use App\Models\MenuItem;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MenuItem */
class PublicMenuItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'image_url' => app(ImageService::class)->url($this->image_path),
            'is_featured' => $this->is_featured,
        ];
    }
}
