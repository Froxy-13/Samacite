<?php

namespace Tests\Feature\AI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithRolesAndPermissions;

class AnalyzeImageTest extends TestCase
{
    use RefreshDatabase, WithRolesAndPermissions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRolesAndPermissions();

        $this->user = User::factory()->create();
        $this->user->assignRole('Utilisateur');

        Storage::fake('public');
    }

    public function test_user_can_analyze_image_with_gemini()
    {
        // Skip si pas de clé API configurée
        if (!config('services.gemini.api_key')) {
            $this->markTestSkipped('Gemini API key not configured');
        }

        // Mock Gemini API response
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Nid de poule dangereux',
                                        'description' => 'Important trou dans la chaussée présentant un danger pour les véhicules',
                                        'category_name' => 'nid_de_poule',
                                        'confidence' => 0.92
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $image = UploadedFile::fake()->image('report.jpg', 800, 600);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', [
                'image' => $image,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'title',
                    'description',
                    'category_id',
                    'confidence',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Nid de poule dangereux',
                    'category_id' => 1, // nid_de_poule maps to ID 1
                ]
            ]);
    }

    public function test_analyze_image_requires_authentication()
    {
        $image = UploadedFile::fake()->image('report.jpg');

        $response = $this->postJson('/api/ai/analyze-image', [
            'image' => $image,
        ]);

        $response->assertStatus(401);
    }

    public function test_analyze_image_validates_file_type()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', [
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_analyze_image_validates_file_size()
    {
        // Créer une image de plus de 10MB
        $image = UploadedFile::fake()->image('large.jpg')->size(11000);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', [
                'image' => $image,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_analyze_image_requires_image_field()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_analyze_image_maps_categories_correctly()
    {
        // Skip si pas de clé API configurée
        if (!config('services.gemini.api_key')) {
            $this->markTestSkipped('Gemini API key not configured');
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Ordures non ramassées',
                                        'description' => 'Accumulation de déchets depuis plusieurs jours',
                                        'category_name' => 'ordures',
                                        'confidence' => 0.88
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $image = UploadedFile::fake()->image('report.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', [
                'image' => $image,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'category_id' => 2, // ordures maps to ID 2
                ]
            ]);
    }

    public function test_analyze_image_handles_unknown_category()
    {
        // Skip si pas de clé API configurée
        if (!config('services.gemini.api_key')) {
            $this->markTestSkipped('Gemini API key not configured');
        }

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Problème inconnu',
                                        'description' => 'Un problème qui ne rentre dans aucune catégorie',
                                        'category_name' => 'category_inconnue',
                                        'confidence' => 0.50
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $image = UploadedFile::fake()->image('report.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', [
                'image' => $image,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'category_id' => 6, // Default to 'autre' (ID 6)
                ]
            ]);
    }

    public function test_analyze_image_handles_api_errors()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([], 500)
        ]);

        $image = UploadedFile::fake()->image('report.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', [
                'image' => $image,
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_analyze_image_handles_invalid_json_response()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Invalid JSON response without proper structure'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $image = UploadedFile::fake()->image('report.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/analyze-image', [
                'image' => $image,
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ]);
    }
}
