<?php

// Script pour afficher les notifications avec détails complets (data JSON)

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::where('telephone', '+221761234567')->first();

if (!$user) {
    echo "❌ Utilisateur non trouvé\n";
    exit(1);
}

echo "👤 Utilisateur : {$user->nom} {$user->prenom} (ID: {$user->id})\n";
echo "📱 Téléphone : {$user->telephone}\n";

$notifications = $user->notifications()->latest()->limit(10)->get();

echo "📊 Nombre total de notifications : {$notifications->count()}\n\n";
echo "📋 Dernières notifications (avec données JSON) :\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($notifications as $index => $notification) {
    $data = $notification->data;
    
    echo "Notification #" . ($index + 1) . " (ID: {$notification->id})\n";
    echo "   Type: " . ($data['type'] ?? 'unknown') . "\n";
    echo "   Message: " . ($data['message'] ?? 'No message') . "\n";
    echo "   Date: {$notification->created_at->format('Y-m-d H:i:s')} ({$notification->created_at->diffForHumans()})\n";
    echo "   Lue: " . ($notification->read_at ? '✅ Oui' : '❌ Non') . "\n";
    
    if (!empty($data)) {
        echo "   Données complètes:\n";
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                echo "      - $key: " . json_encode($value) . "\n";
            } else {
                echo "      - $key: $value\n";
            }
        }
    }
    
    echo str_repeat("-", 80) . "\n\n";
}

echo "✅ Affichage terminé\n";
