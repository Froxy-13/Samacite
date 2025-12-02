<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class ProfileTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRolesAndPermissions();
        
        $this->user = User::factory()->create([
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => 'test@example.com',
            'telephone' => '776123456',
            'password' => Hash::make('password123'),
        ]);
        
        $this->user->assignRole('Utilisateur');
    }

    /** @test */
    public function test_user_can_get_their_profile()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/user/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'nom',
                    'prenom',
                    'email',
                    'telephone',
                    'photo_url',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'nom' => 'Test',
                    'prenom' => 'User',
                    'email' => 'test@example.com',
                    'telephone' => '776123456',
                ]
            ]);
    }

    /** @test */
    public function test_user_can_update_their_profile()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/user/profile', [
                'nom' => 'Updated',
                'prenom' => 'Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nom' => 'Updated',
                    'prenom' => 'Name',
                    'email' => 'updated@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'nom' => 'Updated',
            'prenom' => 'Name',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function test_user_cannot_update_with_existing_email()
    {
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
        ]);
        $otherUser->assignRole('Utilisateur');

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/user/profile', [
                'email' => 'other@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function test_user_can_upload_profile_photo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('profile.jpg', 500, 500);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['photo_url']
            ])
            ->assertJson([
                'success' => true,
            ]);

        // Vérifier que le fichier a été stocké
        $this->user->refresh();
        $this->assertNotNull($this->user->photo_url);
        Storage::disk('public')->assertExists($this->user->photo_url);
    }

    /** @test */
    public function test_user_can_change_password()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/user/password', [
                'current_password' => 'password123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Vérifier que le mot de passe a été changé
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /** @test */
    public function test_user_cannot_change_password_with_wrong_current_password()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/user/password', [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    /** @test */
    public function test_user_cannot_change_password_without_confirmation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/user/password', [
                'current_password' => 'password123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'differentpassword',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('new_password');
    }

    /** @test */
    public function test_photo_upload_validates_file_type()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    /** @test */
    public function test_photo_upload_validates_file_size()
    {
        Storage::fake('public');

        // Créer un fichier de plus de 2MB
        $file = UploadedFile::fake()->image('profile.jpg')->size(3000);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    /** @test */
    public function test_old_photo_is_deleted_when_new_photo_uploaded()
    {
        Storage::fake('public');

        // Upload première photo
        $file1 = UploadedFile::fake()->image('photo1.jpg');
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/profile/photo', ['photo' => $file1]);

        $this->user->refresh();
        $oldPhotoPath = $this->user->photo_url;
        Storage::disk('public')->assertExists($oldPhotoPath);

        // Upload deuxième photo
        $file2 = UploadedFile::fake()->image('photo2.jpg');
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user/profile/photo', ['photo' => $file2]);

        $this->user->refresh();
        $newPhotoPath = $this->user->photo_url;

        // Vérifier que l'ancienne photo a été supprimée
        Storage::disk('public')->assertMissing($oldPhotoPath);
        Storage::disk('public')->assertExists($newPhotoPath);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_profile_endpoints()
    {
        $this->getJson('/api/user/profile')->assertStatus(401);
        $this->putJson('/api/user/profile')->assertStatus(401);
        $this->postJson('/api/user/profile/photo')->assertStatus(401);
        $this->putJson('/api/user/password')->assertStatus(401);
    }
}
