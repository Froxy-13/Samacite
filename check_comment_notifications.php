<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::find(10);
$notifications = $user->notifications()->latest()->take(10)->get();

echo "📊 10 dernières notifications de l'utilisateur #10:\n\n";

foreach ($notifications as $index => $notification) {
    $data = $notification->data;
    $type = $data['type'] ?? (isset($data['data']['type']) ? $data['data']['type'] : 'unknown');
    
    echo "#" . ($index + 1) . " | Type: {$type} | " . ($data['message'] ?? 'No message') . "\n";
    echo "    Date: {$notification->created_at}\n";
    
    if ($type === 'comment_created') {
        echo "    ⭐ COMMENTAIRE:\n";
        echo "       Report ID: " . ($data['report_id'] ?? 'N/A') . "\n";
        echo "       Comment ID: " . ($data['comment_id'] ?? 'N/A') . "\n";
        echo "       Commentateur: " . ($data['commenter'] ?? 'N/A') . "\n";
    }
    
    echo "\n";
}
