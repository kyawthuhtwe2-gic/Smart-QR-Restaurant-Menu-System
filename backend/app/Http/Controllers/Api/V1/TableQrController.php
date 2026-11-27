<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantTableResource;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Services\QrCodeService;
use App\Services\TableService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TableQrController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qr,
        private readonly TableService $tables,
    ) {}

    /**
     * QR preview: public URL plus an SVG image.
     */
    public function show(RestaurantTable $table): JsonResponse
    {
        Gate::authorize('view', $table);

        return ApiResponse::success([
            'table_id' => $table->id,
            'table_number' => $table->table_number,
            'is_active' => $table->is_active,
            'menu_url' => $table->menuUrl(),
            'qr_svg' => $this->qr->svg($table->menuUrl()),
        ], 'QR code retrieved successfully');
    }

    /**
     * Data for the printable layout: restaurant name, table number and QR.
     */
    public function printData(RestaurantTable $table): JsonResponse
    {
        Gate::authorize('view', $table);

        return ApiResponse::success([
            'restaurant_name' => Restaurant::current()?->name ?? config('app.name'),
            'table_number' => $table->table_number,
            'menu_url' => $table->menuUrl(),
            'qr_svg' => $this->qr->svg($table->menuUrl(), 400),
        ], 'Print data retrieved successfully');
    }

    public function download(RestaurantTable $table): Response
    {
        Gate::authorize('view', $table);

        $filename = 'qr-'.(Str::slug($table->table_number) ?: 'table-'.$table->id).'.png';

        return response($this->qr->png($table->menuUrl()), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function regenerate(RestaurantTable $table): JsonResponse
    {
        Gate::authorize('regenerateQr', $table);

        $table = $this->tables->regenerateQr($table);

        return ApiResponse::success(new RestaurantTableResource($table), 'QR code regenerated. The previous QR code no longer works.');
    }
}
