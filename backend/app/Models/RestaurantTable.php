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

    public static function isWellFormedToken(string $token): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{'.self::TOKEN_LENGTH.'}$/', $token);
    }

    /**
     * Replace the token. The previous token stops resolving immediately.
     */
    public function regenerateToken(): static
    {
        $this->forceFill(['qr_token' => static::generateUniqueToken()])->save();

        return $this;
    }

    /**
     * Public URL encoded in the QR code. Stable until the token is regenerated.
     */
    public function menuUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/menu/'.$this->qr_token;
    }
}
