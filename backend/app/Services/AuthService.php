<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Verify credentials and issue a Sanctum bearer token.
     *
     * @return array{user: User, token: string}|null
     */
    public function login(string $email, string $password, string $device = 'admin-spa'): ?array
    {
        $user = User::where('email', $email)->first();

        // Always run a hash check (against a dummy hash when the user is missing) to limit timing differences.
        $hash = $user?->password ?? Hash::make(Str::random(16));
        $valid = Hash::check($password, $hash);

        if (! $user || ! $valid || ! $user->isAdmin()) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken($device)->plainTextToken,
        ];
    }

    public function sendResetLink(string $email): void
    {
        // Result ignored on purpose: the response must not reveal whether the email exists.
        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $credentials): bool
    {
        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
                // Invalidate every existing API token after a password reset.
                $user->tokens()->delete();
            }
        );

        return $status === Password::PASSWORD_RESET;
    }
}
