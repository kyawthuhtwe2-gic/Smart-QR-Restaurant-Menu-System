<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicItemsRequest;
use App\Http\Resources\Public\PublicCategoryResource;
use App\Http\Resources\Public\PublicMenuItemResource;
use App\Http\Resources\Public\PublicRestaurantResource;
use App\Services\PublicMenuService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublicMenuController extends Controller
{
    private const INVALID_QR = 'Invalid or inactive QR code';

    public function __construct(private readonly PublicMenuService $menu) {}

    public function show(string $token): JsonResponse
    {
        $context = $this->menu->context($token);

        if (! $context) {
            return $this->invalid();
        }

        return $this->fresh(ApiResponse::success([
            'restaurant' => new PublicRestaurantResource($context['restaurant']),
            'table' => ['table_number' => $context['table']->table_number],
            'categories' => PublicCategoryResource::collection($this->menu->categories()),
        ], 'Menu retrieved successfully'));
    }

    public function categories(string $token): JsonResponse
    {
        if (! $this->menu->context($token)) {
            return $this->invalid();
        }

        return $this->fresh(ApiResponse::success([
            'categories' => PublicCategoryResource::collection($this->menu->categories()),
        ], 'Categories retrieved successfully'));
    }

    public function items(PublicItemsRequest $request, string $token): JsonResponse
    {
        if (! $this->menu->context($token)) {
            return $this->invalid();
        }

        return $this->fresh(ApiResponse::paginated(
            $this->menu->items($request->filters()),
            PublicMenuItemResource::class,
            'Menu items retrieved successfully',
        ));
    }

    /**
     * One identical response for every kind of failure, so callers cannot tell
     * an unknown token from a deactivated table or restaurant.
     */
    private function invalid(): JsonResponse
    {
        return ApiResponse::error(self::INVALID_QR, 404);
    }

    /**
     * Menus change often; never let a browser or proxy serve a stale price.
     */
    private function fresh(JsonResponse $response): JsonResponse
    {
        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
