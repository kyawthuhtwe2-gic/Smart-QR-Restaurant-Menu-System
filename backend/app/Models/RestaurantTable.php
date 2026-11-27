<?php

namespace App\Models;

use Database\Factories\RestaurantTableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * `qr_token` is deliberately not fillable: it is generated server-side only
 * (on create, and by explicit regeneration).
 */
#[Fillable(['table_number', 'is_active'])]
class RestaurantTable extends Model
{
    /** @use HasFactory<RestaurantTableFactory> */
    use HasFactory;

    public const TOKEN_LENGTH = 48;

    protected static function booted(): void
    {
        static::creating(function (RestaurantTable $table): void {
            if (empty($table->qr_token)) {
                $table->qr_token = static::generateUniqueToken();
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

    /**
     * Cryptographically secure random token (Str::random uses random_bytes).
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(self::TOKEN_LENGTH);
        } while (static::query()->where('qr_token', $token)->exists());

        return $token;
    }
}
