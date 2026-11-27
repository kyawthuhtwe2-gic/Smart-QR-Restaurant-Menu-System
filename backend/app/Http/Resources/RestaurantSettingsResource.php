<?php

namespace App\Http\Resources;

use App\Models\Restaurant;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Restaurant */
class RestaurantSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo_url' => app(ImageService::class)->url($this->logo),
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'currency' => $this->currency,
            'is_active' => $this->is_active,
            'updated_at' => $this->updated_at,
        ];
    }
}
