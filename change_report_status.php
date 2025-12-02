<?php

// Script pour changer le statut d'un signalement et déclencher une notification

use App\Models\User;
use App\Models\Report;
use App\Events\StatusChanged;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Trouver l'utilisateur avec le téléphone +221761234567
$user = User::where('telephone', '+221761234567')->first();

if (!$user) {
    echo "❌ Utilisateur avec le téléphone +221761234567 non trouvé\n";
    echo "💡 Utilisateurs disponibles :\n";
    User::all()->each(function($u) {
        echo "   - ID: {$u->id}, Nom: {$u->nom} {$u->prenom}, Tel: {$u->telephone}\n";
    });
    exit(1);
}

echo "👤 Utilisateur trouvé : {$user->nom} {$user->prenom} (ID: {$user->id})\n";
echo "📧 Email : {$user->email}\n";
echo "📱 Téléphone : {$user->telephone}\n\n";

// Trouver un signalement "En cours" de cet utilisateur
$report = Report::where('user_id', $user->id)
    ->where('status', 'En cours')
    ->first();

if (!$report) {
    echo "❌ Aucun signalement 'En cours' trouvé pour cet utilisateur\n";
    echo "💡 Signalements de cet utilisateur :\n";
    Report::where('user_id', $user->id)->get()->each(function($r) {
        echo "   - ID: {$r->id}, Titre: {$r->title}, Statut: {$r->status}\n";
    });
    
    // Si aucun "En cours", prenons le premier "En attente"
    $report = Report::where('user_id', $user->id)
        ->where('status', 'En attente')
        ->first();
    
    if (!$report) {
        echo "\n❌ Aucun signalement 'En attente' non plus\n";
        exit(1);
    }
    
    echo "\n💡 Utilisation d'un signalement 'En attente' à la place\n";
}

echo "\n📋 Signalement trouvé :\n";
echo "   - ID: {$report->id}\n";
echo "   - Titre: {$report->title}\n";
echo "   - Statut actuel: {$report->status}\n";
echo "   - Catégorie: " . ($report->category ? $report->category->name : 'N/A') . "\n\n";

// Changer le statut en utilisant la méthode updateStatus qui déclenche l'event
$oldStatus = $report->status;
$newStatus = 'Résolu';

echo "🔄 Changement de statut : '{$oldStatus}' → '{$newStatus}'\n";

$report->updateStatus($newStatus);

echo "✅ Statut changé avec succès!\n";
echo "🔔 Event StatusChanged déclenché\n";
echo "📨 Une notification devrait être créée pour l'utilisateur #{$user->id}\n\n";

// Vérifier si le job a été dispatché
echo "💡 Lancez 'php artisan queue:work --once' pour traiter la notification\n";
echo "💡 Ou vérifiez avec 'php show_notifications.php'\n";
