<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::find(10);
$allNotifications = $user->notifications()->orderBy('created_at', 'desc')->get();

echo "📊 TOUTES les notifications de l'utilisateur #10:\n\n";
echo "Total: {$allNotifications->count()} notifications\n\n";

foreach ($allNotifications as $index => $notification) {
    $data = $notification->data;
    $num = $index + 1;
    
    echo str_repeat("=", 80) . "\n";
    echo "Notification #{$num} (ID: {$notification->id})\n";
    echo "Date: {$notification->created_at}\n";
    echo "Lue: " . ($notification->read_at ? 'Oui' : 'Non') . "\n";
    echo "JSON complet:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
