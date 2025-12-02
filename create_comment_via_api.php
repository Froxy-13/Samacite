<?php

// Script pour créer un commentaire via l'API

use App\Models\User;
use App\Models\Report;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Trouver l'utilisateur avec le téléphone +221761234567
$user = User::where('telephone', '+221761234567')->first();

if (!$user) {
    echo "❌ Utilisateur non trouvé\n";
    exit(1);
}

echo "👤 Utilisateur propriétaire : {$user->nom} {$user->prenom} (ID: {$user->id})\n";

// Trouver un de ses signalements
$report = Report::where('user_id', $user->id)->first();

if (!$report) {
    echo "❌ Aucun signalement trouvé\n";
    exit(1);
}

echo "📋 Signalement : {$report->title} (ID: {$report->id})\n\n";

// Trouver un autre utilisateur pour commenter (pas le propriétaire)
$commenter = User::where('id', '!=', $user->id)->first();

if (!$commenter) {
    echo "❌ Aucun autre utilisateur trouvé\n";
    exit(1);
}

echo "💬 Commentateur : {$commenter->nom} {$commenter->prenom} (ID: {$commenter->id})\n\n";

// Créer un token pour l'utilisateur commenter
$token = $commenter->createToken('test-token')->plainTextToken;

echo "🔑 Token créé\n\n";

// Faire une requête POST à l'API
$baseUrl = 'http://localhost:8000';
$url = "{$baseUrl}/api/reports/{$report->id}/comments";

$data = [
    'content' => 'Commentaire via API - Test notification système',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    "Authorization: Bearer {$token}"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📡 Réponse API (HTTP {$httpCode}):\n";
echo $response . "\n\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Commentaire créé via API\n";
    echo "🔔 Une notification devrait être créée\n";
    echo "💡 Lancez 'php artisan queue:work --max-jobs=5 --stop-when-empty' pour traiter\n";
} else {
    echo "❌ Erreur lors de la création du commentaire\n";
}

// Supprimer le token
$commenter->tokens()->delete();
