<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class LoginTest extends TestCase
{
    use WithRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();
    }

    /**
     * Test login with email
     */
    public function test_user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'jean@example.com',
            'password' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jean@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'nom',
                        'prenom',
                        'email',
                        'telephone',
                        'roles',
                    ],
                    'token',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertIsArray($response->json('data.user.roles'));
    }

    /**
     * Test login with telephone
     */
    public function test_user_can_login_with_telephone()
    {
        $user = User::factory()->create([
            'telephone' => '+221701234567',
            'password' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'telephone' => '+221701234567',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'jean@example.com',
            'password' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jean@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Identifiants invalides',
            ]);
    }

    /**
     * Test login with non-existent user
     */
    public function test_login_fails_with_non_existent_user()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Identifiants invalides',
            ]);
    }

    /**
     * Test token is Bearer token
     */
    public function test_token_is_valid_bearer_token()
    {
        $user = User::factory()->create([
            'email' => 'jean@example.com',
            'password' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jean@example.com',
            'password' => 'Password123',
        ]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertTrue(strlen($token) > 20);
    }
}
