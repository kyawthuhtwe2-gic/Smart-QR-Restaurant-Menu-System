<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateRestaurantSettingsRequest;
use App\Http\Resources\RestaurantSettingsResource;
use App\Models\Restaurant;
use App\Services\RestaurantSettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RestaurantSettingsController extends Controller
{
    public function __construct(private readonly RestaurantSettingsService $settings) {}

    public function show(): JsonResponse
    {
        Gate::authorize('view', Restaurant::class);

        $restaurant = $this->settings->current();

        return ApiResponse::success([
            'restaurant' => $restaurant ? new RestaurantSettingsResource($restaurant) : null,
        ], 'Restaurant settings retrieved successfully');
    }

    public function update(UpdateRestaurantSettingsRequest $request): JsonResponse
    {
        Gate::authorize('update', Restaurant::class);

        $restaurant = $this->settings->update(
            $request->attributesForModel(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return ApiResponse::success(['restaurant' => new RestaurantSettingsResource($restaurant)], 'Restaurant settings updated successfully');
    }
}
