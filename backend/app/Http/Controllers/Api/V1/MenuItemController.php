<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItem\IndexMenuItemRequest;
use App\Http\Requests\MenuItem\StoreMenuItemRequest;
use App\Http\Requests\MenuItem\UpdateAvailabilityRequest;
use App\Http\Requests\MenuItem\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Services\MenuItemService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MenuItemController extends Controller
{
    public function __construct(private readonly MenuItemService $items) {}

    public function index(IndexMenuItemRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', MenuItem::class);

        return ApiResponse::paginated($this->items->paginate($request->filters()), MenuItemResource::class, 'Menu items retrieved successfully');
    }

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        Gate::authorize('create', MenuItem::class);

        $item = $this->items->create($request->attributesForModel(), $request->file('image'));

        return ApiResponse::success(new MenuItemResource($item), 'Menu item created successfully', 201);
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        Gate::authorize('view', $menuItem);

        return ApiResponse::success(new MenuItemResource($menuItem->load('category')), 'Menu item retrieved successfully');
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        Gate::authorize('update', $menuItem);

        $item = $this->items->update(
            $menuItem,
            $request->attributesForModel(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return ApiResponse::success(new MenuItemResource($item), 'Menu item updated successfully');
    }

    public function updateAvailability(UpdateAvailabilityRequest $request, MenuItem $menuItem): JsonResponse
    {
        Gate::authorize('update', $menuItem);

        $item = $this->items->setAvailability($menuItem, $request->boolean('is_available'));

        return ApiResponse::success(new MenuItemResource($item), 'Availability updated successfully');
    }

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        Gate::authorize('delete', $menuItem);

        $this->items->delete($menuItem);

        return ApiResponse::success(null, 'Menu item deleted successfully');
    }
}
