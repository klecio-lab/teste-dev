<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ash Ketchum',
            'email' => 'ash@pallet.town',
            'password' => 'pikachu123',
            'password_confirmation' => 'pikachu123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'ash@pallet.town']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create(['email' => 'ash@pallet.town', 'password' => 'pikachu123']);

        $this->postJson('/api/auth/login', [
            'email' => 'ash@pallet.town',
            'password' => 'pikachu123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'ash@pallet.town', 'password' => 'pikachu123']);

        $this->postJson('/api/auth/login', [
            'email' => 'ash@pallet.town',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }
}
