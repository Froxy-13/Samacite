<?php

// Script de démonstration du système de notifications
// À exécuter via: php artisan tinker < test_notifications_demo.php

use App\Models\User;
use App\Models\Category;
use App\Models\Report;

// Seed roles and permissions
\Database\Seeders\RolesAndPermissionsSeeder::class;

echo "=== Demo: Système de Notifications ===\n\n";

// 1. Create test users
echo "[1] Création des utilisateurs de test...\n";
$user = User::factory()->create(['email' => 'test_user_' . time() . '@example.com']);
$user->assignRole('Utilisateur');
echo "✓ Utilisateur créé: {$user->email}\n";

$admin = User::factory()->create(['email' => 'admin_' . time() . '@example.com']);
$admin->assignRole('Admin');
echo "✓ Admin créé: {$admin->email}\n\n";

// 2. Create a report (should trigger notification)
echo "[2] Création d'un signalement...\n";
$category = Category::factory()->create();
$report = Report::factory()->create([
    'user_id' => $user->id,
    'category_id' => $category->id,
    'title' => 'Test Notification Report',
    'description' => 'This report triggers notifications'
]);
echo "✓ Signalement créé: {$report->title} (ID: {$report->id})\n\n";

// 3. Check notifications for user
echo "[3] Notifications de l'utilisateur:\n";
$userNotifications = $user->notifications()->get();
echo "  Total: {$userNotifications->count()}\n";
foreach ($userNotifications as $notif) {
    echo "  - Type: {$notif->type}\n";
    echo "    Data: " . json_encode($notif->data) . "\n";
}
echo "\n";

// 4. Check notifications for admin
echo "[4] Notifications de l'admin:\n";
$adminNotifications = $admin->notifications()->get();
echo "  Total: {$adminNotifications->count()}\n";
foreach ($adminNotifications as $notif) {
    echo "  - Type: {$notif->type}\n";
    echo "    Data: " . json_encode($notif->data) . "\n";
}
echo "\n";

// 5. Change report status
echo "[5] Changement du statut du rapport...\n";
$report->updateStatus('En cours');
echo "✓ Statut changé: En cours\n\n";

// 6. Check updated notifications
echo "[6] Notifications après changement de statut:\n";
$updatedNotifications = $user->notifications()->get();
echo "  Total: {$updatedNotifications->count()}\n";
foreach ($updatedNotifications as $notif) {
    echo "  - Type: {$notif->type}\n";
    echo "    Data: " . json_encode($notif->data) . "\n";
}
echo "\n";

echo "=== Demo terminée avec succès! ===\n";
