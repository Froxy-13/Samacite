<?php

require __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Report;

// Create users
$admin = User::factory()->create([
    'email' => 'admin@test.com',
    'telephone' => '+221771234567'
]);
$admin->assignRole('Admin');

$user1 = User::factory()->create([
    'email' => 'user1@test.com',
    'telephone' => '+221771234568'
]);
$user1->assignRole('Utilisateur');

$user2 = User::factory()->create([
    'email' => 'user2@test.com',
    'telephone' => '+221771234569'
]);
$user2->assignRole('Utilisateur');

// Create category
$category = Category::factory()->create();

// Create report (will trigger ReportCreated event and notification)
$report = Report::factory()->create([
    'user_id' => $user1->id,
    'category_id' => $category->id,
    'status' => 'En attente'
]);

echo "=== TEST DATA CREATED ===\n\n";
echo "ADMIN:\n";
echo "  Email: " . $admin->email . "\n";
echo "  Token: " . $admin->createToken('api')->plainTextToken . "\n\n";

echo "USER 1:\n";
echo "  Email: " . $user1->email . "\n";
echo "  Token: " . $user1->createToken('api')->plainTextToken . "\n\n";

echo "USER 2:\n";
echo "  Email: " . $user2->email . "\n";
echo "  Token: " . $user2->createToken('api')->plainTextToken . "\n\n";

echo "REPORT:\n";
echo "  ID: " . $report->id . "\n\n";

echo "NOTIFICATIONS:\n";
echo "  Admin notifications: " . $admin->notifications()->count() . "\n";
echo "  User1 notifications: " . $user1->notifications()->count() . "\n";
echo "  User2 notifications: " . $user2->notifications()->count() . "\n";
