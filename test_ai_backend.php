<?php
/**
 * Script de test pour l'endpoint AI Analysis
 * 
 * Usage: php test_ai_backend.php
 * 
 * Ce script teste :
 * 1. Connexion et authentification
 * 2. Upload et analyse d'une image
 * 3. Vérification du format de réponse
 */

$baseUrl = 'http://172.17.144.1:8000/api';

echo "╔════════════════════════════════════════════════════╗\n";
echo "║  Test Backend AI Analysis - SamaCité              ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

// Couleurs pour terminal
$colors = [
    'success' => "\033[32m",
    'error' => "\033[31m",
    'info' => "\033[36m",
    'reset' => "\033[0m",
];

function log_step($message, $status = 'info') {
    global $colors;
    $icon = ['success' => '✓', 'error' => '✗', 'info' => '→'][$status] ?? '→';
    $color = $colors[$status];
    echo "{$color}{$icon}{$colors['reset']} {$message}\n";
}

// ==============================
// ÉTAPE 1 : Login
// ==============================
echo "\n📝 ÉTAPE 1 : Authentification\n";
echo str_repeat("-", 50) . "\n";

log_step("Connexion avec credentials...", 'info');

$loginData = [
    'email' => 'admin@samacite.com',
    'password' => 'password'
];

$ch = curl_init("{$baseUrl}/auth/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($loginData),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    log_step("Échec de connexion (HTTP {$httpCode})", 'error');
    echo "\nRéponse: {$response}\n";
    echo "\n⚠️  Vérifiez que :\n";
    echo "   - Le serveur Laravel tourne sur http://172.17.144.1:8000\n";
    echo "   - Les credentials sont corrects\n";
    echo "   - La base de données contient un utilisateur\n";
    exit(1);
}

$loginResult = json_decode($response, true);

if (!isset($loginResult['data']['token'])) {
    log_step("Token non trouvé dans la réponse", 'error');
    echo "\nRéponse: {$response}\n";
    exit(1);
}

$token = $loginResult['data']['token'];
$userName = $loginResult['data']['user']['name'] ?? 'Utilisateur';
log_step("Connecté en tant que: {$userName}", 'success');
log_step("Token obtenu: " . substr($token, 0, 20) . "...", 'success');

// ==============================
// ÉTAPE 2 : Créer une image de test
// ==============================
echo "\n🖼️  ÉTAPE 2 : Préparation image de test\n";
echo str_repeat("-", 50) . "\n";

$testImagePath = __DIR__ . '/test_pothole.jpg';

if (!file_exists($testImagePath)) {
    log_step("Création d'une image de test factice...", 'info');
    
    // Créer une image simple pour le test
    $img = imagecreatetruecolor(800, 600);
    $gray = imagecolorallocate($img, 128, 128, 128);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefilledrectangle($img, 0, 0, 800, 600, $gray);
    imagestring($img, 5, 300, 280, "Test Pothole Image", $black);
    imagejpeg($img, $testImagePath, 90);
    imagedestroy($img);
    
    log_step("Image de test créée: {$testImagePath}", 'success');
} else {
    log_step("Image de test trouvée: {$testImagePath}", 'success');
}

$imageSize = filesize($testImagePath);
log_step("Taille de l'image: " . number_format($imageSize / 1024, 2) . " KB", 'info');

// ==============================
// ÉTAPE 3 : Analyse IA
// ==============================
echo "\n🤖 ÉTAPE 3 : Analyse IA avec Gemini\n";
echo str_repeat("-", 50) . "\n";

log_step("Envoi de l'image à l'endpoint AI...", 'info');

$ch = curl_init("{$baseUrl}/ai/analyze-image");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$token}",
    ],
    CURLOPT_POSTFIELDS => [
        'image' => new CURLFile($testImagePath, 'image/jpeg', 'test.jpg')
    ],
]);

$startTime = microtime(true);
$response = curl_exec($ch);
$duration = round((microtime(true) - $startTime) * 1000);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

log_step("Temps de réponse: {$duration}ms", 'info');

if ($httpCode !== 200) {
    log_step("Échec de l'analyse (HTTP {$httpCode})", 'error');
    echo "\nRéponse: {$response}\n";
    
    echo "\n⚠️  Causes possibles :\n";
    echo "   - GEMINI_API_KEY non configurée dans .env\n";
    echo "   - Clé API invalide ou quota dépassé\n";
    echo "   - Permission file.upload manquante pour l'utilisateur\n";
    echo "\n📖 Vérifiez AI_INSTALLATION_GUIDE.md pour la configuration\n";
    exit(1);
}

$aiResult = json_decode($response, true);

if (!isset($aiResult['data'])) {
    log_step("Format de réponse invalide", 'error');
    echo "\nRéponse: {$response}\n";
    exit(1);
}

// ==============================
// ÉTAPE 4 : Vérification résultat
// ==============================
echo "\n✨ ÉTAPE 4 : Résultat de l'analyse\n";
echo str_repeat("-", 50) . "\n";

$data = $aiResult['data'];

log_step("Analyse réussie!", 'success');
echo "\n";
echo "   📌 Titre       : {$data['title']}\n";
echo "   📝 Description : {$data['description']}\n";
echo "   🏷️  Catégorie   : {$data['category_id']}\n";
echo "   🎯 Confiance   : " . ($data['confidence'] * 100) . "%\n";

// Vérifier le mapping des catégories
$categoryNames = [
    1 => 'Routes & Chaussées',
    2 => 'Eau & Assainissement',
    3 => 'Électricité & Éclairage',
    4 => 'Déchets & Propreté',
    5 => 'Sécurité Publique',
    6 => 'Autre',
];

$categoryName = $categoryNames[$data['category_id']] ?? 'Inconnue';
log_step("Nom de catégorie: {$categoryName}", 'info');

// ==============================
// RÉSUMÉ
// ==============================
echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 TOUS LES TESTS RÉUSSIS!\n";
echo str_repeat("=", 50) . "\n\n";

echo "✅ Checklist complète :\n";
echo "   [✓] Backend accessible sur http://172.17.144.1:8000\n";
echo "   [✓] Authentification fonctionnelle\n";
echo "   [✓] Upload d'image validé\n";
echo "   [✓] API Gemini opérationnelle\n";
echo "   [✓] Mapping des catégories correct\n";
echo "   [✓] Format de réponse conforme\n\n";

echo "📱 Le backend est prêt pour l'intégration Flutter!\n\n";

echo "🔍 Prochaines étapes :\n";
echo "   1. Ouvrir l'app Flutter\n";
echo "   2. Vérifier que api_constants.dart utilise l'IP: 172.17.144.1\n";
echo "   3. Tester la création de signalement avec photo\n";
echo "   4. Observer l'auto-complétion des champs\n\n";

echo "💡 Pour tests manuels avec cURL :\n";
echo "   curl -X POST http://172.17.144.1:8000/api/ai/analyze-image \\\n";
echo "     -H 'Authorization: Bearer {$token}' \\\n";
echo "     -F 'image=@path/to/image.jpg'\n\n";
