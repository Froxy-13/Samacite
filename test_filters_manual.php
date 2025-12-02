<?php

/**
 * Script de test manuel pour vérifier les filtres de signalements
 * 
 * Ce script va :
 * 1. Créer des signalements de test avec différents status
 * 2. Tester les filtres via des requêtes HTTP simulées
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "  🧪 TEST MANUEL DES FILTRES\n";
echo "========================================\n\n";

// Nettoyer et préparer la base de données
echo "📦 Préparation de la base de données...\n";
Artisan::call('migrate:fresh --seed');
echo "✅ Base de données prête\n\n";

// Créer un utilisateur de test
$user = User::factory()->create([
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);
$user->assignRole('Utilisateur');

// Récupérer une catégorie
$category = Category::first();

if (!$category) {
    echo "❌ Aucune catégorie trouvée. Assurez-vous que le seeder fonctionne.\n";
    exit(1);
}

echo "👤 Utilisateur créé : {$user->email}\n";
echo "📁 Catégorie : {$category->name}\n\n";

// Créer des signalements avec différents status
echo "📝 Création de signalements de test...\n";

$reports = [
    [
        'title' => 'Nid de poule dangereux',
        'description' => 'Un gros trou sur la route',
        'status' => 'En attente',
    ],
    [
        'title' => 'Lampadaire cassé',
        'description' => 'Éclairage public défectueux',
        'status' => 'En attente',
    ],
    [
        'title' => 'Fuite d\'eau',
        'description' => 'Fuite importante dans la rue',
        'status' => 'En cours',
    ],
    [
        'title' => 'Ordures non ramassées',
        'description' => 'Déchets accumulés depuis 3 jours',
        'status' => 'En cours',
    ],
    [
        'title' => 'Panneau de signalisation manquant',
        'description' => 'Stop disparu',
        'status' => 'Résolu',
    ],
];

foreach ($reports as $reportData) {
    Report::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => $reportData['title'],
        'description' => $reportData['description'],
        'status' => $reportData['status'],
        'latitude' => 14.739909,
        'longitude' => -17.464313,
    ]);
    echo "  ✓ {$reportData['title']} ({$reportData['status']})\n";
}

echo "\n📊 Statistiques dans la base de données :\n";
echo "  • En attente : " . Report::where('status', 'En attente')->count() . " signalements\n";
echo "  • En cours : " . Report::where('status', 'En cours')->count() . " signalements\n";
echo "  • Résolu : " . Report::where('status', 'Résolu')->count() . " signalements\n";
echo "  • Total : " . Report::count() . " signalements\n\n";

// Générer un token pour les tests
$token = $user->createToken('test-token')->plainTextToken;
echo "🔑 Token Bearer généré : {$token}\n\n";

echo "========================================\n";
echo "  ✅ TESTS À EFFECTUER\n";
echo "========================================\n\n";

echo "1️⃣  TEST SANS FILTRE (tous les signalements)\n";
echo "curl -X GET \"http://192.168.1.7:8000/api/reports\" \\\n";
echo "  -H \"Authorization: Bearer {$token}\"\n\n";

echo "2️⃣  TEST FILTRE 'En attente'\n";
echo "curl -X GET \"http://192.168.1.7:8000/api/reports?filter[status]=En%20attente\" \\\n";
echo "  -H \"Authorization: Bearer {$token}\"\n\n";

echo "3️⃣  TEST FILTRE 'En cours'\n";
echo "curl -X GET \"http://192.168.1.7:8000/api/reports?filter[status]=En%20cours\" \\\n";
echo "  -H \"Authorization: Bearer {$token}\"\n\n";

echo "4️⃣  TEST FILTRE 'Résolu'\n";
echo "curl -X GET \"http://192.168.1.7:8000/api/reports?filter[status]=Résolu\" \\\n";
echo "  -H \"Authorization: Bearer {$token}\"\n\n";

echo "5️⃣  TEST FILTRE PAR CATÉGORIE\n";
echo "curl -X GET \"http://192.168.1.7:8000/api/reports?filter[category_id]={$category->id}\" \\\n";
echo "  -H \"Authorization: Bearer {$token}\"\n\n";

echo "6️⃣  TEST FILTRE COMBINÉ (Status + Catégorie)\n";
echo "curl -X GET \"http://192.168.1.7:8000/api/reports?filter[status]=En%20cours&filter[category_id]={$category->id}\" \\\n";
echo "  -H \"Authorization: Bearer {$token}\"\n\n";

echo "========================================\n";
echo "  📱 TEST AVEC LE MOBILE\n";
echo "========================================\n\n";

echo "Utilisez ces identifiants dans l'app Flutter :\n";
echo "  • Email : test@example.com\n";
echo "  • Mot de passe : password\n\n";

echo "Puis naviguez vers la liste des signalements et testez les filtres !\n\n";

echo "✅ Setup terminé avec succès !\n\n";
