<?php

namespace App\Models;

use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * The application supports exactly one restaurant record.
 */
#[Fillable(['name', 'logo', 'description', 'address', 'phone', 'currency', 'is_active'])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Restaurant $restaurant): void {
            if (static::query()->exists()) {
                throw new LogicException('Only one restaurant record is supported.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->first();
    }
}
