<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->validated('email'), $request->validated('password'));

        if (! $result) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(['user' => new UserResource($request->user())], 'Authenticated user retrieved');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->sendResetLink($request->validated('email'));

        return ApiResponse::success(null, 'If that email exists, a password reset link has been sent');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $ok = $this->auth->resetPassword($request->only('email', 'password', 'password_confirmation', 'token'));

        if (! $ok) {
            return ApiResponse::error('Invalid or expired password reset token', 422);
        }

        return ApiResponse::success(null, 'Password has been reset successfully');
    }
}
