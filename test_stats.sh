#!/bin/bash
cd /c/Users/HP/Desktop/samacite
echo "🧪 Testing Stats Endpoint..."
php artisan tinker << 'PHP'
use App\Models\User;
use App\Services\StatsService;

$user = User::where('email', 'thiane@samacite.sn')->first();
echo "\n✅ Admin user: " . $user->email . "\n";

$statsService = app(StatsService::class);
$stats = $statsService->getGlobalStats();

echo "📊 Global Stats:\n";
echo "   Total Reports: " . $stats['total_reports'] . "\n";
echo "   Total Users: " . $stats['total_users'] . "\n";
echo "   Average Reports per User: " . $stats['average_reports_per_user'] . "\n";

echo "\n✅ Stats loaded successfully!\n";
exit(0);
PHP
