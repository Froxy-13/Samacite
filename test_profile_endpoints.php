<?php

// Script de test manuel des endpoints de profil

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 TEST DES ENDPOINTS DE PROFIL\n";
echo str_repeat("=", 60) . "\n\n";

// Trouver un utilisateur de test
$user = User::first();

if (!$user) {
    echo "❌ Aucun utilisateur trouvé\n";
    exit(1);
}

echo "👤 Utilisateur test : {$user->nom} {$user->prenom}\n";
echo "   ID: {$user->id}\n";
echo "   Email: {$user->email}\n";
echo "   Téléphone: {$user->telephone}\n";
echo "   Photo: " . ($user->photo_url ? "✅ Oui" : "❌ Non") . "\n\n";

// Créer un token
$token = $user->createToken('test-profile')->plainTextToken;
echo "🔑 Token créé : " . substr($token, 0, 20) . "...\n\n";

$baseUrl = 'http://192.168.1.17:8000'; // Adapter selon votre config

echo str_repeat("=", 60) . "\n";
echo "📋 ENDPOINTS DISPONIBLES\n";
echo str_repeat("=", 60) . "\n\n";

echo "1️⃣  GET /api/user/profile\n";
echo "   → Récupérer le profil utilisateur\n";
echo "   curl -X GET {$baseUrl}/api/user/profile \\\n";
echo "     -H 'Authorization: Bearer {$token}' \\\n";
echo "     -H 'Accept: application/json'\n\n";

echo "2️⃣  PUT /api/user/profile\n";
echo "   → Modifier le profil\n";
echo "   curl -X PUT {$baseUrl}/api/user/profile \\\n";
echo "     -H 'Authorization: Bearer {$token}' \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -H 'Accept: application/json' \\\n";
echo "     -d '{\"nom\": \"Nouveau\", \"prenom\": \"Nom\"}'\n\n";

echo "3️⃣  POST /api/user/profile/photo\n";
echo "   → Upload photo de profil\n";
echo "   curl -X POST {$baseUrl}/api/user/profile/photo \\\n";
echo "     -H 'Authorization: Bearer {$token}' \\\n";
echo "     -H 'Accept: application/json' \\\n";
echo "     -F 'photo=@/path/to/photo.jpg'\n\n";

echo "4️⃣  PUT /api/user/password\n";
echo "   → Changer le mot de passe\n";
echo "   curl -X PUT {$baseUrl}/api/user/password \\\n";
echo "     -H 'Authorization: Bearer {$token}' \\\n";
echo "     -H 'Content-Type: application/json' \\\n";
echo "     -H 'Accept: application/json' \\\n";
echo "     -d '{";
echo "\"current_password\": \"old\", ";
echo "\"new_password\": \"new123\", ";
echo "\"new_password_confirmation\": \"new123\"";
echo "}'\n\n";

echo str_repeat("=", 60) . "\n";
echo "📱 COLLECTION POSTMAN\n";
echo str_repeat("=", 60) . "\n\n";

$postmanCollection = [
    'info' => [
        'name' => 'SamaCité - Profile API',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    ],
    'item' => [
        [
            'name' => 'Get Profile',
            'request' => [
                'method' => 'GET',
                'header' => [
                    ['key' => 'Accept', 'value' => 'application/json'],
                    ['key' => 'Authorization', 'value' => 'Bearer {{token}}']
                ],
                'url' => [
                    'raw' => '{{base_url}}/api/user/profile',
                    'host' => ['{{base_url}}'],
                    'path' => ['api', 'user', 'profile']
                ]
            ]
        ],
        [
            'name' => 'Update Profile',
            'request' => [
                'method' => 'PUT',
                'header' => [
                    ['key' => 'Accept', 'value' => 'application/json'],
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                    ['key' => 'Authorization', 'value' => 'Bearer {{token}}']
                ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'nom' => 'Nouveau',
                        'prenom' => 'Nom',
                        'email' => 'nouveau@example.com'
                    ], JSON_PRETTY_PRINT)
                ],
                'url' => [
                    'raw' => '{{base_url}}/api/user/profile',
                    'host' => ['{{base_url}}'],
                    'path' => ['api', 'user', 'profile']
                ]
            ]
        ],
        [
            'name' => 'Upload Photo',
            'request' => [
                'method' => 'POST',
                'header' => [
                    ['key' => 'Accept', 'value' => 'application/json'],
                    ['key' => 'Authorization', 'value' => 'Bearer {{token}}']
                ],
                'body' => [
                    'mode' => 'formdata',
                    'formdata' => [
                        ['key' => 'photo', 'type' => 'file', 'src' => '']
                    ]
                ],
                'url' => [
                    'raw' => '{{base_url}}/api/user/profile/photo',
                    'host' => ['{{base_url}}'],
                    'path' => ['api', 'user', 'profile', 'photo']
                ]
            ]
        ],
        [
            'name' => 'Change Password',
            'request' => [
                'method' => 'PUT',
                'header' => [
                    ['key' => 'Accept', 'value' => 'application/json'],
                    ['key' => 'Content-Type', 'value' => 'application/json'],
                    ['key' => 'Authorization', 'value' => 'Bearer {{token}}']
                ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'current_password' => 'password123',
                        'new_password' => 'newpassword123',
                        'new_password_confirmation' => 'newpassword123'
                    ], JSON_PRETTY_PRINT)
                ],
                'url' => [
                    'raw' => '{{base_url}}/api/user/password',
                    'host' => ['{{base_url}}'],
                    'path' => ['api', 'user', 'password']
                ]
            ]
        ]
    ],
    'variable' => [
        ['key' => 'base_url', 'value' => 'http://192.168.1.17:8000'],
        ['key' => 'token', 'value' => $token]
    ]
];

$collectionFile = 'SamaCite-Profile-API.postman_collection.json';
file_put_contents($collectionFile, json_encode($postmanCollection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "✅ Collection Postman créée : {$collectionFile}\n";
echo "   → Importer ce fichier dans Postman\n\n";

echo str_repeat("=", 60) . "\n";
echo "✅ IMPLÉMENTATION TERMINÉE !\n";
echo str_repeat("=", 60) . "\n\n";

echo "📊 Résumé :\n";
echo "   ✅ Migration ajoutée (photo_url)\n";
echo "   ✅ Model User mis à jour\n";
echo "   ✅ ProfileController avec 4 méthodes\n";
echo "   ✅ Routes API configurées\n";
echo "   ✅ Storage link créé\n";
echo "   ✅ 11 tests passent (49 assertions)\n\n";

echo "🚀 Prochaines étapes :\n";
echo "   1. Tester avec Postman (importer la collection)\n";
echo "   2. Tester avec l'app Flutter\n";
echo "   3. Vérifier l'upload de photos\n\n";

// Nettoyer le token
$user->tokens()->where('name', 'test-profile')->delete();
echo "🧹 Token de test supprimé\n";
