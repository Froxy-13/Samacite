<?php

// Script pour afficher les notifications de test

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Récupérer l'utilisateur avec le téléphone +221761234567 (ID: 10)
$user = User::find(10);

if (!$user) {
    echo "❌ Utilisateur ID 10 non trouvé\n";
    exit(1);
}

echo "👤 Utilisateur : {$user->nom} {$user->prenom} (ID: {$user->id})\n";
echo "📱 Téléphone : {$user->telephone}\n";

$count = $user->notifications()->count();
echo "📊 Nombre total de notifications : {$count}\n\n";

echo "📋 Dernières notifications :\n";
echo str_repeat('-', 80) . "\n";

$notifications = $user->notifications()->latest()->take(10)->get();

foreach ($notifications as $notification) {
    $data = $notification->data;
    $type = $data['type'] ?? 'unknown';
    $message = $data['message'] ?? 'No message';
    $createdAt = $notification->created_at->format('Y-m-d H:i:s');
    $ago = $notification->created_at->diffForHumans();
    
    echo "🔔 {$message}\n";
    echo "   Type: {$type}\n";
    echo "   Date: {$createdAt} ({$ago})\n";
    echo "   Lue: " . ($notification->read_at ? '✅ Oui' : '❌ Non') . "\n";
    echo str_repeat('-', 80) . "\n";
}

echo "\n✅ Affichage terminé\n";
