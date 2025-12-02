<?php

use App\Models\Comment;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📝 Commentaires du rapport #3:\n\n";

$comments = Comment::where('report_id', 3)
    ->with('user')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total: {$comments->count()} commentaires\n\n";

foreach ($comments as $index => $comment) {
    echo "#" . ($index + 1) . " - ID {$comment->id}\n";
    echo "   Auteur: {$comment->user->name} (ID: {$comment->user_id})\n";
    echo "   Contenu: " . substr($comment->content, 0, 50) . "...\n";
    echo "   Date: {$comment->created_at}\n\n";
}

echo "\n📊 Notifications de l'utilisateur #10 liées aux commentaires:\n\n";

$user = User::find(10);
$notifications = $user->notifications()
    ->whereRaw("json_extract(data, '$.type') = 'comment_created'")
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total: {$notifications->count()} notifications de commentaires\n\n";

foreach ($notifications as $index => $notification) {
    $data = $notification->data;
    echo "#" . ($index + 1) . " - " . ($data['message'] ?? 'No message') . "\n";
    echo "   Report ID: " . ($data['report_id'] ?? 'N/A') . "\n";
    echo "   Comment ID: " . ($data['comment_id'] ?? 'N/A') . "\n";
    echo "   Commentateur: " . ($data['commenter'] ?? 'N/A') . "\n";
    echo "   Date: {$notification->created_at}\n\n";
}
