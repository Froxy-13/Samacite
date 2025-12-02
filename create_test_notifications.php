<?php

// Script de test pour créer des notifications de test

use App\Models\User;
use App\Jobs\NotifyUserJob;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Récupérer le premier utilisateur
$user = User::first();

if (!$user) {
    echo "❌ Aucun utilisateur trouvé. Veuillez créer un utilisateur d'abord.\n";
    exit(1);
}

echo "📋 Création de notifications de test pour l'utilisateur #{$user->id} ({$user->email})\n\n";

// Notification 1 : Test général
NotifyUserJob::dispatch(
    $user,
    'Notification de test - Backend Laravel',
    [
        'type' => 'test',
        'message' => 'Ceci est une notification de test pour valider le système',
        'timestamp' => now()->toDateTimeString(),
    ]
);
echo "✅ Notification 1 créée : Test général\n";

// Notification 2 : Simulation de like
NotifyUserJob::dispatch(
    $user,
    'Quelqu\'un a aimé votre signalement',
    [
        'type' => 'report_liked',
        'report_id' => 1,
        'liker_name' => 'Utilisateur Test',
        'liker_id' => 999,
    ]
);
echo "✅ Notification 2 créée : Simulation de like\n";

// Notification 3 : Simulation de commentaire
NotifyUserJob::dispatch(
    $user,
    'Un nouveau commentaire a été ajouté à votre signalement',
    [
        'type' => 'comment_created',
        'report_id' => 1,
        'comment_id' => 1,
        'commenter' => 'Jean Dupont',
    ]
);
echo "✅ Notification 3 créée : Simulation de commentaire\n";

// Notification 4 : Simulation de changement de statut
NotifyUserJob::dispatch(
    $user,
    'Le statut de votre signalement a changé de En attente à En cours',
    [
        'type' => 'status_changed',
        'report_id' => 1,
        'old_status' => 'En attente',
        'new_status' => 'En cours',
    ]
);
echo "✅ Notification 4 créée : Simulation de changement de statut\n";

echo "\n🎉 4 notifications de test créées avec succès!\n";
echo "📌 Utilisateur : {$user->email} (ID: {$user->id})\n";
echo "💡 Lancez 'php artisan queue:work' pour traiter les jobs si vous utilisez les queues.\n";
