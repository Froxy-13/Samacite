<?php

// Script pour créer un commentaire et déclencher une notification

use App\Models\User;
use App\Models\Report;
use App\Models\Comment;
use App\Events\CommentCreated;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Trouver l'utilisateur avec le téléphone +221761234567
$user = User::where('telephone', '+221761234567')->first();

if (!$user) {
    echo "❌ Utilisateur non trouvé\n";
    exit(1);
}

echo "👤 Utilisateur : {$user->nom} {$user->prenom} (ID: {$user->id})\n\n";

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

// Créer le commentaire
$comment = Comment::create([
    'report_id' => $report->id,
    'user_id' => $commenter->id,
    'content' => 'Bonjour, j\'ai le même problème dans mon quartier. Merci de résoudre cela rapidement !',
]);

echo "✅ Commentaire créé (ID: {$comment->id})\n";

// Déclencher l'event
event(new CommentCreated($comment));

echo "🔔 Event CommentCreated déclenché\n";
echo "📨 Une notification devrait être créée pour l'utilisateur #{$user->id}\n\n";
echo "💡 Lancez 'php artisan queue:work --max-jobs=5 --stop-when-empty' pour traiter\n";
