<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::find(10);
$notifications = $user->notifications()->latest()->take(5)->get();

echo "📊 5 dernières notifications de l'utilisateur #10:\n\n";

foreach ($notifications as $index => $notification) {
    echo "Notification #" . ($index + 1) . " (créée le {$notification->created_at}):\n";
    echo json_encode($notification->data, JSON_PRETTY_PRINT) . "\n";
    echo str_repeat("-", 80) . "\n\n";
}
