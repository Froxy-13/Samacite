<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class RegisterTest extends TestCase
{
    use WithRolesAndPermissions;

    /**
     * Test successful user registration with email
     */
    public function test_user_can_register_with_email()
    {
        $email = 'jean_' . time() . '@example.com';
        $telephone = '+221' . random_int(700000000, 799999999);

        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => $email,
            'telephone' => $telephone,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'nom',
                    'prenom',
                    'email',
                    'telephone',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'telephone' => $telephone,
        ]);
    }

    /**
     * Test user registration validation
     */
    public function test_register_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'nom' => '',
            'prenom' => '',
            'email' => 'invalid-email',
            'telephone' => '',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nom', 'prenom', 'email', 'telephone', 'password']);
    }

    /**
     * Test email uniqueness
     */
    public function test_email_must_be_unique()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'existing@example.com',
            'telephone' => '+221701234567',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test telephone uniqueness
     */
    public function test_telephone_must_be_unique()
    {
        User::factory()->create(['telephone' => '+221701234567']);

        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean@example.com',
            'telephone' => '+221701234567',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['telephone']);
    }

    /**
     * Test password confirmation
     */
    public function test_password_must_be_confirmed()
    {
        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean@example.com',
            'telephone' => '+221701234567',
            'password' => 'Password123',
            'password_confirmation' => 'DifferentPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
