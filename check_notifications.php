<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check notifications
$notifications = DB::table('notifications')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "=== Dernières 10 notifications ===\n";
foreach ($notifications as $notif) {
    $data = json_decode($notif->data, true);
    echo "ID: {$notif->id}\n";
    echo "User ID: {$notif->notifiable_id}\n";
    echo "Type: {$notif->type}\n";
    echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
    echo "Created: {$notif->created_at}\n";
    echo "Read: " . ($notif->read_at ? 'Oui' : 'Non') . "\n";
    echo "---\n";
}

echo "\n=== Statistiques ===\n";
echo "Total notifications: " . DB::table('notifications')->count() . "\n";
echo "Notifications non lues: " . DB::table('notifications')->whereNull('read_at')->count() . "\n";
echo "Notifications créées aujourd'hui: " . DB::table('notifications')->whereDate('created_at', today())->count() . "\n";

// Check admins
$admins = DB::table('users')
    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->where('roles.name', 'Admin')
    ->select('users.id', 'users.nom', 'users.prenom', 'users.email')
    ->get();

echo "\n=== Admins trouvés ===\n";
foreach ($admins as $admin) {
    echo "ID: {$admin->id} - {$admin->prenom} {$admin->nom} ({$admin->email})\n";
    $notifCount = DB::table('notifications')
        ->where('notifiable_id', $admin->id)
        ->count();
    echo "  Notifications: {$notifCount}\n";
}

// Check recent reports
$recentReports = DB::table('reports')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'title', 'user_id', 'created_at']);

echo "\n=== 5 derniers signalements ===\n";
foreach ($recentReports as $report) {
    echo "ID: {$report->id} - {$report->title} (User: {$report->user_id}) - {$report->created_at}\n";
}
