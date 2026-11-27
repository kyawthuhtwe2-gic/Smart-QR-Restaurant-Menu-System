<?php

namespace App\Services;

use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class RestaurantSettingsService
{
    public const LOGO_DIR = 'restaurant';

    public function __construct(private readonly ImageService $images) {}

    public function current(): ?Restaurant
    {
        return Restaurant::current();
    }

    /**
     * Update (or, on a fresh install, create) the single restaurant record.
     * The old logo file is removed only after the database change commits.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, ?UploadedFile $logo = null, bool $removeLogo = false): Restaurant
    {
        $restaurant = Restaurant::current();
        $oldPath = $restaurant?->logo;
        $newPath = $logo ? $this->images->store($logo, self::LOGO_DIR) : null;

        try {
            $restaurant = DB::transaction(function () use ($restaurant, $data, $newPath, $removeLogo) {
                if ($newPath) {
                    $data['logo'] = $newPath;
                } elseif ($removeLogo) {
                    $data['logo'] = null;
                }

                if ($restaurant) {
                    $restaurant->update($data);

                    return $restaurant;
                }

                return Restaurant::create($data);
            });
        } catch (Throwable $e) {
            $this->images->delete($newPath, self::LOGO_DIR);

            throw $e;
        }

        if ($oldPath && $oldPath !== $restaurant->logo) {
            $this->images->delete($oldPath, self::LOGO_DIR);
        }

        return $restaurant;
    }
}
