<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $password = 'secret-pass-1'): User
    {
        return User::factory()->create(['password' => $password]);
    }

    public function test_login_success_returns_token_and_user(): void
    {
        $user = $this->admin();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass-1'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token', 'token_type']])
            ->assertJsonMissingPath('data.user.password');
    }

    public function test_login_failure_with_wrong_password(): void
    {
        $user = $this->admin();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_login_failure_with_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'x'])
            ->assertStatus(401);
    }

    public function test_login_validation_error(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email', 'password'], 'errors');
    }

    public function test_non_admin_role_cannot_login(): void
    {
        $user = $this->admin();
        $user->forceFill(['role' => 'guest'])->save();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass-1'])
            ->assertStatus(401);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_returns_current_user_with_bearer_token(): void
    {
        $user = $this->admin();
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass-1'])
            ->json('data.token');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->admin();
        $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass-1'])
            ->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_forgot_password_does_not_reveal_whether_email_exists(): void
    {
        Notification::fake();
        $user = $this->admin();

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_with_valid_token_revokes_tokens(): void
    {
        $user = $this->admin();
        $user->createToken('old');
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-pass-9',
            'password_confirmation' => 'brand-new-pass-9',
        ])->assertOk();

        $this->assertTrue(Hash::check('brand-new-pass-9', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_password_with_invalid_token_fails(): void
    {
        $user = $this->admin();

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'bogus',
            'email' => $user->email,
            'password' => 'brand-new-pass-9',
            'password_confirmation' => 'brand-new-pass-9',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
