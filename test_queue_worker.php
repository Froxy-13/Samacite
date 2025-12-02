<?php

// Script pour tester le queue worker

use App\Models\User;
use App\Jobs\NotifyUserJob;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test du Queue Worker\n";
echo str_repeat("=", 50) . "\n\n";

$user = User::find(10);

echo "📤 Création d'une notification test...\n";
echo "   Utilisateur : {$user->name}\n";
echo "   Type : test_queue_worker\n\n";

NotifyUserJob::dispatch(
    $user,
    '🎉 Test réussi - Queue worker opérationnel !',
    [
        'type' => 'test_queue_worker',
        'timestamp' => now()->toDateTimeString(),
        'message' => 'Si vous voyez cette notification, le queue worker fonctionne correctement'
    ]
);

echo "✅ Job ajouté à la queue\n";
echo "⏳ Le worker devrait le traiter dans quelques secondes...\n\n";

echo "💡 Vérifiez :\n";
echo "   1. La fenêtre du queue worker pour voir le job traité\n";
echo "   2. Les notifications : php check_comment_notifications.php\n\n";

// Attendre 3 secondes pour laisser le worker traiter
echo "⏰ Attente de 3 secondes...\n";
sleep(3);

echo "\n📊 Vérification des notifications récentes...\n\n";

$recentNotifications = $user->notifications()
    ->where('created_at', '>', now()->subMinutes(1))
    ->orderBy('created_at', 'desc')
    ->get();

if ($recentNotifications->count() > 0) {
    echo "✅ {$recentNotifications->count()} notification(s) créée(s) dans la dernière minute :\n\n";
    
    foreach ($recentNotifications as $notif) {
        $data = $notif->data;
        echo "   • " . ($data['message'] ?? 'No message') . "\n";
        echo "     Type: " . ($data['type'] ?? 'unknown') . "\n";
        echo "     Date: {$notif->created_at->format('H:i:s')}\n\n";
    }
    
    echo "🎉 QUEUE WORKER FONCTIONNE PARFAITEMENT !\n";
} else {
    echo "⚠️  Aucune notification récente trouvée.\n";
    echo "   Le worker est peut-être encore en train de démarrer.\n";
}
