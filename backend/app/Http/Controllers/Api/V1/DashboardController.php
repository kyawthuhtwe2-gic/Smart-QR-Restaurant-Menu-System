<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MenuItemResource;
use App\Models\Restaurant;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        return ApiResponse::success([
            'stats' => $this->dashboard->stats(),
            'recent_menu_updates' => MenuItemResource::collection($this->dashboard->recentMenuUpdates()),
            'restaurant' => [
                'name' => Restaurant::current()?->name,
                'currency' => Restaurant::current()?->currency,
            ],
        ], 'Dashboard retrieved successfully');
    }
}
