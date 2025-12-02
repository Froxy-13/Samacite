<?php

// Script pour débugger le processus de notification de commentaire

use App\Models\User;
use App\Models\Report;
use App\Models\Comment;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Trouver l'utilisateur avec le téléphone +221761234567
$user = User::where('telephone', '+221761234567')->first();
$commenter = User::find(1); // Admin

echo "Testing comment notification manually...\n\n";

echo "1️⃣ User model has 'name' accessor: ";
try {
    $name = $commenter->name;
    echo "✅ YES (value: {$name})\n";
} catch (\Exception $e) {
    echo "❌ NO (error: {$e->getMessage()})\n";
}

echo "\n2️⃣ Report for user: ";
$report = Report::where('user_id', $user->id)->first();
echo "✅ Found report ID {$report->id}\n";

echo "\n3️⃣ Creating comment...\n";
$comment = Comment::create([
    'report_id' => $report->id,
    'user_id' => $commenter->id,
    'content' => 'Test comment for debugging',
]);
echo "✅ Comment created (ID: {$comment->id})\n";

echo "\n4️⃣ Manual notification dispatch:\n";
$listener = new \App\Listeners\SendCommentNotification();
try {
    $event = new \App\Events\CommentCreated($comment);
    $listener->handle($event);
    echo "✅ Listener executed\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "    Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n💡 Check queue:work to process NotifyUserJob\n";
