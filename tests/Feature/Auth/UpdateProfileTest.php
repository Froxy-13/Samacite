<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'email' => 'jean@example.com',
            'telephone' => '+33612345678',
        ]);
    }

    /**
     * Test user can update their own profile with all fields
     */
    public function test_user_can_update_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'nom' => 'Jean',
                'prenom' => 'Martin',
                'telephone' => '+33698765432',
                'email' => 'jean.martin@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Profil mis à jour avec succès');
        $response->assertJsonPath('data.nom', 'Jean');
        $response->assertJsonPath('data.prenom', 'Martin');
        $response->assertJsonPath('data.telephone', '+33698765432');
        $response->assertJsonPath('data.email', 'jean.martin@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'nom' => 'Jean',
            'prenom' => 'Martin',
            'telephone' => '+33698765432',
            'email' => 'jean.martin@example.com',
        ]);
    }

    /**
     * Test user can update only nom
     */
    public function test_user_can_update_only_nom(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'nom' => 'Pierre',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.nom', 'Pierre');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'nom' => 'Pierre',
        ]);
    }

    /**
     * Test user can update only prenom
     */
    public function test_user_can_update_only_prenom(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'prenom' => 'Claude',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.prenom', 'Claude');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'prenom' => 'Claude',
        ]);
    }

    /**
     * Test user can update only telephone
     */
    public function test_user_can_update_only_telephone(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'telephone' => '+33712345678',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.telephone', '+33712345678');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'telephone' => '+33712345678',
        ]);
    }

    /**
     * Test user can update email
     */
    public function test_user_can_update_email(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'email' => 'newemail@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', 'newemail@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'newemail@example.com',
        ]);
    }

    /**
     * Test user can update password
     */
    public function test_user_can_update_password(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(200);

        // Verify password was actually hashed and updated
        $this->assertTrue(
            password_verify('NewPassword123!', $this->user->fresh()->password)
        );
    }

    /**
     * Test password requires confirmation
     */
    public function test_password_requires_confirmation(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test invalid email format
     */
    public function test_invalid_email_format(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test duplicate email validation
     */
    public function test_duplicate_email_validation(): void
    {
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'email' => 'other@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test invalid telephone format
     */
    public function test_invalid_telephone_format(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'telephone' => 'not-a-phone',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test nom max length validation
     */
    public function test_nom_max_length_validation(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'nom' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * Test unauthenticated user cannot update profile
     */
    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->patchJson('/api/profile', [
            'nom' => 'Hacker',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user cannot update other user's profile through this endpoint
     */
    public function test_user_can_only_update_own_profile(): void
    {
        $otherUser = User::factory()->create([
            'nom' => 'Other',
            'prenom' => 'User',
            'email' => 'other@example.com',
            'telephone' => '+33612345679',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'nom' => 'Hacked',
            ]);

        $response->assertStatus(200);

        // Verify only authenticated user's profile was updated
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'nom' => 'Hacked',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'nom' => 'Other',
        ]);
    }

    /**
     * Test successful profile update returns user data
     */
    public function test_profile_update_returns_all_user_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'nom' => 'Updated',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'nom',
                'prenom',
                'email',
                'telephone',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    /**
     * Test update all fields at once
     */
    public function test_user_can_update_all_fields_at_once(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson('/api/profile', [
                'nom' => 'NewNom',
                'prenom' => 'NewPrenom',
                'telephone' => '+33612345999',
                'email' => 'new.email@example.com',
                'password' => 'ComplexPassword123!',
                'password_confirmation' => 'ComplexPassword123!',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.nom', 'NewNom');
        $response->assertJsonPath('data.prenom', 'NewPrenom');
        $response->assertJsonPath('data.telephone', '+33612345999');
        $response->assertJsonPath('data.email', 'new.email@example.com');

        $this->assertTrue(
            password_verify('ComplexPassword123!', $this->user->fresh()->password)
        );
    }
}
