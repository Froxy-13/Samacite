<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Test des notifications - Mode QUEUE: " . env('QUEUE_CONNECTION') . "\n\n";

// Get a test user
$user = DB::table('users')->where('id', 5)->first();
if (!$user) {
    echo "❌ Utilisateur ID 5 non trouvé. Ajustez l'ID dans le script.\n";
    exit(1);
}

echo "👤 Utilisateur test: {$user->prenom} {$user->nom} (ID: {$user->id})\n";

// Count notifications before
$notifsBefore = DB::table('notifications')->count();
echo "📊 Notifications avant test: {$notifsBefore}\n\n";

// Create a test report
echo "📝 Création d'un signalement de test...\n";
$report = DB::table('reports')->insertGetId([
    'user_id' => $user->id,
    'title' => 'TEST NOTIFICATION - ' . date('H:i:s'),
    'description' => 'Ceci est un test de notification automatique',
    'status' => 'en_cours',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Signalement créé (ID: {$report})\n\n";

// Manually trigger the event (simulating what happens in the controller)
echo "🔔 Déclenchement de l'event ReportCreated...\n";
$reportModel = \App\Models\Report::with(['user', 'category'])->find($report);
\App\Events\ReportCreated::dispatch($reportModel);

// Wait a bit for processing
echo "⏳ Attente de 2 secondes pour le traitement...\n";
sleep(2);

// Count notifications after
$notifsAfter = DB::table('notifications')->count();
$newNotifs = $notifsAfter - $notifsBefore;

echo "\n📊 Résultats:\n";
echo "   Notifications avant: {$notifsBefore}\n";
echo "   Notifications après: {$notifsAfter}\n";
echo "   Nouvelles notifications: {$newNotifs}\n\n";

if ($newNotifs > 0) {
    echo "✅ SUCCESS! {$newNotifs} notification(s) créée(s)\n\n";
    
    // Show the new notifications
    $newNotifsData = DB::table('notifications')
        ->orderBy('created_at', 'desc')
        ->limit($newNotifs)
        ->get();
    
    echo "📬 Détails des notifications:\n";
    foreach ($newNotifsData as $notif) {
        $data = json_decode($notif->data, true);
        $user = DB::table('users')->find($notif->notifiable_id);
        echo "   → User: {$user->prenom} {$user->nom}\n";
        echo "     Message: " . ($data['message'] ?? 'N/A') . "\n";
        echo "     Type: " . ($data['type'] ?? 'N/A') . "\n";
        echo "     Admin: " . (isset($data['admin']) ? 'Oui' : 'Non') . "\n\n";
    }
} else {
    echo "❌ ÉCHEC! Aucune notification créée\n";
    echo "   Vérifiez les logs: storage/logs/laravel.log\n";
    exit(1);
}

// Check admins received notifications
$admins = DB::table('users')
    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->where('roles.name', 'Admin')
    ->select('users.id')
    ->pluck('id');

$adminNotifs = DB::table('notifications')
    ->whereIn('notifiable_id', $admins)
    ->where('created_at', '>=', now()->subMinutes(1))
    ->count();

echo "👨‍💼 Notifications aux admins: {$adminNotifs}\n";
echo "   Admins dans le système: " . count($admins) . "\n\n";

if ($adminNotifs >= count($admins)) {
    echo "✅ Tous les admins ont reçu la notification!\n";
} else {
    echo "⚠️  Certains admins n'ont pas reçu la notification\n";
}

echo "\n🎉 Test terminé!\n";
