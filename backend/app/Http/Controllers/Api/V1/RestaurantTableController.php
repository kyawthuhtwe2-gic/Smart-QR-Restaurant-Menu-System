<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Table\IndexTableRequest;
use App\Http\Requests\Table\StoreTableRequest;
use App\Http\Requests\Table\UpdateTableRequest;
use App\Http\Requests\Table\UpdateTableStatusRequest;
use App\Http\Resources\RestaurantTableResource;
use App\Models\RestaurantTable;
use App\Services\TableService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RestaurantTableController extends Controller
{
    public function __construct(private readonly TableService $tables) {}

    public function index(IndexTableRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', RestaurantTable::class);

        return ApiResponse::paginated($this->tables->paginate($request->filters()), RestaurantTableResource::class, 'Tables retrieved successfully');
    }

    public function store(StoreTableRequest $request): JsonResponse
    {
        Gate::authorize('create', RestaurantTable::class);

        $table = $this->tables->create($request->validated());

        return ApiResponse::success(new RestaurantTableResource($table), 'Table created successfully', 201);
    }

    public function show(RestaurantTable $table): JsonResponse
    {
        Gate::authorize('view', $table);

        return ApiResponse::success(new RestaurantTableResource($table), 'Table retrieved successfully');
    }

    public function update(UpdateTableRequest $request, RestaurantTable $table): JsonResponse
    {
        Gate::authorize('update', $table);

        return ApiResponse::success(new RestaurantTableResource($this->tables->update($table, $request->validated())), 'Table updated successfully');
    }

    public function updateStatus(UpdateTableStatusRequest $request, RestaurantTable $table): JsonResponse
    {
        Gate::authorize('update', $table);

        $table = $this->tables->setActive($table, $request->boolean('is_active'));

        return ApiResponse::success(new RestaurantTableResource($table), $table->is_active ? 'Table activated' : 'Table deactivated');
    }

    public function destroy(RestaurantTable $table): JsonResponse
    {
        Gate::authorize('delete', $table);

        $this->tables->delete($table);

        return ApiResponse::success(null, 'Table deleted successfully');
    }
}
