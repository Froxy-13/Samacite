<?php

namespace Tests\Feature\Notifications;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FcmTokenTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_register_fcm_token(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/fcm-token', [
                'token' => 'test-fcm-token-123',
                'device_type' => 'android',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'token',
                    'device_type',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token' => 'test-fcm-token-123',
                    'device_type' => 'android',
                ],
            ]);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => 'test-fcm-token-123',
            'device_type' => 'android',
        ]);
    }

    public function test_fcm_token_is_updated_if_already_exists(): void
    {
        // Create initial token
        FcmToken::create([
            'user_id' => $this->user->id,
            'token' => 'test-fcm-token-123',
            'device_type' => 'android',
        ]);

        // Update with different device_type
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/fcm-token', [
                'token' => 'test-fcm-token-123',
                'device_type' => 'ios',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => 'test-fcm-token-123',
            'device_type' => 'ios',
        ]);

        // Ensure no duplicate tokens
        $this->assertEquals(1, FcmToken::where('token', 'test-fcm-token-123')->count());
    }

    public function test_fcm_token_registration_requires_authentication(): void
    {
        $response = $this->postJson('/api/fcm-token', [
            'token' => 'test-fcm-token-123',
            'device_type' => 'android',
        ]);

        $response->assertStatus(401);
    }

    public function test_fcm_token_registration_requires_token_field(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/fcm-token', [
                'device_type' => 'android',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_fcm_token_registration_requires_device_type_field(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/fcm-token', [
                'token' => 'test-fcm-token-123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_type']);
    }

    public function test_fcm_token_registration_validates_device_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/fcm-token', [
                'token' => 'test-fcm-token-123',
                'device_type' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_type']);
    }

    public function test_user_can_delete_fcm_token(): void
    {
        FcmToken::create([
            'user_id' => $this->user->id,
            'token' => 'test-fcm-token-123',
            'device_type' => 'android',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/fcm-token', [
                'token' => 'test-fcm-token-123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FCM token deleted successfully',
            ]);

        $this->assertDatabaseMissing('fcm_tokens', [
            'user_id' => $this->user->id,
            'token' => 'test-fcm-token-123',
        ]);
    }

    public function test_delete_fcm_token_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/fcm-token', [
            'token' => 'test-fcm-token-123',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_fcm_token_returns_404_if_not_found(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/fcm-token', [
                'token' => 'non-existent-token',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Token not found',
            ]);
    }

    public function test_user_can_have_multiple_tokens_for_different_devices(): void
    {
        // Register Android token
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/fcm-token', [
                'token' => 'android-token',
                'device_type' => 'android',
            ]);

        // Register iOS token
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/fcm-token', [
                'token' => 'ios-token',
                'device_type' => 'ios',
            ]);

        $this->assertEquals(2, FcmToken::where('user_id', $this->user->id)->count());
    }

    public function test_fcm_tokens_are_deleted_when_user_is_deleted(): void
    {
        FcmToken::create([
            'user_id' => $this->user->id,
            'token' => 'test-fcm-token-123',
            'device_type' => 'android',
        ]);

        $userId = $this->user->id;
        $this->user->delete();

        $this->assertEquals(0, FcmToken::where('user_id', $userId)->count());
    }
}
