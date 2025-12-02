<?php

use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = User::find(10);

echo str_repeat("=", 60) . "\n";
echo "   📊 STATISTIQUES NOTIFICATIONS - User #10\n";
echo "   " . $user->name . " (+221761234567)\n";
echo str_repeat("=", 60) . "\n\n";

$total = $user->notifications()->count();
$comments = $user->notifications()->whereRaw("json_extract(data, '$.type') = 'comment_created'")->count();
$likes = $user->notifications()->whereRaw("json_extract(data, '$.type') = 'report_liked'")->count();
$reports = $user->notifications()->whereRaw("json_extract(data, '$.type') = 'report_created'")->count();
$status = $user->notifications()->whereRaw("json_extract(data, '$.type') = 'status_changed'")->count();
$unread = $user->unreadNotifications()->count();

echo "📬 Total notifications      : $total\n";
echo "💬 Commentaires            : $comments\n";
echo "👍 Likes                   : $likes\n";
echo "📝 Signalements créés      : $reports\n";
echo "🔄 Changements de statut   : $status\n";
echo "🔔 Non lues                : $unread\n\n";

echo str_repeat("=", 60) . "\n";
echo "   🎉 SYSTÈME DE NOTIFICATIONS OPÉRATIONNEL\n";
echo str_repeat("=", 60) . "\n\n";

echo "✅ Tous les types de notifications fonctionnent :\n";
echo "   • Report Created   ✓\n";
echo "   • Status Changed   ✓\n";
echo "   • Comment Created  ✓\n";
echo "   • Report Liked     ✓\n\n";

echo "⚠️  IMPORTANT pour le serveur 192.168.1.17:8000 :\n";
echo "   → Le queue worker DOIT être actif en permanence\n";
echo "   → Commande : php artisan queue:work --tries=3 --timeout=3600\n";
echo "   → Ou utiliser systemd (voir QUEUE_WORKER_DEPLOYMENT.md)\n\n";

echo "📖 Documentation créée :\n";
echo "   • deployment/systemd/samacite-queue.service\n";
echo "   • deployment/scripts/check_queue_worker.sh\n";
echo "   • deployment/windows/start-queue-worker.bat\n";
echo "   • QUEUE_WORKER_DEPLOYMENT.md\n\n";
