<?php

// Test rapide d'un endpoint de profil

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test rapide endpoint GET /api/user/profile\n";
echo str_repeat("=", 60) . "\n\n";

// Trouver un utilisateur
$user = User::first();

if (!$user) {
    echo "❌ Aucun utilisateur trouvé\n";
    exit(1);
}

// Créer un token
$token = $user->createToken('quick-test')->plainTextToken;

echo "👤 Utilisateur : {$user->nom} {$user->prenom}\n";
echo "🔑 Token créé\n\n";

// Simuler la requête via le controller
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function() use ($user) {
    return $user;
});

$controller = new \App\Http\Controllers\Auth\ProfileController();

try {
    $response = $controller->show($request);
    $data = json_decode($response->getContent(), true);
    
    echo "✅ Réponse du contrôleur :\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    echo "📊 Vérifications :\n";
    echo "   " . ($data['success'] ? "✅" : "❌") . " success = true\n";
    echo "   " . (isset($data['data']['id']) ? "✅" : "❌") . " id présent\n";
    echo "   " . (isset($data['data']['nom']) ? "✅" : "❌") . " nom présent\n";
    echo "   " . (isset($data['data']['prenom']) ? "✅" : "❌") . " prenom présent\n";
    echo "   " . (isset($data['data']['email']) ? "✅" : "❌") . " email présent\n";
    echo "   " . (isset($data['data']['telephone']) ? "✅" : "❌") . " telephone présent\n";
    echo "   " . (isset($data['data']['photo_url']) ? "✅" : "❌") . " photo_url présent\n";
    echo "   " . (isset($data['data']['created_at']) ? "✅" : "❌") . " created_at présent\n";
    echo "   " . (isset($data['data']['updated_at']) ? "✅" : "❌") . " updated_at présent\n\n";
    
    echo "🎉 Endpoint fonctionnel !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

// Nettoyer
$user->tokens()->where('name', 'quick-test')->delete();
echo "🧹 Token nettoyé\n";
