<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_json_accept_header(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'new-user@autospec.test',
            'password' => 'secret123',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new-user@autospec.test',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login-user@autospec.test',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login-user@autospec.test',
            'password' => 'secret123',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    public function test_login_returns_unauthorized_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'wrong-user@autospec.test',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong-user@autospec.test',
            'password' => 'wrong-password',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_google_sign_in_creates_or_reuses_user(): void
    {
        $response = $this->postJson('/api/auth/google', [
            'email' => 'google-user@autospec.test',
            'name' => 'Google User',
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'google-user@autospec.test',
        ]);
    }
}
