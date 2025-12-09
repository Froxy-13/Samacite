#!/bin/bash
cd /c/Users/HP/Desktop/samacite
echo "🔔 Testing Notifications..."
php artisan tinker << 'PHP'
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

$admin = User::where('email', 'thiane@samacite.sn')->first();
echo "\n✅ Admin user: " . $admin->email . "\n";

$notifications = DatabaseNotification::where('notifiable_id', $admin->id)->get();

echo "\n📬 Notifications ({$notifications->count()} total):\n";
foreach ($notifications as $notif) {
    $data = json_decode($notif->data, true);
    echo "   - " . $data['message'] . "\n";
    echo "     Report ID: " . $data['report_id'] . " → {$data['url']}\n";
}

echo "\n✅ Notifications loaded successfully!\n";
exit(0);
PHP
