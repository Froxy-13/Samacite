<?php

// Script de test synchrone pour créer des notifications immédiatement

use App\Models\User;
use App\Notifications\GenericNotification;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Récupérer le premier utilisateur
$user = User::first();

if (!$user) {
    echo "❌ Aucun utilisateur trouvé. Veuillez créer un utilisateur d'abord.\n";
    exit(1);
}

echo "📋 Création de notifications de test SYNCHRONES pour l'utilisateur #{$user->id} ({$user->email})\n\n";

// Notification 1 : Test général
$user->notify(new GenericNotification(
    'Notification de test - Backend Laravel',
    [
        'type' => 'test',
        'message' => 'Ceci est une notification de test pour valider le système',
        'timestamp' => now()->toDateTimeString(),
    ]
));
echo "✅ Notification 1 créée : Test général\n";

// Notification 2 : Simulation de like
$user->notify(new GenericNotification(
    'Quelqu\'un a aimé votre signalement',
    [
        'type' => 'report_liked',
        'report_id' => 1,
        'liker_name' => 'Utilisateur Test',
        'liker_id' => 999,
    ]
));
echo "✅ Notification 2 créée : Simulation de like\n";

// Notification 3 : Simulation de commentaire
$user->notify(new GenericNotification(
    'Un nouveau commentaire a été ajouté à votre signalement',
    [
        'type' => 'comment_created',
        'report_id' => 1,
        'comment_id' => 1,
        'commenter' => 'Jean Dupont',
    ]
));
echo "✅ Notification 3 créée : Simulation de commentaire\n";

// Notification 4 : Simulation de changement de statut
$user->notify(new GenericNotification(
    'Le statut de votre signalement a changé de En attente à En cours',
    [
        'type' => 'status_changed',
        'report_id' => 1,
        'old_status' => 'En attente',
        'new_status' => 'En cours',
    ]
));
echo "✅ Notification 4 créée : Simulation de changement de statut\n";

echo "\n🎉 4 notifications de test créées et enregistrées immédiatement!\n";
echo "📌 Utilisateur : {$user->email} (ID: {$user->id})\n";
echo "💾 Les notifications sont déjà dans la base de données\n";
