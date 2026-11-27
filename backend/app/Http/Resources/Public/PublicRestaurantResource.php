<?php

namespace App\Http\Resources\Public;

use App\Models\Restaurant;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Restaurant */
class PublicRestaurantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'logo_url' => app(ImageService::class)->url($this->logo),
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'currency' => $this->currency,
        ];
    }
}
